<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\paymentmethod;

use JTL\Checkout\Bestellung;
use JTL\Smarty\JTLSmarty;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayPaymentMethod;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\CancelableInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepAdditionalInterface;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\HandleStepReviewOrderInterface;
use Plugin\s360_unzer_shop5\src\Payments\Traits\CancelPaymentTransaction;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasAuthorization;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasBasket;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasCustomer;
use Plugin\s360_unzer_shop5\src\Payments\Traits\HasMetadata;
use Plugin\s360_unzer_shop5\src\Payments\Traits\SupportsB2B;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;

class UnzerPaylaterDirectDebit extends HeidelpayPaymentMethod implements
    HandleStepAdditionalInterface,
    HandleStepReviewOrderInterface,
    CancelableInterface
{
    use HasAuthorization;
    use HasMetadata;
    use HasCustomer;
    use HasBasket;
    use SupportsB2B;
    use CancelPaymentTransaction;

    protected function getAllowedCountries(): array
    {
        return ['AT', 'DE'];
    }

    protected function getAllowedCurrencies(): array
    {
        return ['EUR'];
    }

    /**
     * Save the payment reference and some other bank data.
     *
     * @param Bestellung $order
     * @param Authorization $transaction
     * @return array
     */
    public function getOrderAttributes(Bestellung $order, AbstractTransactionType $transaction): array
    {
        return [
            self::ATTR_IBAN                   => $transaction->getIban(),
            self::ATTR_BIC                    => $transaction->getBic(),
            self::ATTR_TRANSACTION_DESCRIPTOR => $transaction->getDescriptor(),
            self::ATTR_ACCOUNT_HOLDER         => $transaction->getHolder()
        ];
    }

    /**
     * Different billing and shipping address is allowed, BUT first and last name should be the same.
     *
     * @return bool
     */
    public function isSelectable(): bool
    {
        // @see: https://unz.atlassian.net/browse/S360-21?focusedCommentId=322832
        // if (isset($_SESSION['Bestellung']->kLieferadresse) && $_SESSION['Bestellung']->kLieferadresse == -1) {
        //     /** @var Lieferadresse $shipping */
        //     $shipping = $this->sessionHelper->getFrontendSession()->get('Lieferadresse');
        //     $billing = $this->sessionHelper->getFrontendSession()->getCustomer();

        //     if ($shipping->cVorname !== $billing->cVorname || $shipping->cNachname !== $billing->cNachname) {
        //         $this->debugLog(
        //             'Hide Unzer Paylater Direct Debit as the names for shipping and billing address are different.',
        //             static::class
        //         );
        //         return false;
        //     }
        // }
        if ($this->isB2BCustomer($this->sessionHelper->getFrontendSession()->getCustomer())) {
            return false;
        }

        return parent::isSelectable();
    }

    /**
     * Add Customer Resource to view.
     *
     * @param JTLSmarty $view
     * @return void
     */
    public function handleStepAdditional(JTLSmarty $view): void
    {
        $this->adapter->getConnectionForSession();
        $shopCustomer = $this->sessionHelper->getFrontendSession()->getCustomer();
        $customer = $this->createOrFetchHeidelpayCustomer(
            $this->adapter,
            $this->sessionHelper,
            $this->isB2BCustomer($shopCustomer)
        );
        $customer->setShippingAddress(
            $this->createHeidelpayAddress(
                $this->sessionHelper->getFrontendSession()->get('Lieferadresse')
            )
        );
        $customer->setBillingAddress(
            $this->createHeidelpayAddress($this->sessionHelper->getFrontendSession()->getCustomer())
        );

        $data = $view->getTemplateVars('hpPayment') ?: [];
        $data['customer'] = $customer;
        $data['isB2B'] = $this->isB2BCustomer($shopCustomer);

        $view->assign('hpPayment', $data);
    }

    /**
     * Generate and add threat metrix id (fraud prevention).
     *
     * @deprecated Only used for backwards compatibility (UI Components v1)
     * @param JTLSmarty $view
     * @return null|string
     */
    public function handleStepReviewOrder(JTLSmarty $view): ?string
    {
        $data = $view->getTemplateVars('hpPayment') ?: [];
        $data['threatMetrixId'] = $this->sessionHelper->generateThreatMetrixId();
        $view->assign('hpPayment', $data);

        return 'template/partials/_threatMetrix';
    }


    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     * @return bool
     */
    public function validateAdditional(): bool
    {
        $postPaymentData = $_POST['paymentData'] ?? [];

        // Save Threat Metrix ID
        if (isset($postPaymentData['threatMetrixId'])) {
            $this->sessionHelper->set(SessionHelper::KEY_THREAT_METRIX_ID, $postPaymentData['threatMetrixId']);
        }

        return parent::validateAdditional();
    }

    protected function performTransaction(BasePaymentType $payment, Bestellung $order): AbstractTransactionType
    {
        // Create or fetch customer resource
        $shopCustomer = $this->sessionHelper->getFrontendSession()->getCustomer();
        $customer = $this->createOrFetchHeidelpayCustomer(
            $this->adapter,
            $this->sessionHelper,
            $this->isB2BCustomer($shopCustomer)
        );
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

        return $this->adapter->getCurrentConnection()->performAuthorization(
            $this->createAuthorization($shopCustomer, $order),
            $payment->getId(),
            $customer,
            $this->createMetadata(),
            $basket
        );
    }
}
