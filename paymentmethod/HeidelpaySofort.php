<?php
declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Charge;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\RedirectPaymentInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;

/**
 * Heidelpay Sofort Payment Method.
 *
 * SOFORT is an online direct payment method, which works on the basis of online banking.
 * It is the predominant online banking method in the German-speaking countries in Europe, and in Belgium.
 *
 * To pay with SOFORT, the customer is redirected to the SOFORT web page. The customer will login
 * by using his bank details for authentication. After successful login, SOFORT initiates a payment transaction
 * with the customer's bank account.
 *
 * SOFORT allows merchants to serve customers without credit cards,
 * and those who prefer online payment methods over invoices.
 *
 * @see https://docs.heidelpay.com/docs/sofort-payment
 */
class HeidelpaySofort extends HeidelpayPaymentMethod implements RedirectPaymentInterface
{
    use HasMetadata;
    use HasCustomer;

    /**
     * @inheritDoc
     * @return AbstractTransactionType|Charge
     */
    protected function performTransaction(BasePaymentType $payment, $order): AbstractTransactionType
    {
        // Create / Update existing customer resource if needed
        $customer = $this->createOrFetchHeidelpayCustomer($this->adapter, $this->sessionHelper, false);

        if ($customer->getId()) {
            $customer = $this->adapter->getApi()->updateCustomer($customer);
        }

        return $this->adapter->getApi()->charge(
            $this->getTotalPriceCustomerCurrency($order),
            $order->Waehrung->cISO,
            $payment->getId(),
            $this->getReturnURL($order),
            $customer,
            $order->cBestellNr ?? null,
            $this->createMetadata()
        );
    }
}
