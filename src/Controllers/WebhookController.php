<?php declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Controllers;

use UnzerSDK\Resources\AbstractUnzerResource;
use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Foundation\EventPayload;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayApiAdapter;
use Plugin\s360_unzer_shop5\src\Webhooks\PaymentEventSubscriber;

/**
 * Handle Heidelpay Webhooks
 *
 * @package Plugin\s360_unzer_shop5\src\Controllers
 */
class WebhookController extends Controller
{
    /**
     * @var HeidelpayApiAdapter
     */
    protected $adapter;

    /**
     * @inheritDoc
     */
    public function __construct(PluginInterface $plugin)
    {
        parent::__construct($plugin);

        $this->adapter = Shop::Container()->get(HeidelpayApiAdapter::class);
    }

    /**
     * Handle Webhook event.
     *
     * Note: Do not use the event name as indicator for the state of a resource, just use it
     * as an indicator to run the correct subscriber/listener.
     *
     * @SuppressWarnings(PHPMD.ExitExpression)
     * @return string
     */
    public function handle(): string
    {
        // Fetch Webhook resource
        $input = file_get_contents('php://input');
        $event = json_decode($input);
        $resource = $this->adapter->getApi()->fetchResourceFromEvent(file_get_contents('php://input'));
        $this->debugLog('Handling event: ' . print_r($input, true), static::class);
        $this->debugLog('Fetched resource from Event: ' . $resource->jsonSerialize(), static::class);

        if (!$resource instanceof AbstractUnzerResource) {
            $this->errorLog('Fetched resource is not a AbstractUnzerResource. Abort!', static::class);
            http_response_code(403);
            exit;
        }

        if (!isset($event->event) or empty($event->event)) {
            $this->errorLog('Request does not contain an event. Abort!', static::class);
            http_response_code(403);
            exit;
        }

        $subscriber = new PaymentEventSubscriber();
        $subscriber->handleEvent(
            new EventPayload(
                $event->event,
                $event->publicKey,
                $event->retrieveUrl,
                $event->paymentId ?? null,
                $resource
            )
        );

        return '';
    }
}
