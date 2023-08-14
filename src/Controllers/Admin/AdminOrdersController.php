<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Controllers\Admin;

use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Shipment;
use InvalidArgumentException;
use JTL\Checkout\Bestellung;
use JTL\Helpers\Request;
use JTL\Helpers\Text;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Controllers\AjaxResponse;
use Plugin\s360_unzer_shop5\src\Controllers\HasAjaxResponse;
use Plugin\s360_unzer_shop5\src\Orders\OrderMappingEntity;
use Plugin\s360_unzer_shop5\src\Orders\OrderMappingModel;
use Plugin\s360_unzer_shop5\src\Orders\OrderViewStruct;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayApiAdapter;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use RuntimeException;

/**
 * Admin Orders Controller
 *
 * @package Plugin\s360_unzer_shop5\src\Controllers\Admin
 */
class AdminOrdersController extends AdminController implements AjaxResponse
{
    use HasAjaxResponse;

    public const TEMPLATE_ID_ORDERS = 'template/orders';
    public const TEMPLATE_ID_ORDER_DETAIL = 'template/partials/_order_detail';
    public const TEMPLATE_ID_ORDER_ITEM = 'template/partials/_order_item';

    /**
     * @var OrderMappingModel
     */
    private $model;

    /**
     * @var HeidelpayApiAdapter
     */
    private $adapter;

    /**
     * Init dependencies
     *
     * @return void
     */
    protected function prepare(): void
    {
        parent::prepare();

        // Abort if there are no API keys set yet (probably new install).
        if (
            empty($this->config->get(Config::PRIVATE_KEY))
            || empty($this->config->get(Config::PUBLIC_KEY))
        ) {
            $this->debugLog('Abort Controller. No API Keys set yet.', static::class);
            return;
        }

        $this->adapter = Shop::Container()->get(HeidelpayApiAdapter::class);
    }

    /**
     * DI of model.
     *
     * @param OrderMappingModel $model
     * @return void
     */
    public function setModel(OrderMappingModel $model): void
    {
        $this->model = $model;
    }

    /**
     * Handle Orders Action.
     *
     * @return string
     */
    public function handle(): string
    {
        return $this->view(self::TEMPLATE_ID_ORDERS);
    }

    /**
     * Handle ajax request and send json response.
     *
     * Basic Structure of the JSON:
     * {
     *    status: 'success'|'fail'|'error'|'unknown',
     *    messages: [...],
     *    data: [...]
     * }
     *
     * @throws InvalidArgumentException if the provided action is not recognized.
     * @throws RuntimeException if the json encoding encounters an error.
     * @return void
     */
    public function handleAjax(): void
    {
        switch (Request::getVar('action')) {
            case 'loadOrders':
                $this->handleLoadOrders();
                break;

            case 'getOrderDetails':
                $this->handleGetOrderDetails();
                break;

            default:
                throw new InvalidArgumentException(
                    'Invalid action "' . Text::filterXSS(Request::getVar('action')) . '"'
                );
        }
    }

    /**
     * Handle the order detail view action
     *
     * @throws UnzerApiException if there is an error returned on API-request.
     * @throws RuntimeException if there is an error while using the SDK.
     * @throws RuntimeException if the json is invalid.
     * @return void
     */
    private function handleGetOrderDetails(): void
    {
        // Return error if order id does not exist
        $orderId = Request::postInt('orderId', null);
        if (empty($orderId)) {
            $this->jsonResponse(['status' => self::RESULT_ERROR, 'messages' => ['Missing parameter orderId']]);
        }

        // Get Heidelpay Payment and update local details
        $orderMapping = $this->model->find((int) $orderId);

        if (empty($orderMapping)) {
            $this->jsonResponse([
                'status' => self::RESULT_ERROR,
                'messages' => ['Cannot find an order for id: ' . $orderId]
            ]);
        }

        $payment = $this->adapter->fetchPayment($orderMapping->getPaymentId());
        $order = new Bestellung($orderMapping->getId(), true);

        // Load Charges, Cancellations and Shipments
        foreach ($payment->getCharges() as $chg) {
            /** @var Charge $chg */
            $payment->getCharge($chg->getId());

            foreach ($chg->getCancellations() as $cancel) {
                /** @var Cancellation $cancel */
                try {
                    $this->adapter->getApi()->fetchRefundById($payment, $chg->getId(), $cancel->getId());
                } catch (UnzerApiException $exc) {
                    $this->errorLog(
                        'Error while loading cancellation: ' . $exc->getMerchantMessage()
                        . ' | Error-Code: ' . $exc->getCode(),
                        static::class
                    );
                }
            }
        }

        foreach ($payment->getShipments() as $shipment) {
            /** @var Shipment $shipment */
            $payment->getShipment($shipment->getId());
        }

        // Update order mapping
        $orderMapping->setOrder($order);
        $orderMapping->setPaymentState($payment->getStateName());
        // $orderMapping->setInvoiceId($payment->getInvoiceId());
        $this->model->save($orderMapping);

        // Load View
        $url = $this->config->getInsightPortalUrl($orderMapping);
        $orderMapping->setOrder(null);

        $this->jsonResponse([
            'status' => self::RESULT_SUCCESS,
            'data'   => new OrderViewStruct(
                $orderMapping,
                $this->view(self::TEMPLATE_ID_ORDER_DETAIL, [
                    'hpOrderMapping' => $orderMapping,
                    'hpOrder'     => $order,
                    'hpPayment'   => $payment,
                    'hpPortalUrl' => $url
                ])
            )
        ]);
    }

    /**
     * Handle the loading of orders.
     *
     * @throws RuntimeException if the json is invalid.
     * @return void
     */
    private function handleLoadOrders(): void
    {
        $offset = Request::postInt('offset');
        $limit = Request::postInt('limit', 100);
        $search = Request::postVar('search');
        $orders = $this->model->loadOrders((int)$limit, (int)$offset, $search);
        $data = [];

        foreach ($orders as $order) {
            /** @var OrderMappingEntity $order */
            $url = $this->config->getInsightPortalUrl($order);
            $data[] = new OrderViewStruct(
                $order,
                $this->view(self::TEMPLATE_ID_ORDER_ITEM, [
                    'hpOrder'     => $order,
                    'hpPortalUrl' => $url
                ])
            );
        }

        $this->jsonResponse([
            'status' => self::RESULT_SUCCESS,
            'data'   => $data
        ]);
    }
}
