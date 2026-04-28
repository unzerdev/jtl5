<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Payments\Traits;

use DateTime;
use JTL\Checkout\Bestellung;
use JTL\Customer\Customer;
use JTL\Link\LinkInterface;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use UnzerSDK\Resources\EmbeddedResources\RiskData;
use UnzerSDK\Resources\TransactionTypes\Authorization;

trait HasAuthorization
{
    protected function createAuthorization(Customer $customer, Bestellung $order, bool $withRiskData = true, ?string $recurrenceType = null): Authorization
    {
        $specialpages = Shop::Container()->getLinkService()->getSpecialPages();

        $authorization = new Authorization(
            $this->getTotalPriceCustomerCurrency($order),
            $order->Waehrung->getCode(),
            $this->getReturnURL($order)
        );

        $authorization->setTermsAndConditionUrl(
            $specialpages->first(static fn(LinkInterface $link) => $link->getLinkType() === LINKTYP_AGB)?->getUrl()
        );
        $authorization->setPrivacyPolicyUrl(
            $specialpages->first(static fn(LinkInterface $link) => $link->getLinkType() === LINKTYP_DATENSCHUTZ)?->getUrl()
        );
        $authorization->setOrderId($order->cBestellNr ?? null);

        if ($withRiskData) {
            $riskData = (new RiskData())
                ->setThreatMetrixId($this->sessionHelper->get(SessionHelper::KEY_THREAT_METRIX_ID))
                ->setRegistrationLevel($customer->nRegistriert == '1' ? '1' : '0')
                ->setRegistrationDate(
                    DateTime::createFromFormat('Y-m-d', $customer->dErstellt ?? date('Y-m-d'))->format('Ymd')
                );

            $authorization->setRiskData($riskData);
        }

        if ($recurrenceType) {
            $authorization->setRecurrenceType($recurrenceType);
        }

        return $authorization;
    }
}