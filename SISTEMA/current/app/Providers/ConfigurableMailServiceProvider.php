<?php namespace App\Providers;

use Illuminate\Mail\MailServiceProvider;
use App\Services\Mail\TransportManager;
use Settings;

class ConfigurableMailServiceProvider extends MailServiceProvider {

    /**
     * Register the Swift Transport instance.
     *
     * @return void
     */
    protected function registerSwiftTransport()
    {
        $this->loadConfig();

        $this->app->singleton('swift.transport', function ($app) {
            return new TransportManager($app);
        });
    }

    protected function loadConfig()
    {
        try {
            $settings = settings('email');
        } catch (\Exception $e) {
            $settings = [];
        }

        $config = [
            'driver' => array_get($settings, 'provider', 'mail'),
            'host'   => array_get($settings, 'smtp_server_host', ''),
            'port'   => array_get($settings, 'smtp_server_port', ''),
            'from'   => [
                'address' => array_get($settings, 'noreply_email', config('mail.from.address')),
                'name'    => array_get($settings, 'from_name', config('mail.from.name')),
            ],
            'encryption' => array_get($settings, 'smtp_security', ''),
            'auth'       => array_get($settings, 'smtp_authentication', 1),
            'username'   => array_get($settings, 'smtp_username', ''),
            'password'   => array_get($settings, 'smtp_password', ''),
        ];

        if ( $config['driver'] == 'smtp' && empty($settings['use_smtp_server']) )
            $config['driver'] = 'mail';

        switch ($config['driver']) {
            case 'smtp':
                if ( ! $config['auth']) {
                    unset($config['username'], $config['password']);
                }
                break;
            case 'sendgrid':
                $this->app['config']->set('services.sendgrid', [
                    'secret' => array_get($settings, 'api_key', '')
                ]);
                break;
            case 'postmark':
                $this->app['config']->set('services.postmark', [
                    'secret' => array_get($settings, 'api_key', '')
                ]);
                break;
            case 'mailgun':
                switch (array_get($settings, 'region', null)) {
                    case 'eu':
                        $endpoint = 'api.eu.mailgun.net';
                        break;
                    default:
                        $endpoint = null;
                }

                $this->app['config']->set('services.mailgun', [
                    'secret' => array_get($settings, 'api_key', ''),
                    'domain' => array_get($settings, 'domain', ''),
                    'endpoint' => $endpoint,
                ]);
                break;
        }

        $this->app['config']->set('mail', $config);
    }
}