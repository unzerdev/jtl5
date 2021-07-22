<?php declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Payments\Traits;

use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\EmbeddedResources\Address;
use JTL\Checkout\Adresse;
use JTL\Customer\Customer as ShopCustomer;
use JTL\Helpers\Text;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayApiAdapter;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use UnzerSDK\Resources\EmbeddedResources\CompanyInfo;

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
            $customer = $adapter->getApi()->fetchCustomer($session->get(SessionHelper::KEY_CUSTOMER_ID));

            if (!empty($frontSession->getCustomer()->cFirma) && empty($customer->getCompany())) {
                $customer->setCompany(Text::convertUTF8($frontSession->getCustomer()->cFirma));
            }

            // Update names as they might have changed (but not on B2B so that we do not overwrite the B2B Form changes)
            if (!$isB2B) {
                $customer->setFirstname(Text::convertUTF8($frontSession->getCustomer()->cVorname));
                $customer->setLastname(Text::convertUTF8($frontSession->getCustomer()->cNachname));

                // Remove Company Infomartion as we do not want to be treated as a B2B User
                $customer->setCompany(null);
            }

            return $customer;
        }

        // Create new customer object but do not save the customer in the api
        // because some mandatory fields (e.g. birthday) may be missing!
        if ($isB2B) {
            return $this->createHeidelpayB2BCustomer($session->getFrontendSession()->getCustomer());
        }

        return $adapter->getApi()->createOrUpdateCustomer(
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
            Text::convertUTF8($customer->cVorname),
            Text::convertUTF8($customer->cNachname)
        );

        $customerObj->setEmail($customer->cMail);

        // Set external customer so we do not have to map it ourself.
        $customerObj->setCustomerId($customer->kKunde);

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
        return (new Address())
            ->setName(Text::convertUTF8($address->cVorname . ' ' . $address->cNachname))
            ->setStreet(Text::convertUTF8($address->cStrasse . ' ' . $address->cHausnummer))
            ->setZip(Text::convertUTF8($address->cPLZ))
            ->setCity(Text::convertUTF8($address->cOrt))
            ->setCountry(Text::convertUTF8($address->cLand));
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
            ->setName(Text::convertUTF8($customer->cVorname . ' ' . $customer->cNachname))
            ->setStreet(Text::convertUTF8($customer->cStrasse . ' ' . $customer->cHausnummer))
            ->setZip(Text::convertUTF8($customer->cPLZ))
            ->setCity(Text::convertUTF8($customer->cOrt))
            ->setCountry(Text::convertUTF8($customer->cLand));

        // Registered = registered in the commercial register with a commercial register number
        if ($customer->cUSTID) {
            $obj = CustomerFactory::createRegisteredB2bCustomer(
                $address,
                Text::convertUTF8($customer->cUSTID),
                Text::convertUTF8($customer->cFirma)
            );

            $obj->setFirstname(Text::convertUTF8($customer->cVorname));
            $obj->setLastname(Text::convertUTF8($customer->cNachname));
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
            Text::convertUTF8($customer->cVorname),
            Text::convertUTF8($customer->cNachname),
            $birthday,
            $address,
            Text::convertUTF8($customer->cMail),
            Text::convertUTF8($customer->cFirma)
        );
        $obj->setSalutation($customer->cAnrede == 'm' ? 'mr' : 'mrs');
        $obj->setCustomerId($customer->kKunde);

        return $obj;
    }
}
