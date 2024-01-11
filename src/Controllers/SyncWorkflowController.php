<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Controllers;

use Exception;
use JTL\Checkout\Bestellung;
use UnzerSDK\Exceptions\UnzerApiException;
use JTL\Helpers\Request;
use JTL\Helpers\Text;
use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Orders\OrderMappingModel;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayApiAdapter;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use RuntimeException;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;

/**
 * Sync Workflow Controller
 * @package Plugin\s360_unzer_shop5\src\Controllers
 */
class SyncWorkflowController extends Controller
{
    /**
     * @var OrderMappingModel
     */
    private $model;

    /**
     * @var HeidelpayApiAdapter
     */
    private $adapter;

    /**
     * @inheritDoc
     */
    public function __construct(PluginInterface $plugin)
    {
        parent::__construct($plugin);

        $this->adapter = Shop::Container()->get(HeidelpayApiAdapter::class);
        $this->model = new OrderMappingModel(Shop::Container()->getDB());
    }

    /**
     * Save the invoice id for this order if it is a heidelpay order
     *
     * @SuppressWarnings(PHPMD.ExitExpression)
     * @SuppressWarnings(PHPMD.Superglobals)
     * @return string not used
     */
    public function handle(): string
    {
        // Validate Request.
        $this->debugLog('Called SyncWorkflowController with the following data: ' . print_r($_POST, true));
        $attrs = [];
        parse_str(str_replace('|', '&', Request::postVar('attrs', '')), $attrs);

        if (
            empty($attrs) || empty($attrs[HeidelpayPaymentMethod::ATTR_PAYMENT_ID])
            || !Request::hasGPCData('invoice_id')
        ) {
            $this->errorLog('Missing parameter payment_id or invoice_id' . print_r($attrs, true), static::class);
            http_response_code(403);
            exit;
        }

        // Get mapped order. Skip non heidelpay orders.
        $order = $this->model->findByPayment($attrs[HeidelpayPaymentMethod::ATTR_PAYMENT_ID]);
        if (empty($order)) {
            $this->noticeLog(
                'Could not find a mapped order for order id ' . $attrs[HeidelpayPaymentMethod::ATTR_PAYMENT_ID] . '
                 (Possible Reason: Used non-Heidelpay Payment Method)',
                static::class
            );
            exit;
        }

        // Save Invoice ID in order mapping so that we can use it in the shipment call
        $order->setInvoiceId((string) Request::postVar('invoice_id'));
        $saved = $this->model->save($order);
        if ($saved < 0) {
            $this->errorLog(
                'Could not save invoice id ' . Request::postVar('invoice_id') . ' for order ' . $order->getId(),
                static::class
            );
            exit;
        }

        // Update Payment Resource (needed for HDD, as it needs invoiceDate etc)
        $this->updatePaymentType($order->getPaymentTypeId(), new Bestellung($order->getId(), true));
        $this->debugLog('Number of affected rows: ' . $saved, static::class);
        $this->debugLog(
            'Saved invoice id ' . Request::postVar('invoice_id') . ' for order ' . json_encode($order->jsonSerialize()),
            static::class
        );
        return '';
    }

    /**
     * Update payment type if necessary
     */
    private function updatePaymentType(string $paymentTypeId, Bestellung $order): void
    {
        try {
            $api = $this->adapter->getConnectionForOrder($order);
            $paymentType = $this->adapter->fetchPaymentType($paymentTypeId);

            if ($paymentType instanceof InstallmentSecured) {
                $paymentType->setInvoiceDate(date('Y-m-d'));
                $paymentType->setInvoiceDueDate(date('Y-m-d'));
                $api->updatePaymentType($paymentType);
                $this->debugLog('Updated payment type: ' . json_encode($paymentType->jsonSerialize()));
            }
        } catch (UnzerApiException $exc) {
            $msg = $exc->getMerchantMessage() . ' | Id: ' . $exc->getErrorId() . ' | Code: ' . $exc->getCode();
            $this->errorLog(Text::convertISO($msg), static::class);
        } catch (RuntimeException $exc) {
            $this->errorLog(
                'An exception was thrown while using the Heidelpay SDK: ' . Text::convertISO($exc->getMessage()),
                static::class
            );
        } catch (Exception $exc) {
            $this->errorLog('An error occured in the payment process: ' . $exc->getMessage(), static::class);
        }
    }
}
