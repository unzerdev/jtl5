<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5\src\Utils;

use JTL\Alert\Alert;
use JTL\Helpers\Text;
use JTL\Services\JTL\AlertServiceInterface;
use JTL\Session\Frontend;
use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Utils\Config;

/**
 * Session helper
 *
 * @package Plugins\s360_unzer_shop5\Utils
 */
class SessionHelper
{
    use JtlLoggerTrait;

    public const KEY_ORDER_ID = 'orderId';
    public const KEY_RESOURCE_ID = 'resourceId';
    public const KEY_CART_CHECKSUM = 'cartChecksum';
    public const KEY_CHECKOUT_SESSION = 'checkoutSession';
    public const KEY_CONFIRM_POST_ARRAY = 'confirmPostArray';
    public const KEY_SHORT_ID = 'shortId';
    public const KEY_PAYMENT_ID = 'paymentId';
    public const KEY_CUSTOMER_ID = 'customerId';
    public const KEY_THREAT_METRIX_ID = 'threatMetrixId';

    /**
     * @var AlertServiceInterface
     */
    private $alerts;

    /**
     * Init Session
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function __construct()
    {
        // We need to set cISOSprache ourself (as its not set there for some reason)
        // If not, the JTL Session class might break the admin (AdminSession is not compatible with Session class)
        if (isset($_SESSION['AdminAccount']) && !isset($_SESSION['cISOSprache']) && isset($_SESSION['kSprache'])) {
            $langs = Shop::Container()->getDB()->query("SELECT * FROM tsprache", 2);
            foreach ($langs as $lang) {
                if ($_SESSION['kSprache'] == $lang->kSprache) {
                    $_SESSION['cISOSprache'] = trim($lang->cISO);
                    $_SESSION['currentLanguage'] = clone $lang;
                    break;
                }
            }
        }

        $this->alerts = Shop::Container()->getAlertService();
    }

    /**
     * Set Alert Service
     *
     * @param AlertServiceInterface $service
     * @return void
     */
    public function setAlertService(AlertServiceInterface $service): void
    {
        $this->alerts = $service;
    }

    /**
     * Get Alert Service.
     *
     * @return AlertServiceInterface
     */
    public function getAlertService(): AlertServiceInterface
    {
        return $this->alerts;
    }

    /**
     * Set a session value for a key.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value): void
    {
        Frontend::getInstance()->set(
            $this->buildSessionKey([Config::PLUGIN_SESSION, $key]),
            $value
        );
    }

    /**
     * Get a session value for a key.
     *
      * @param string $key
      * @param mixed $default
      * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Frontend::getInstance()->get(
            $this->buildSessionKey([Config::PLUGIN_SESSION, $key]),
            $default
        );
    }

    /**
     * Get Plugin session entries.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @return array
     */
    public function all(): array
    {
        return $_SESSION[Config::PLUGIN_SESSION] ?? [];
    }

    /**
     * Check if a session key exists.
     *
     * @param string $key
     * @return boolean
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Clear/delete a session entry
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @param string|null $key
     * @return void
     */
    public function clear(?string $key = null): void
    {
        if (is_null($key)) {
            unset($_SESSION[Config::PLUGIN_SESSION]);
            return;
        }

        $items = &$_SESSION[Config::PLUGIN_SESSION];
        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);

        foreach ($segments as $segment) {
            if (!isset($items[$segment]) || !is_array($items[$segment])) {
                continue;
            }

            $items = &$items[$segment];
        }

        unset($items[$lastSegment]);
    }

    /**
     * Get the frontend session.
     *
     * @return Frontend
     */
    public function getFrontendSession(): Frontend
    {
        return Frontend::getInstance();
    }

    /**
     * Build a session key in dot notation
     *
     * @param array $parts
     * @return string
     */
    public function buildSessionKey(array $parts): string
    {
        $filtered = array_filter($parts);
        return implode('.', $filtered);
    }

    /**
     * Get the payment checkout session
     *
     * @return array
     */
    public function getCheckoutSession()
    {
        return $this->get(self::KEY_CHECKOUT_SESSION, []);
    }

    /**
     * Save payment data in session
     *
     * @param string $resourceId
     * @return void
     */
    public function setCheckoutSession(string $resourceId): void
    {
        $this->set(
            $this->buildSessionKey(
                [self::KEY_CHECKOUT_SESSION, self::KEY_RESOURCE_ID]
            ),
            Text::filterXSS($resourceId)
        );
    }

    /**
     * Clear the payment session
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @return void
     */
    public function clearCheckoutSession(): void
    {
        $this->clear(self::KEY_CHECKOUT_SESSION);
    }

    /**
     * Set an error alert and redirect if needed.
     *
     * In case of a redirect it clears the plugin session first.
     *
     * @param string $merchant The merchant message that is logged.
     * @param string $customer The customer message that is displayed
     * @param string $key a unique identifier for the alert
     * @param string|null $redirect Url to redirect to
     * @param string|null $context
     * @return void
     */
    public function addErrorAlert(string $merchant, string $customer, string $key, string $redirect = null, string $context = null): void
    {
        if (empty($redirect)) {
            $this->clear();
        }

        $this->errorLog($merchant, $context ?? static::class);
        $this->redirectError($customer, $key, $redirect);
    }

    /**
     * Add an error alert and redirect if necessary.
     *
     * @SuppressWarnings(PHPMD.ExitExpression)
     * @param string $message
     * @param string $errorKey
     * @param string $url
     * @return void
     */
    public function redirectError(string $message, string $errorKey, string $url = null): void
    {
        if ($url) {
            $this->alerts->addAlert(Alert::TYPE_ERROR, $message, $errorKey, ['saveInSession' => true]);
            header('Location: ' . $url);
            exit;
            return;
        }

        $this->alerts->addAlert(Alert::TYPE_ERROR, $message, $errorKey);
    }

    /**
     * Generate a new threat metrix id if there is not already one in the session.
     *
     * @return string
     */
    public function generateThreatMetrixId(): string
    {
        if ($this->get(SessionHelper::KEY_THREAT_METRIX_ID)) {
            return $this->get(SessionHelper::KEY_THREAT_METRIX_ID);
        }

        $merchantId = pathinfo(Shop::getURL(), PATHINFO_FILENAME);
        $merchantId = preg_replace('/[^a-z0-9_-]/i', '', $merchantId);

        $id = $merchantId . '-' . bin2hex(openssl_random_pseudo_bytes(16));
        $this->set(SessionHelper::KEY_THREAT_METRIX_ID, $id);

        return $id;
    }
}
