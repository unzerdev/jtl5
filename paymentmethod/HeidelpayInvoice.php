<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use JTL\Backend\Notification;
use JTL\Backend\NotificationEntry;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use JTL\Checkout\Bestellung;
use JTL\Checkout\ZahlungsInfo;
use JTL\Helpers\Text;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;

/**
 * With invoice payments an invoice is sent to the customer - the customer pays upon receipt of the invoice and
 * after the process is finished you (the merchant) receive your money.
 *
 * The Invoice payment method supports you in sending an invoice and the subsequent processes up to payment
 * of the invoice by the customer. As soon as the customer has made the payment via online banking
 * or manual bank transfer, you will receive a notification of the successful payment.
 *
 * @see https://docs.heidelpay.com/docs/invoice-payment
 * @deprecated
 */
class HeidelpayInvoice extends HeidelpayPaymentMethod
{
    use HasMetadata;
    use HasCustomer;

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
                    sprintf(nl2br(__('hpDeprecationInvoiceNotice')), $payMethod->getName())
                );

                $notification->setPluginId((string) $this->plugin->getID());
                Notification::getInstance()->addNotify($notification);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function isSelectable(): bool
    {
        // Add deprecation notice log IF payment method is selectable (ie being actively used)
        $isSelectable = parent::isSelectable();
        $payMethod = $this->plugin->getPaymentMethods()->getMethodByID($this->moduleID);

        if ($isSelectable && $payMethod !== null && $payMethod->getActive()) {
            $this->noticeLog(
                sprintf(nl2br(__('hpDeprecationInvoiceNotice')), $payMethod->getName())
            );
        }

        return $isSelectable;
    }

    /**
     * Data the merchant needs to put on the Invoice.
     *
     * The information iban, bic, descriptor and holder data must be be stated on the invoice
     * so that the customer can make the bank transfer.
     *
     * The customer should be informed that he should use the descriptor during online banking transfer.
     * This is the identifier that links the payment to the customer.
     *
     * We also save this data as payment info (tzahlungsinfo) to it is easily accessible.
     *
     * @param Bestellung $order
     * @param Charge $transaction
     * @return array
     */
    public function getOrderAttributes(Bestellung $order, AbstractTransactionType $transaction): array
    {
        // save payment information
        $oPaymentInfo = new ZahlungsInfo(0, $order->kBestellung);
        $oPaymentInfo->kKunde            = $order->kKunde;
        $oPaymentInfo->kBestellung       = $order->kBestellung;
        $oPaymentInfo->cInhaber          = Text::convertUTF8($transaction->getHolder() ?? '');
        $oPaymentInfo->cIBAN             = Text::convertUTF8($transaction->getIban() ?? '');
        $oPaymentInfo->cBIC              = Text::convertUTF8($transaction->getBic() ?? '');
        $oPaymentInfo->cKontoNr          = $oPaymentInfo->cIBAN;
        $oPaymentInfo->cBLZ              = $oPaymentInfo->cBIC;
        $oPaymentInfo->cVerwendungszweck = Text::convertUTF8($transaction->getDescriptor() ?? '');
        $oPaymentInfo->cBankName         = '';
        $oPaymentInfo->cKartenNr         = '';
        $oPaymentInfo->cCVV              = '';

        isset($oPaymentInfo->kZahlungsInfo) ? $oPaymentInfo->updateInDB() : $oPaymentInfo->insertInDB();

        return [
            self::ATTR_IBAN                   => $oPaymentInfo->cIBAN,
            self::ATTR_BIC                    => $oPaymentInfo->cBIC,
            self::ATTR_TRANSACTION_DESCRIPTOR => $oPaymentInfo->cVerwendungszweck,
            self::ATTR_ACCOUNT_HOLDER         => $oPaymentInfo->cInhaber,
        ];
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
