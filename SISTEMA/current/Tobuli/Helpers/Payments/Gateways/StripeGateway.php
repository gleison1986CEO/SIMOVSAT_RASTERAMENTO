<?php

namespace Tobuli\Helpers\Payments\Gateways;

use App\Exceptions\PaymentsConfigurationException;
use App\Exceptions\PaymentsIssueException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Stripe\Balance;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Exception\CardException;
use Stripe\Invoice;
use Stripe\PaymentIntent;
use Stripe\Plan;
use Stripe\Stripe;
use Stripe\Subscription;
use Tobuli\Entities\Order;

class StripeGateway extends PaymentGateway implements PaymentGatewayInterface
{
    private $config;
    private $currency;

    public function __construct()
    {
        $this->config = settings('payments.stripe');
        $this->currency = strtolower($this->config['currency']);
        Stripe::setApiKey($this->config['secret_key']);

        parent::__construct();
    }

    public function pay($user, Order $order)
    {
        try {
            $customer = $this->createCustomer($user, request('stripeSource'));

            $intent = PaymentIntent::retrieve(request('intent'));
        } catch (CardException $e) {
            $body = $e->getJsonBody();

            $this->handleException(new PaymentsIssueException($body['error']['message']), $body);
        }

        if ($intent->status != 'succeeded') {
            $this->handleException(new CardException('Could not charge the card'));
        }

        $this->storeSubscription($user, $order, $intent->id);

        return Redirect::route('payments.pay_callback', [
            'gateway'   => $this->gatewayName(),
            'intent_id' => $intent->id,
        ]);
    }

    public function payCallback(Request $request)
    {
        try {
            $intent = PaymentIntent::retrieve($request->intent_id);
        } catch (CardException $e) {
            $body = $e->getJsonBody();

            $this->handleException(new PaymentsIssueException($body['error']['message']), $body);
        }

        if ($intent->status != 'succeeded') {
            $this->handleException(new CardException('Could not charge the card'));
        }

        $this->activateSubscription($request->intent_id);

        return Redirect::route('payments.success');
    }

    public function subscribe($user, Order $order)
    {
        if (!empty($this->config['one_time_payment'])) {
            return $this->pay($user, $order);
        }

        try {
            $intent = PaymentIntent::retrieve(request('intent'));

            if ($intent->status != 'succeeded') {
                $this->handleException(new CardException('Could not charge the card'));
            }

            if (!$stripe_plan = $this->existingPlan($order->plan)) {
                $stripe_plan = $this->createPlan($order->plan);
            }

            $customer = $this->createCustomer($user, request('stripeSource'));

            $trialEnd = $order->plan
                ->calculateExpirationDate(Carbon::now());
            $subscription = Subscription::create([
                'customer' => $customer->id,
                'items'    => [
                    [
                        'plan' => $stripe_plan,
                    ],
                ],
                'payment_behavior' => 'error_if_incomplete',
                'trial_end' => Carbon::parse($trialEnd)
                    ->timestamp,
            ]);
        } catch (CardException $e) {
            $body = $e->getJsonBody();

            $this->handleException(new PaymentsIssueException($body['error']['message']), $body);
        }

        $this->storeSubscription($user, $order, $subscription->id);

        return Redirect::route('payments.subscribe_callback', [
            'gateway'         => $this->gatewayName(),
            'subscription_id' => $subscription->id,
            'intent_id'       => $intent->id,
        ]);
    }

    public function subscribeCallback(Request $request)
    {
        try {
            $subscription = Subscription::retrieve($request->subscription_id);
        } catch (CardException $e) {
            $body = $e->getJsonBody();

            $this->handleException(new PaymentsIssueException($body['error']['message']), $body);
        }

        try {
            $intent = PaymentIntent::retrieve($request->intent_id);
        } catch (CardException $e) {
            $body = $e->getJsonBody();

            $this->handleException(new PaymentsIssueException($body['error']['message']), $body);
        }

        if ($intent->status != 'succeeded') {
            $subscription->cancel();

            $this->handleException(new PaymentsIssueException('Could not charge the card'));
        }

        if ($subscription->status === Subscription::STATUS_PAST_DUE) {
            $subscription->cancel();
        }

        if (in_array($subscription->status, [Subscription::STATUS_TRIALING, Subscription::STATUS_ACTIVE])) {
            $this->activateSubscription($subscription->id);
        }

        return Redirect::route('payments.success');
    }

    public function checkout(Order $order)
    {
        $paymentIntent = $this->getPaymentIntent($order);

        return view('front::Subscriptions.Gateways.stripe')->with([
            'order_id'       => $order->id,
            'gateway'        => $this->gatewayName(),
            'public_key'     => $this->config['public_key'],
            'payment_intent' => $paymentIntent,
        ]);
    }

    public function isConfigCorrect(Request $request)
    {
        try {
            Stripe::setApiKey($request->secret_key);
            Plan::create([
                'amount'   => 1,
                'currency' => $request->currency,
                'interval' => 'day',
                'product'  => ['name' => 'test'],
            ]);
            Balance::retrieve();
        } catch (\Exception $e) {
            $this->handleException(new PaymentsConfigurationException($e->getMessage()));
        }

        return true;
    }

    public function isSubscriptionActive($subscription)
    {
        try {
            $subscription = Subscription::retrieve($subscription->gateway_id);
        } catch (\Exception $e) {
            return false;
        }

        if (!in_array($subscription->status, [Subscription::STATUS_TRIALING, Subscription::STATUS_ACTIVE])) {
            return false;
        }

        return true;
    }

    public function isSubscriptionRenewed($subscription)
    {
        if (!$subscription->expiration_date) {
            return false;
        }

        return !Invoice::all([
            'subscription' => $subscription->gateway_id,
            'status' => Invoice::STATUS_PAID,
            'created' => ['gte' => strtotime($subscription->expiration_date)],
        ])->isEmpty();
    }

    public function getSubscriptionEnd($subscription)
    {
        try {
            $gatewaySubscription = Subscription::retrieve($subscription->gateway_id);
        } catch (\Exception $e) {
            return null;
        }

        $endTimestamp = $this->getGatewayEndTimestamp($gatewaySubscription);

        if (empty($endTimestamp)) {
            return null;
        }

        return date('Y-m-d H:i:s', $endTimestamp);
    }

    /**
     * Method to process gateway's webhook request
     *
     * @param Request $request
     * @return Response
     */
    public function webhook(Request $request)
    {
        $event = $this->getWebhookEvent($request->header('Stripe-Signature'));

        if (empty($event)) {
            $this->handleException(new PaymentsIssueException('Wrong data'));
        }

        switch ($event->type) {
            case 'invoice.paid':
                $this->webhookRenewSubscription($event->data->object);

                break;
            case 'invoice.payment_failed':
                $subscriptionId = $event->data->object->subscription;

                $subscription = \Tobuli\Entities\Subscription::where('gateway_id', $subscriptionId)->first();

                if (empty($subscription)) {
                    $this->handleException(new PaymentsIssueException('Webhook subscription not found'));
                }

                $subscription->setExpirationDate(Carbon::now());

                break;
            default:

                break;
        }

        return response('', 200);
    }

    /**
     * Get webhook event and validate request
     *
     * @param string $header Stripe-signature header
     * @return \Stripe\Event|null
     */
    private function getWebhookEvent($header)
    {
        try {
            $payload = @file_get_contents('php://input');

            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $header,
                $this->config['webhook_key']
            );

            return $event;
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            $this->handleException(new PaymentsIssueException('Unexpected Value'), $payload);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            $this->handleException(new PaymentsIssueException('Signature Verification'), $payload);
        }

        return null;
    }

    /**
     * Renew subscription
     *
     * @param Invoice $invoice
     * @return void
     */
    private function webhookRenewSubscription($invoice)
    {
        $subscriptionId = $invoice->subscription;

        try {
            $gatewaySubscription = Subscription::retrieve($subscriptionId);
        } catch (\Exception $e) {
            $this->handleException(new PaymentsIssueException('Webhook gateway subscription not found'));
        }

        if (! in_array($gatewaySubscription->status, [Subscription::STATUS_TRIALING, Subscription::STATUS_ACTIVE])) {
            $this->handleException(
                new PaymentsIssueException('Webhook subscription wrong status "'.$gatewaySubscription->status.'"')
            );
        }

        $subscription = \Tobuli\Entities\Subscription::where('gateway_id', $subscriptionId)
            ->first();

        if (empty($subscription)) {
            $this->handleException(new PaymentsIssueException('Webhook subscription not found'));
        }

        $endTimestamp = $this->getGatewayEndTimestamp($gatewaySubscription);

        if (empty($endTimestamp)) {
            $this->handleException(new PaymentsIssueException('Webhook end timestamp empty'));
        }

        $this->renewSubscription($subscription, date('Y-m-d H:i:s', $endTimestamp));
    }

    private function createCustomer($user, $token)
    {
        return Customer::create([
            'email'  => $user->email,
            'source' => $token,
        ]);
    }

    private function existingPlan($plan)
    {
        try {
            return Plan::retrieve($this->planID($plan));
        } catch (\Exception $e) {
            return null;
        }
    }

    private function createPlan($plan)
    {
        return Plan::create([
            'id'       => $this->planID($plan),
            'amount'   => $plan->price * 100,
            'currency' => $this->currency,
            'interval' => substr_replace($plan->duration_type, '', -1),
            'interval_count' => $plan->duration_value,
            'product'  => [
                'name' => $plan->title,
            ],
        ]);
    }

    private function planID($plan)
    {
        $duration = $plan->duration_value;

        return md5("$plan->id:$plan->price:$plan->duration_type:$duration");
    }

    public function cancelSubscription($subscription)
    {
        try {
            Subscription::retrieve($subscription->gateway_id)
                ->cancel();
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    public function getPaymentIntent($order)
    {
        return PaymentIntent::create([
            'amount'        => $order->price * 100,
            'currency'      => $this->currency,
            'payment_method_types' => ['card'],
        ]);
    }

    /**
     * Get gateway subscription's end timestamp
     *
     * @param \Stripe\Subscription $gatewaySubscription
     * @return string|null
     */
    private function getGatewayEndTimestamp($gatewaySubscription)
    {
        if (! in_array($gatewaySubscription->status, [Subscription::STATUS_TRIALING, Subscription::STATUS_ACTIVE])) {
            return null;
        }

        return $gatewaySubscription->status == Subscription::STATUS_TRIALING
            ? $gatewaySubscription->trial_end
            : $gatewaySubscription->current_period_end;
    }
}
