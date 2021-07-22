<?php declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Payments\Traits;

use JTL\Checkout\Lieferadresse;
use JTL\Customer\Customer;

/**
 * Provide methods for payment methods which require B2B Customers.
 *
 * @see https://docs.heidelpay.com/docs/additional-resources
 * @package Plugin\s360_unzer_shop5\src\Payments\Traits
 */
trait SupportsB2B
{
    /**
     * Check if the customer is a B2B Customer
     *
     * @param Customer $customer
     * @return boolean
     */
    protected function isB2BCustomer(Customer $customer): bool
    {
        // * Note: Maybe use customer group(s) here?
        return isset($customer->cFirma) && strlen(trim($customer->cFirma)) > 0;
    }

    /**
     * Checks if shipping and invoice address are the same or not.
     *
     * @param Lieferadresse|stdClass $shipping
     * @param Customer $invoice
     * @return boolean
     */
    protected function shippingEqualsInvoiceAddress($shipping, Customer $invoice): bool
    {
        $equalProps = ['cFirma', 'cVorname', 'cNachname', 'cStrasse', 'cHausnummer', 'cPLZ', 'cOrt', 'cLand'];

        foreach ($equalProps as $prop) {
            if (!property_exists($shipping, $prop) || !property_exists($invoice, $prop)) {
                return false;
            }

            if ($shipping->$prop != $invoice->$prop) {
                return false;
            }
        }

        return true;
    }
}
