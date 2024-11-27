<?php declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Controllers\Admin;

use JTL\Link\Link;
use JTL\Plugin\Data\PaymentMethod;
use JTL\Plugin\PluginInterface;
use JTL\Smarty\JTLSmarty;
use JTL\Shop;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerApplePay;
use Plugin\s360_unzer_shop5\paymentmethod\UnzerApplePayV2;
use Plugin\s360_unzer_shop5\src\Controllers\Controller;
use Plugin\s360_unzer_shop5\src\Utils\JtlLinkHelper;

use function Functional\first;

/**
 * Abstract Admin Controller
 *
 * @package Plugin\s360_unzer_shop5\src\Controllers\Admin
 */
abstract class AdminController extends Controller
{
    /**
     * @var array Errors to show to the user.
     */
    protected $errors;

    /**
     * @var array warnings to show to the user.
     */
    protected $warnings;

    /**
     * @var array Messages to show to the user.
     */
    protected $messages;

    /**
     * @var array Success Messages to show to the user.
     */
    protected $successes;

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     * @param PluginInterface $plugin
     */
    public function __construct(PluginInterface $plugin, JTLSmarty $smarty)
    {
        $this->errors = [];
        $this->warnings = [];
        $this->messages = [];
        $this->successes = [];

        parent::__construct($plugin, $smarty);
    }

    /**
     * Prepare variable which are passed to the view.
     *
     * @return void
     */
    protected function prepare(): void
    {
        $linkHelper = new JtlLinkHelper();

        $data = [
            'adminTemplatePath' => $this->plugin->getPaths()->getAdminPath() . 'template/',
            'adminTemplateUrl'  => $this->plugin->getPaths()->getAdminURL() . 'template/',
            'adminMenuUrl'      => $this->plugin->getPaths()->getAdminURL(),
            'adminUrl'          => $linkHelper->getFullAdminUrl(),
            'pluginVersion'     => (string) $this->plugin->getCurrentVersion()
        ];

        // Check for correct seo urls of frontend links
        foreach ($this->plugin->getLinks()->getLinks() as $link) {
            /** @var Link $link */
            switch ($link->getHandler()) {
                case 'sync-workflow.php':
                    if (!in_array('unzer-sync-workflow', $link->getSEOs())) {
                        $this->addWarning(__('hpWarningSyncWorkflowUrlChanged'));
                    }
                    break;

                case 'webhook.php':
                    if (!in_array('unzer-webhook', $link->getSEOs())) {
                        $this->addWarning(__('hpWarningWebhookUrlChanged'));
                    }
                    break;
            }
        }

        //  Check if deprecated apple pay method is active, if so, show migration note
        if ($this->isPaymentMethodInUse(UnzerApplePay::class)) {
            $this->addMessage(nl2br(__('hpApplePayMigrationNotification')));
        }

        if (
            $this->isPaymentMethodInUse(UnzerApplePayV2::class) &&
            !is_readable(PFAD_ROOT . '/.well-known/apple-developer-merchantid-domain-association')
        ) {
            $this->addError(nl2br(sprintf(__('hpApplePayMissingMerchantIdDomainAssociation'), Shop::getURL(true))));
        }

        $this->smarty->assign('hpAdmin', $data);
    }

    protected function isPaymentMethodInUse(string $paymentMethod)
    {
        $method = first(
            $this->plugin->getPaymentMethods()->getMethods(),
            static fn(PaymentMethod $method) => $method->getClassName() === $paymentMethod && $method->getActive()
        );

        if ($method) {
            return  Shop::Container()->getDB()->select(
                'tversandartzahlungsart',
                'kZahlungsart',
                $method->getMethodID()
            );
        }

        return false;
    }

    /**
     * Render a template view.
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    protected function view(string $template, array $data = []): string
    {
        foreach ($data as $key => $value) {
            $this->smarty->assign($key, $value);
        }

        $template = $this->plugin->getPaths()->getAdminPath() . $template . '.tpl';
        $custom = $this->plugin->getPaths()->getAdminPath() . $template . '_custom.tpl';

        if (file_exists($custom)) {
            $template = $custom;
        }

        $this->smarty->assign('hpErrors', $this->errors);
        $this->smarty->assign('hpWarnings', $this->warnings);
        $this->smarty->assign('hpSuccesses', $this->successes);
        $this->smarty->assign('hpMessages', $this->messages);

        return $this->smarty->fetch($template);
    }

    /**
     * Add an error to display to the user.
     *
     * @param string $error
     * @param string $postfix
     * @return void
     */
    protected function addError(string $error, string $postfix = ''): void
    {
        $this->errors[] = $error . $postfix;
    }

    /**
     * Add a warning to display to the user.
     *
     * @param string $warning
     * @return void
     */
    protected function addWarning(string $warning): void
    {
        if (!\in_array($warning, $this->warnings, true)) {
            $this->warnings[] = $warning;
        }
    }

    /**
     * Add a success to display to the user.
     *
     * @param string $success
     * @return void
     */
    protected function addSuccess(string $success): void
    {
        if (!\in_array($success, $this->successes, true)) {
            $this->successes[] = $success;
        }
    }

    /**
     * Add a message to display to the user.
     *
     * @param string $message
     * @return void
     */
    protected function addMessage(string $message): void
    {
        if (!\in_array($message, $this->messages, true)) {
            $this->messages[] = $message;
        }
    }
}
