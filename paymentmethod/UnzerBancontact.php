<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepAdditionalInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Utils\Config;

/**
 * Bancontact
 *
 * @package Plugin\s360_unzer_shop5\paymentmethod
 */
class UnzerBancontact extends HeidelpayPaymentMethod implements RedirectPaymentInterface, HandleStepAdditionalInterface
{
    use HasCustomer;
    use HasMetadata;

    /**
     * Pass Styling Options to template
     *
     * @param JTLSmarty $view
     * @return void
     */
    public function handleStepAdditional(JTLSmarty $view): void
    {
        /** @var Config $config */
        $config = Shop::Container()->get(Config::class);
        $data = $view->getTemplateVars('hpPayment') ?: [];

        $data['styling'] = [
            Config::FONT_COLOR  => $config->get(Config::FONT_COLOR),
            Config::FONT_FAMILY => $config->get(Config::FONT_FAMILY),
            Config::FONT_SIZE   => $config->get(Config::FONT_SIZE),
        ];

        $view->assign('hpPayment', $data);
    }

    /**
     * @inheritDoc
     * @return AbstractTransactionType|Charge
     */
    protected function performTransaction(BasePaymentType $payment, $order): AbstractTransactionType
    {
        // Create a customer with shipping address for Paypal's Buyer Protection
        $customer = $this->createOrFetchHeidelpayCustomer($this->adapter, $this->sessionHelper, false);
        $customer->setShippingAddress($this->createHeidelpayAddress($order->Lieferadresse));
        $customer->setBillingAddress($this->createHeidelpayAddress($order->oRechnungsadresse));

        // Update existing customer resource if needed
        if ($customer->getId()) {
            $customer = $this->adapter->getApi()->updateCustomer($customer);
        }

        $charge = new Charge(
            $this->getTotalPriceCustomerCurrency($order),
            $order->Waehrung->getCode(),
            $this->getReturnURL($order)
        );
        $charge->setOrderId($order->cBestellNr ?? null);

        return $this->adapter->getApi()->performCharge(
            $charge,
            $payment->getId(),
            $customer,
            $this->createMetadata()
        );
    }
}
