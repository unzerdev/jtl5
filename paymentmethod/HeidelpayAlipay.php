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
 * Heidelpay Alipay Payment Method.
 *
 * Alipay is China's leading third-party mobile and online payment solution established by Alibaba.
 * It is providing an easy, safe and secure way for millions of individuals and businesses to make
 * and receive payments on the Internet.
 *
 * Alipay also provides escrow payment service that reduces transaction risk for online consumers;
 * shoppers have the ability to verify whether they are happy with goods they have purchased
 * before releasing funds to the seller.
 *
 * @see https://docs.heidelpay.com/docs/alipay
 */
class HeidelpayAlipay extends HeidelpayPaymentMethod implements RedirectPaymentInterface
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
