<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Payments\Traits;

use JTL\Shop;
use UnzerSDK\Constants\CompanyCommercialSectorItems;
use UnzerSDK\Constants\CompanyRegistrationTypes;
use UnzerSDK\Constants\CompanyTypes;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\EmbeddedResources\Address;
use JTL\Checkout\Adresse;
use JTL\Customer\Customer as ShopCustomer;
use JTL\Helpers\Text;
use JTL\Language\LanguageModel;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayApiAdapter;
use Plugin\s360_unzer_shop5\src\Utils\Logger;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Constants\ShippingTypes;
use UnzerSDK\Exceptions\UnzerApiException;

use UnzerSDK\Resources\EmbeddedResources\CompanyInfo;
use UnzerSDK\Resources\EmbeddedResources\CompanyOwner;
use function Functional\first;

/**
 * Payment Methods which require a Customer object.
 *
 * @see https://docs.heidelpay.com/docs/additional-resources#create-customer-resources
 * @package Plugin\s360_unzer_shop5\src\Payments\Traits
 */
trait HasCustomer
{
    /**
     * Create a new customer resource or fetch one if we have a id for it.
     *
     * @param HeidelpayApiAdapter $adapter
     * @param SessionHelper $session
     * @param bool $isB2B
     * @return Customer
     */
    protected function createOrFetchHeidelpayCustomer(
        HeidelpayApiAdapter $adapter,
        SessionHelper $session,
        bool $isB2B
    ): Customer {
        if ($session->has(SessionHelper::KEY_CUSTOMER_ID) && $session->get(SessionHelper::KEY_CUSTOMER_ID) != -1) {
            $frontSession = $session->getFrontendSession();

            // Try to fetch customer
            try {
                $customer = $adapter->getCurrentConnection()->fetchCustomer($session->get(SessionHelper::KEY_CUSTOMER_ID));
            } catch (UnzerApiException $exc) {
                if ($exc->getCode() === ApiResponseCodes::API_ERROR_CUSTOMER_DOES_NOT_EXIST) {
                    Logger::debug($exc->getMessage() . ' - maybe due to key pair change and invalid session');
                }

                // Could not load customer for their saved id -> try to create a new customer
                $session->clear(SessionHelper::KEY_CUSTOMER_ID);
                return $this->createOrFetchHeidelpayCustomer($adapter, $session, $isB2B);
            }

            if (!empty($frontSession->getCustomer()->cFirma) && empty($customer->getCompany())) {
                $customer->setCompany(Text::convertUTF8(html_entity_decode($frontSession->getCustomer()->cFirma)));
            }

            $this->setCustomerLanguage($customer);

            // Update names as they might have changed (but not on B2B so that we do not overwrite the B2B Form changes)
            if (!$isB2B) {
                $customer->setFirstname(Text::convertUTF8(html_entity_decode($frontSession->getCustomer()->cVorname)));
                $customer->setLastname(Text::convertUTF8(html_entity_decode($frontSession->getCustomer()->cNachname)));

                // Remove Company Infomartion as we do not want to be treated as a B2B User
                $customer->setCompany(null);
                // $customer->setCompanyInfo(new CompanyInfo());
            }

            // UOPP-91: Set company info for b2b customers
            if ($isB2B && $customer->getCompanyInfo() === null) {
                $customer->setCompanyInfo(new CompanyInfo());
                $customer->getCompanyInfo()
                    ->setRegistrationType(CompanyRegistrationTypes::REGISTRATION_TYPE_NOT_REGISTERED)
                    ->setFunction('OWNER')
                    ->setCommercialSector(CompanyCommercialSectorItems::OTHER)
                    ->setOwner((new CompanyOwner())->setFirstname($customer->getFirstname())->setLastname($customer->getLastname()))
                    ->setCompanyType(CompanyTypes::OTHER);
            }

            return $customer;
        }

        // Create new customer object but do not save the customer in the api
        // because some mandatory fields (e.g. birthday) may be missing!
        if ($isB2B) {
            return $adapter->getCurrentConnection()->createOrUpdateCustomer(
                $this->createHeidelpayB2BCustomer($session->getFrontendSession()->getCustomer())
            );
        }

        return $adapter->getCurrentConnection()->createOrUpdateCustomer(
            $this->createHeidelpayCustomer($session->getFrontendSession()->getCustomer())
        );
    }

    /**
     * Create a Heidelpay Customer Instance.
     *
     * @param ShopCustomer $customer
     * @return Customer
     */
    protected function createHeidelpayCustomer(ShopCustomer $customer): Customer
    {
        $customerObj = CustomerFactory::createCustomer(
            Text::convertUTF8(html_entity_decode($customer->cVorname)),
            Text::convertUTF8(html_entity_decode($customer->cNachname))
        );

        if (
            !empty($customer->dGeburtstag) &&
            $customer->dGeburtstag !== '0000-00-00' &&
            $customer->dGeburtstag !== '_DBNULL_'
        ) {
            $customerObj->setBirthDate(date('Y-m-d', strtotime($customer->dGeburtstag)));
        }

        $customerObj->setEmail($customer->cMail);

        if (!empty($customer->cFirma)) {
            $customerObj->setCompany(
                html_entity_decode(mb_convert_encoding($customer->cFirma, 'UTF-8', 'ISO-8859-1'), ENT_COMPAT, 'UTF-8')
            );
        }

        // Set user language
        $this->setCustomerLanguage($customerObj);

        // Set external customer so we do not have to map it ourself.
        if (!empty($customer->kKunde)) {
            $customerObj->setCustomerId((string) $customer->kKunde);
        }

        return $customerObj;
    }

    /**
     * Create a Heidelpay Address for Shipping
     *
     * @param \stdClass|Adresse|\JTL\Customer\Customer $address
     * @return Address
     */
    protected function createHeidelpayAddress($address): Address
    {
        $type = isset($_SESSION['Bestellung']) && $_SESSION['Bestellung']->kLieferadresse == -1
            ? ShippingTypes::DIFFERENT_ADDRESS
            : ShippingTypes::EQUALS_BILLING;

        $address = (new Address())
            ->setName(Text::convertUTF8(html_entity_decode($address->cVorname . ' ' . $address->cNachname)))
            ->setStreet(Text::convertUTF8(html_entity_decode($address->cStrasse . ' ' . $address->cHausnummer)))
            ->setZip(Text::convertUTF8(html_entity_decode($address->cPLZ)))
            ->setCity(Text::convertUTF8(html_entity_decode($address->cOrt)))
            ->setCountry(Text::convertUTF8(html_entity_decode($address->cLand)))
            ->setCompany(!empty($address->cFirma) ? Text::convertUTF8(html_entity_decode($address->cFirma)) : null);

        if (! $address instanceof \JTL\Customer\Customer) {
            $address->setShippingType($type);
        }

        return $address;
    }

    /**
     * Create a Heidelpay B2B Customer (registered or non-registered) instance.
     *
     * @param ShopCustomer $customer
     * @return Customer
     */
    protected function createHeidelpayB2BCustomer(ShopCustomer $customer): Customer
    {
        // UOPP-91
        $companyOwner = (new CompanyOwner())
            ->setFirstname(Text::convertUTF8(html_entity_decode($customer->cVorname)))
            ->setLastname(Text::convertUTF8(html_entity_decode($customer->cNachname)));

        $address  = (new Address())
            ->setName(Text::convertUTF8(html_entity_decode($customer->cVorname . ' ' . $customer->cNachname)))
            ->setStreet(Text::convertUTF8(html_entity_decode($customer->cStrasse . ' ' . $customer->cHausnummer)))
            ->setZip(Text::convertUTF8(html_entity_decode($customer->cPLZ)))
            ->setCity(Text::convertUTF8(html_entity_decode($customer->cOrt)))
            ->setCountry(Text::convertUTF8(html_entity_decode($customer->cLand)))
            ->setCompany(!empty($customer->cFirma) ? Text::convertUTF8(html_entity_decode($customer->cFirma)) : null);

        // Registered = registered in the commercial register with a commercial register number
        if ($customer->cUSTID) {
            $obj = CustomerFactory::createRegisteredB2bCustomer(
                $address,
                Text::convertUTF8(html_entity_decode($customer->cUSTID)),
                Text::convertUTF8(html_entity_decode($customer->cFirma))
            );

            $obj->setFirstname(Text::convertUTF8(html_entity_decode($customer->cVorname)));
            $obj->setLastname(Text::convertUTF8(html_entity_decode($customer->cNachname)));
            $obj->setEmail($customer->cMail);
            $obj->setCustomerId((string) $customer->kKunde);
            $obj->getCompanyInfo()?->setOwner($companyOwner);
            $obj->getCompanyInfo()?->setCompanyType(CompanyTypes::OTHER);

            if (!empty($customer->cAnrede)) {
                $obj->setSalutation($customer->cAnrede == 'm' ? 'mr' : ($customer->cAnrede == 'w' ? 'mrs' : null));
            }

            $this->setCustomerLanguage($obj);

            return $obj;
        }

        if (empty($customer->dGeburtstag) || $customer->dGeburtstag === '0000-00-00' || $customer->dGeburtstag === '_DBNULL_') {
            $birthday = '';
        } else {
            $birthday = date('Y-m-d', strtotime($customer->dGeburtstag));
        }

        $obj = CustomerFactory::createNotRegisteredB2bCustomer(
            Text::convertUTF8(html_entity_decode($customer->cVorname)),
            Text::convertUTF8(html_entity_decode($customer->cNachname)),
            $birthday,
            $address,
            Text::convertUTF8(html_entity_decode($customer->cMail)),
            Text::convertUTF8(html_entity_decode($customer->cFirma))
        );
        $obj->setCustomerId((string) $customer->kKunde);
        $obj->getCompanyInfo()?->setOwner($companyOwner);
        $obj->getCompanyInfo()?->setCompanyType(CompanyTypes::OTHER);
        $this->setCustomerLanguage($obj);

        if (!empty($customer->cAnrede)) {
            $obj->setSalutation($customer->cAnrede == 'm' ? 'mr' : ($customer->cAnrede == 'w' ? 'mrs' : null));
        }

        return $obj;
    }

    /**
     * @param Address $address
     * @return array{firstname: string, lastname: string}
     */
    protected function getNamesFromAddress(Address $address): array
    {
        $names = mb_split('\s+', $address->getName() ?? '', 2);
        if (!empty($names) && \count($names) >= 1) {
            return [
                'firstname' => current($names) ?? '',
                'lastname' => end($names) ?? '',
            ];
        }

        return ['firstname' => '', 'lastname' => $address->getName() ?? ''];
    }

    /**
     * @param Address $address
     * @return array{number: string, street: string}
     */
    protected function getStreetFromAddress(Address $address): array
    {
        $data = ['number' => '', 'street' => ''];
        $split = mb_split(' ', $address->getStreet() ?? '');

        if (\count($split) > 1) {
            $data['number'] = $split[count($split) - 1];
            unset($split[count($split) - 1]);
            $data['street'] = implode(' ', $split);
        } else {
            $sStreet = implode(' ', $split);
            if (mb_strlen($sStreet) > 1) {
                $data['number'] = mb_substr($sStreet, -1);
                $data['street'] = mb_substr($sStreet, 0, -1);
            }
        }

        return $data;
    }

    private function setCustomerLanguage(Customer $customer): void
    {
        $language = first(
            $this->sessionHelper->getFrontendSession()->getLanguages(),
            fn (LanguageModel $lang) => $lang->id === ($this->sessionHelper->getFrontendSession()->getCustomer()->kSprache ?? Shop::getLanguageID())
        )?->getIso639();

        $customer->setLanguage(strtolower($language ?? 'en'));
    }
}
