<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Utils;

use UnzerSDK\Validators\PrivateKeyValidator;
use UnzerSDK\Validators\PublicKeyValidator;
use JTL\Backend\Notification;
use JTL\Backend\NotificationEntry;
use JTL\DB\DbInterface;
use JTL\Plugin\PluginInterface;
use JTL\Shop;

/**
 * Config Class
 *
 * @package Plugin\s360_unzer_shop5\src\Utils
 */
class Config
{
    public const TABLE = 'xplugin_s360_unzer_shop5_config';
    public const PLUGIN_ID = 's360_unzer_shop5';
    public const PLUGIN_SESSION = 's360_heidelpay';
    public const HIP_URL = 'https://insights.unzer.com/merchant/{merchantId}/order/{id}';
    public const HIP_URL_SANDBOX = 'https://sbx-insights.unzer.com/merchant/{merchantId}/order/{id}';

    // Lang Var Keys
    public const LANG_INVALID_TOKEN = 's360_hp_invalid_form_token';
    public const LANG_PAYMENT_PROCESS_RUNTIME_EXCEPTION = 's360_hp_payment_process_runtime_exception';
    public const LANG_PAYMENT_PROCESS_EXCEPTION = 's360_hp_payment_process_exception';
    public const LANG_SEPA_MANDATE = 's360_hp_sepa_mandate';
    public const LANG_REDIRECTING = 's360_hp_redirecting';
    public const LANG_CONFIRM_INSTALLMENT_TITLE = 's360_hp_confirm_instalment_title';
    public const LANG_DOWNLOAD_AND_CONFIRM_INSTALLMENT_PLAN = 's360_hp_confirm_download_instalment_plan';
    public const LANG_TOTAL_PURCHASE_AMOUNT = 's360_hp_total_purchase_amount';
    public const LANG_TOTAL_INTEREST_AMOUNT = 's360_hp_total_interest_amount';
    public const LANG_TOTAL_AMOUNT = 's360_hp_total_amount';
    public const LANG_DOWNLOAD_YOUR_PLAN = 's360_hp_download_your_plan';
    public const LANG_CLOSE_MODAL = 's360_hp_close_modal';
    public const LANG_CONFIRMATION_CHECKSUM = 's360_hp_confirmation_checksum';

    // Config Keys
    public const PRIVATE_KEY = 'privateKey';
    public const PUBLIC_KEY = 'publicKey';
    public const MERCHANT_ID = 'merchantId';
    public const FONT_SIZE = 'fontSize';
    public const FONT_COLOR = 'fontColor';
    public const FONT_FAMILY = 'fontFamily';
    public const SELECTOR_SUBMIT_BTN = 'selectorSubmitButton';
    public const PQ_SELECTOR_CHANGE_PAYMENT_METHOD = 'pqSelectorChangePaymentMethod';
    public const PQ_METHOD_CHANGE_PAYMENT_METHOD = 'pqMethodChangePaymentMethod';
    public const PQ_SELECTOR_ERRORS = 'pqSelectorErrors';
    public const PQ_METHOD_ERRORS = 'pqMethodErrors';
    public const PQ_SELECTOR_REVIEW_STEP = 'pqSelectorReviewStep';
    public const PQ_METHOD_REVIEW_STEP = 'pqMethodReviewStep';
    public const PQ_SELECTOR_PAYMENT_INFORMATION = 'pqSelectorPaymentInformation';
    public const PQ_METHOD_PAYMENT_INFORMATION = 'pqMethodPaymentInformation';

    /**
     * @var DbInterface
     */
    protected $database;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Load Config.
     */
    public function __construct()
    {
        $this->database = Shop::Container()->getDB();
        $this->load();
    }

    /**
     * Load Config Data.
     *
     * @return self
     */
    public function load(): self
    {
        $this->data = array_column($this->database->query('SELECT * FROM ' . self::TABLE, 9), 'value', 'key');
        return $this;
    }

    /**
     * Get a config entry.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set a new value for a config entry.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Check if a config entry exists.
     *
     * @return bool
     */
    public function has($key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get all config values.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Save config to the database.
     *
     * @return void
     */
    public function save(): void
    {
        foreach ($this->data as $key => $value) {
            $this->database->executeQueryPrepared(
                'INSERT INTO ' . self::TABLE . '(`key`, `value`)
                VALUES (:key, :value)
                ON DUPLICATE KEY UPDATE `value` = :value',
                ['key' => $key, 'value' => $value],
                3
            );
        }
    }

    /**
     * Get a payment setting.
     *
     * @param string $key
     * @param string $moduleId
     * @return string|null
     */
    public function getPaymentSetting(string $key, string $moduleId): ?string
    {
        /** @var PluginInterface $plugin */
        $plugin = Shop::Container()->get(self::PLUGIN_ID);
        return $plugin->getConfig()->getValue($moduleId . '_' . $key);
    }

    /**
     * Get Insight Portal URL if merchant id is configured
     *
     * @param string|null $uid
     * @return string|null
     */
    public function getInsightPortalUrl(?string $uid): ?string
    {
        $merchantId = $this->get(self::MERCHANT_ID);

        if (!empty($merchantId) && !empty($uid)) {
            return str_replace(
                ['{merchantId}', '{id}'],
                [$merchantId, $uid],
                $this->isSandbox() ? self::HIP_URL_SANDBOX : self::HIP_URL
            );
        }

        return null;
    }

    /**
     * Check if api is in sandbox mode based on api keys
     *
     * @return boolean
     */
    public function isSandbox(): bool
    {
        $priv = $this->get(self::PRIVATE_KEY);
        $pub = $this->get(self::PUBLIC_KEY);

        return substr($priv, 0, 1) === 's' && substr($pub, 0, 1) === 's';
    }

    /**
     * Init Backend notifications.
     *
     * @return void
     */
    public function initBackendNotification(): void
    {
        /** @var JtlLinkHelper $link */
        $link = Shop::Container()->get(JtlLinkHelper::class);

        /** @var PluginInterface $plugin */
        $plugin = Shop::Container()->get(self::PLUGIN_ID);

        if (!PrivateKeyValidator::validate($this->get(Config::PRIVATE_KEY))) {
            $this->addErrorNotification(__('hpInvalidPrivateKeyNotification'), $link, $plugin);
        }

        if (!PublicKeyValidator::validate($this->get(Config::PUBLIC_KEY))) {
            $this->addErrorNotification(
                __('hpInvalidPublicKeyNotification'),
                $link,
                $plugin
            );
        }
    }

    /**
     * Add an error notification.
     *
     * @param string $message
     * @param JtlLinkHelper $link
     * @param PluginInterface $plugin
     * @return void
     */
    private function addErrorNotification(string $message, JtlLinkHelper $link, PluginInterface $plugin): void
    {
        $notification = new NotificationEntry(
            NotificationEntry::TYPE_DANGER,
            __('hpNotificationHeader'),
            $message,
            $link->getFullAdminTabUrl(JtlLinkHelper::ADMIN_TAB_SETTINGS)
        );
        $notification->setPluginId($plugin->getID());
        Notification::getInstance()->addNotify($notification);
    }
}
