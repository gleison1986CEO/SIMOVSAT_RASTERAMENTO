<?php

namespace Tobuli\Helpers\Alerts;

use Illuminate\Support\Arr;
use Tobuli\Entities\User;
use Tobuli\Helpers\Alerts\Filter\EnabledFilter;
use Tobuli\Helpers\Alerts\Filter\FilterInterface;
use Tobuli\Helpers\Alerts\Notification\AbstractNotification;
use Tobuli\Helpers\Alerts\Notification\ColorNotification;
use Tobuli\Helpers\Alerts\Notification\CommandNotification;
use Tobuli\Helpers\Alerts\Notification\EmailNotification;
use Tobuli\Helpers\Alerts\Notification\Input\InputAwareInterface;
use Tobuli\Helpers\Alerts\Notification\PopupNotification;
use Tobuli\Helpers\Alerts\Notification\PushNotification;
use Tobuli\Helpers\Alerts\Notification\SharingEmailNotification;
use Tobuli\Helpers\Alerts\Notification\SharingSmsNotification;
use Tobuli\Helpers\Alerts\Notification\SilentNotification;
use Tobuli\Helpers\Alerts\Notification\SmsNotification;
use Tobuli\Helpers\Alerts\Notification\SoundNotification;
use Tobuli\Helpers\Alerts\Notification\WebhookNotification;

class NotificationProvider
{
    private const LIST = [
        ColorNotification::class,
        CommandNotification::class,
        SilentNotification::class,
        SoundNotification::class,
        PopupNotification::class,
        PushNotification::class,
        EmailNotification::class,
        SmsNotification::class,
        WebhookNotification::class,
        SharingEmailNotification::class,
        SharingSmsNotification::class,
    ];

    private ?User $user;

    /**
     * @var AbstractNotification[]
     */
    private array $cache = [];

    /**
     * @var FilterInterface[]
     */
    private array $filters = [];

    public function __construct(?User $user = null, array $filters = [EnabledFilter::class])
    {
        $this->user = $user;

        $this->setFilters($filters);
    }

    /**
     * @return AbstractNotification[]
     */
    public function all(): array
    {
        foreach (self::LIST as $class) {
            $this->load($class);
        }

        return $this->filter($this->cache);
    }

    /**
     * @return AbstractNotification[]
     */
    public function except(array $keys): array
    {
        return array_filter($this->all(), fn ($item) => !in_array($item::getKey(), $keys));
    }

    /**
     * @return AbstractNotification[]
     */
    public function get(array $keys): array
    {
        foreach ($keys as $key) {
            foreach (self::LIST as $class) {
                if ($class::getKey() === $key) {
                    $this->load($class);

                    continue 2;
                }
            }

            throw new \InvalidArgumentException("`$key` does not match any notification");
        }

        return $this->filter(Arr::only($this->cache, $keys));
    }

    /**
     * @param  AbstractNotification[] $notifications
     * @return AbstractNotification[]
     */
    private function filter(array $notifications): array
    {
        $list = [];

        foreach ($notifications as $notification) {
            foreach ($this->filters as $filter) {
                if (!$filter->passes($notification, $this->user)) {
                    continue 2;
                }
            }

            $list[] = $notification;
        }

        return $list;
    }

    public function find(string $key): ?AbstractNotification
    {
        return Arr::first($this->get([$key]));
    }

    private function load(string $class): void
    {
        $key = $class::getKey();

        if (!isset($this->cache[$key])) {
            $this->cache[$key] = new $class();
        }
    }

    public function getInputMeta(array $alertData): array
    {
        /** @var InputAwareInterface[] $notifications */
        $notifications = array_filter($this->all(), fn ($item) => $item instanceof InputAwareInterface);

        return array_map(fn ($item) => $item->getInput($alertData)->toArray(), $notifications);
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function clearFilters(): self
    {
        return $this->setFilters([]);
    }

    public function setFilters(array $filters): self
    {
        $this->filters = [];

        foreach ($filters as $filter) {
            if (!is_a($filter, FilterInterface::class, true)) {
                throw new \InvalidArgumentException('Must be subclass or instance of ' . FilterInterface::class);
            }

            if (is_string($filter)) {
                $filter = new $filter();
            }

            $this->filters[] = $filter;
        }

        return $this;
    }
}