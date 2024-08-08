<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use JTL\Backend\Notification;
use JTL\Backend\NotificationEntry;
use JTL\Checkout\Bestellung;
use JTL\Shop;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;

/**
 * Heidelpay Giropay Payment Method.
 *
 * Giropay is an Internet payment System in Germany, based on online banking.
 * Introduced in February 2006, this payment method allows customers to buy
 * securely on the Internet using direct online transfers from their bank account.
 *
 * Giropay is the official online banking implementation of the German banks.
 *
 * @deprecated
 * @see https://docs.heidelpay.com/docs/giropay-payment
 */
class HeidelpayGiropay extends HeidelpayPaymentMethod implements RedirectPaymentInterface
{
    use HasMetadata;
    use HasCustomer;

    protected function getAllowedCountries(): array
    {
        return ['DE'];
    }

    protected function getAllowedCurrencies(): array
    {
        return ['EUR'];
    }

    /**
     * @inheritDoc
     */
    public function initBackendNotification(): void
    {
        // Add deprecation notice IF paymethod is used (ie assigned to a shipping method)
        $payMethod = $this->plugin->getPaymentMethods()->getMethodByID($this->moduleID);

        if ($payMethod !== null && $payMethod->getActive()) {
            $this->kZahlungsart = $payMethod->getMethodID();
            $result = Shop::Container()->getDB()->select('tversandartzahlungsart', 'kZahlungsart', $this->kZahlungsart);

            if ($result) {
                $notification = new NotificationEntry(
                    NotificationEntry::TYPE_INFO,
                    sprintf(__('hpDeprecationPaymentMethodTitle'), $payMethod->getName()),
                    sprintf(nl2br(__('hpDeprecationGiroPayNotice')), $payMethod->getName())
                );

                $notification->setPluginId((string) $this->plugin->getID());
                Notification::getInstance()->addNotify($notification);
            }
        }
    }

    /**
     * Deactivate as GiroPay has dicontinued its service
     * @param array $args
     * @return bool
     */
    public function isValidIntern($args = []): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     * @return AbstractTransactionType|Charge
     */
    protected function performTransaction(BasePaymentType $payment, Bestellung $order): AbstractTransactionType
    {
        // Create / Update existing customer resource if needed
        $customer = $this->createOrFetchHeidelpayCustomer($this->adapter, $this->sessionHelper, false);

        if ($customer->getId()) {
            $customer = $this->adapter->getCurrentConnection()->updateCustomer($customer);
        }

        $charge = new Charge(
            $this->getTotalPriceCustomerCurrency($order),
            $order->Waehrung->getCode(),
            $this->getReturnURL($order)
        );
        $charge->setOrderId($order->cBestellNr ?? null);

        return $this->adapter->getCurrentConnection()->performCharge(
            $charge,
            $payment->getId(),
            $customer,
            $this->createMetadata()
        );
    }
}
