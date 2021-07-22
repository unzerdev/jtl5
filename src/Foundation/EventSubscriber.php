<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Foundation;

use InvalidArgumentException;

/**
 * Heidelpay Webhook Event Subscriber
 * @package Plugin\s360_unzer_shop5\src\Foundation
 */
abstract class EventSubscriber
{
    /**
     * Get the list of subscribed events and their callbacks.
     *
     * @return array
     */
    abstract public static function getSubscribedEvents(): array;

    /**
     * Handle webhook event.
     *
     * Calls the registered callback for the webhook event if it exists
     *
     * Note: Do not use the event name as indicator for the state of a resource, just use it
     * as an indicator to run the correct subscriber/listener.
     *
     * @throws InvalidArgumentException if no event listener is registered for the webhook event.
     * @throws InvalidArgumentException if the registered event listener does no exist.
     * @param string $event
     * @param EventPayload $payload
     * @return void
     */
    public function handleEvent(EventPayload $payload): void
    {
        $events = static::getSubscribedEvents();
        $event = $payload->getEvent();

        if (!array_key_exists($event, $events)) {
            throw new InvalidArgumentException('There is no event listener subscribed for event: ' . $event);
        }

        if (!method_exists($this, $events[$event])) {
            throw new InvalidArgumentException(
                'The registered event listener ' . $events[$event] . ' for the event ' . $event . ' does not exist!'
            );
        }

        call_user_func([$this, $events[$event]], $payload);
    }
}
