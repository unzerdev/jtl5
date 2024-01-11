<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Payments\Traits;

use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\EmbeddedResources\Address;
use JTL\Checkout\Adresse;
use JTL\Customer\Customer as ShopCustomer;
use JTL\Helpers\Text;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayApiAdapter;
use Plugin\s360_unzer_shop5\src\Utils\Logger;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Constants\ShippingTypes;
use UnzerSDK\Exceptions\UnzerApiException;

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

            // Update names as they might have changed (but not on B2B so that we do not overwrite the B2B Form changes)
            if (!$isB2B) {
                $customer->setFirstname(Text::convertUTF8(html_entity_decode($frontSession->getCustomer()->cVorname)));
                $customer->setLastname(Text::convertUTF8(html_entity_decode($frontSession->getCustomer()->cNachname)));

                if ($customer->getShippingAddress()->getShippingType() === ShippingTypes::DIFFERENT_ADDRESS) {
                    $customer->setFirstname(
                        Text::convertUTF8(html_entity_decode($frontSession->getDeliveryAddress()->cVorname))
                    );
                    $customer->setLastname(
                        Text::convertUTF8(html_entity_decode($frontSession->getDeliveryAddress()->cNachname))
                    );
                }

                // Remove Company Infomartion as we do not want to be treated as a B2B User
                $customer->setCompany(null);
                // $customer->setCompanyInfo(new CompanyInfo());
            }

            return $customer;
        }

        // Create new customer object but do not save the customer in the api
        // because some mandatory fields (e.g. birthday) may be missing!
        if ($isB2B) {
            return $this->createHeidelpayB2BCustomer($session->getFrontendSession()->getCustomer());
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

        $customerObj->setEmail($customer->cMail);

        if (!empty($customer->cFirma)) {
            $customerObj->setCompany(
                html_entity_decode(utf8_encode($customer->cFirma), ENT_COMPAT, 'UTF-8')
            );
        }

        // Set external customer so we do not have to map it ourself.
        $customerObj->setCustomerId((string) $customer->kKunde);

        return $customerObj;
    }

    /**
     * Create a Heidelpay Address for Shipping
     *
     * @param \stdClass|Adresse $address
     * @return Address
     */
    protected function createHeidelpayAddress($address): Address
    {
        $type = $_SESSION['Bestellung'] && $_SESSION['Bestellung']->kLieferadresse == -1
            ? ShippingTypes::DIFFERENT_ADDRESS
            : ShippingTypes::EQUALS_BILLING;

        return (new Address())
            ->setName(Text::convertUTF8(html_entity_decode($address->cVorname . ' ' . $address->cNachname)))
            ->setStreet(Text::convertUTF8(html_entity_decode($address->cStrasse . ' ' . $address->cHausnummer)))
            ->setZip(Text::convertUTF8(html_entity_decode($address->cPLZ)))
            ->setCity(Text::convertUTF8(html_entity_decode($address->cOrt)))
            ->setCountry(Text::convertUTF8(html_entity_decode($address->cLand)))
            ->setShippingType($type);
    }

    /**
     * Create a Heidelpay B2B Customer (registered or non-registered) instance.
     *
     * @param ShopCustomer $customer
     * @return Customer
     */
    protected function createHeidelpayB2BCustomer(ShopCustomer $customer): Customer
    {
        $address  = (new Address())
            ->setName(Text::convertUTF8(html_entity_decode($customer->cVorname . ' ' . $customer->cNachname)))
            ->setStreet(Text::convertUTF8(html_entity_decode($customer->cStrasse . ' ' . $customer->cHausnummer)))
            ->setZip(Text::convertUTF8(html_entity_decode($customer->cPLZ)))
            ->setCity(Text::convertUTF8(html_entity_decode($customer->cOrt)))
            ->setCountry(Text::convertUTF8(html_entity_decode($customer->cLand)));

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
            $obj->setSalutation($customer->cAnrede == 'm' ? 'mr' : 'mrs');
            $obj->setCustomerId($customer->kKunde);

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
        $obj->setSalutation($customer->cAnrede == 'm' ? 'mr' : 'mrs');
        $obj->setCustomerId($customer->kKunde);

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
}
