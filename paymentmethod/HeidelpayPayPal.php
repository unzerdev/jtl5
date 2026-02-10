<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use JTL\Checkout\Bestellung;
use JTL\Shop;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\CancelableInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\CancelPaymentTransaction;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasAuthorization;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasBasket;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasDirectCharge;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Utils\Config;

/**
 * Heidelpay Paypal Payment Method.
 *
 * PayPal Holdings, Inc. is an American company operating a worldwide online payments system
 * that supports online money transfers and serves as an electronic alternative to
 * traditional paper methods like cheques and money orders.
 *
 * The customer has to sign up for a PayPal account.
 * Afterwards there is no need to enter the payment details again during the payment process.
 *
 * The Plugin does not support Paypal Express!
 *
 * @see https://docs.heidelpay.com/docs/paypal-payment
 */
class HeidelpayPayPal extends HeidelpayPaymentMethod implements RedirectPaymentInterface, CancelableInterface
{
    use CancelPaymentTransaction;
    use HasBasket;
    use HasCustomer;
    use HasAuthorization;
    use HasDirectCharge;
    use HasMetadata;

    /**
     * Although Paypal support both auth as well as charge calls, we only support Direct Charge.
     *
     * We assign a customer with a shipping address to the charge to allow for "PayPal buyer protection"
     *
     * @inheritDoc
     * @return AbstractTransactionType|Charge
     */
    protected function performTransaction(BasePaymentType $payment, Bestellung $order): AbstractTransactionType
    {
        /** @var Config $config */
        $config = Shop::Container()->get(Config::class);

        // Create a customer with shipping address for Paypal's Buyer Protection
        $shopCustomer = $this->sessionHelper->getFrontendSession()->getCustomer();
        $customer = $this->createOrFetchHeidelpayCustomer($this->adapter, $this->sessionHelper, false);
        $customer->setShippingAddress($this->createHeidelpayAddress($order->Lieferadresse));
        $customer->setBillingAddress($this->createHeidelpayAddress($order->oRechnungsadresse));

        $this->debugLog('Customer Resource: ' . $customer->jsonSerialize(), static::class);

        // Update existing customer resource if needed
        if ($customer->getId()) {
            $customer = $this->adapter->getCurrentConnection()->updateCustomer($customer);
            $this->debugLog('Updated Customer Resource: ' . $customer->jsonSerialize(), static::class);
        }

        // Create Basket
        $session = $this->sessionHelper->getFrontendSession();
        $basket = $this->createHeidelpayBasket(
            $session->getCart(),
            $order->Waehrung,
            $session->getLanguage(),
            $order->cBestellNr ?? $payment->getId()
        );
        $this->debugLog('Basket Resource: ' . $basket->jsonSerialize(), static::class);

        // Authorize payment
        if ($config->getPaymentSetting(Config::PAYMENT_BOOKING_MODE, $this->moduleID) === 'authorize') {
            return $this->adapter->getCurrentConnection()->performAuthorization(
                $this->createAuthorization($shopCustomer, $order, false),
                $payment->getId(),
                $customer,
                $this->createMetadata(),
                $basket
            );
        }

        return $this->adapter->getCurrentConnection()->performCharge(
            $this->createCharge($order),
            $payment->getId(),
            $customer,
            $this->createMetadata()
        );
    }
}
