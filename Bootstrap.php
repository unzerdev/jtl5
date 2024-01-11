<?php

declare(strict_types=1);

namespace Plugin\s360_unzer_shop5;

use JTL\Checkout\Bestellung;
use JTL\Events\Dispatcher;
use JTL\Helpers\Request;
use JTL\Plugin\Bootstrapper;
use JTL\Plugin\BootstrapperInterface;
use JTL\Plugin\Payment\Method;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayFlexiPayDirect;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayHirePurchaseDirectDebit;
use Plugin\s360_unzer_shop5\paymentmethod\HeidelpayInvoiceFactoring;
use Plugin\s360_unzer_shop5\Seeders\Shop4PluginMigrationSeeder;
use Plugin\s360_unzer_shop5\src\ApplePay\CertificationService;
use Plugin\s360_unzer_shop5\src\Controllers\Admin\AdminApplePayController;
use Plugin\s360_unzer_shop5\src\Controllers\Admin\AdminKeyPairsController;
use Plugin\s360_unzer_shop5\src\Controllers\Admin\AdminOrdersController;
use Plugin\s360_unzer_shop5\src\Controllers\Admin\AdminSettingsController;
use Plugin\s360_unzer_shop5\src\Controllers\ApplePayController;
use Plugin\s360_unzer_shop5\src\Controllers\FrontendOutputController;
use Plugin\s360_unzer_shop5\src\Controllers\PaymentController;
use Plugin\s360_unzer_shop5\src\Controllers\SyncController;
use Plugin\s360_unzer_shop5\src\Foundation\Seeder;
use Plugin\s360_unzer_shop5\src\Foundation\ServiceProvider;
use Plugin\s360_unzer_shop5\src\KeyPairs\KeyPairModel;
use Plugin\s360_unzer_shop5\src\Orders\OrderMappingModel;
use Plugin\s360_unzer_shop5\src\Payments\Interfaces\NotificationInterface;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\JtlLinkHelper;
use Plugin\s360_unzer_shop5\src\Utils\Logger;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;
use Throwable;

/**
 * Plugin Bootstrapper
 * @package Plugin\s360_unzer_shop5
 */
class Bootstrap extends Bootstrapper implements BootstrapperInterface
{
    /**
     * @inheritDoc
     */
    public function boot(Dispatcher $dispatcher): void
    {
        parent::boot($dispatcher);
        require_once __DIR__ . '/vendor/autoload.php';

        /**
         * Register Services
         */
        $services = new ServiceProvider(Shop::Container());
        $services->register();

        /**
         * Hook Registration & Handling
         */
        $dispatcher->listen('shop.hook.' . \HOOK_BESTELLABSCHLUSS_INC_BESTELLUNGINDB, function (array $args) {
            try {
                /** @var Bestellung $order */
                $order = $args['oBestellung'];

                /** @var SessionHelper $sessionHelper */
                $sessionHelper = Shop::Container()->get(SessionHelper::class);

                // update cBestellNummer because we might already have generated it but bestellungInDB() has generated
                // a new one, resulting in a wrong order number in the confirmation mail for example!
                $orderNumber = $sessionHelper->get(SessionHelper::KEY_ORDER_ID);
                if (!empty($orderNumber) && $orderNumber != $order->cBestellNr) {
                    $order->cBestellNr = $orderNumber;
                } elseif ($sessionHelper->get(SessionHelper::KEY_CHECKOUT_SESSION)) {
                    $sessionHelper->set(SessionHelper::KEY_ORDER_ID, $order->cBestellNr);
                }

                /**
                 * Handle Pending Orders.
                 *
                 * Prevent the WaWi from collection an order that is currently PENDING.
                 * Therefore, we mark the order as already collected (not great but JTL does not have a pending state).
                 */
                if (Shop::has('360HpOrderPending')) {
                    $order->cAbgeholt = 'Y';
                }
            } catch (Throwable $th) {
                Logger::error(
                    'Error ' . $th->getCode() . ':' . $th->getMessage() . ', Exception in Hook '
                    . \HOOK_BESTELLABSCHLUSS_INC_BESTELLUNGINDB
                );
            }
        });

        $dispatcher->listen('shop.hook.' . \HOOK_SMARTY_OUTPUTFILTER, function () {
            // Hook into template output.
            try {
                $paymentController = new PaymentController($this->getPlugin());
                $paymentController->handle();

                $controller = new FrontendOutputController($this->getPlugin());
                $controller->handle();
            } catch (Throwable $th) {
                Logger::error(
                    'Error ' . $th->getCode() . ':' . $th->getMessage() . ', Exception in Hook '
                    . \HOOK_SMARTY_OUTPUTFILTER
                );
            }
        });

        $dispatcher->listen('shop.hook.' . \HOOK_BESTELLUNGEN_XML_BEARBEITESET, function (array $args) {
            // Hook into order syncs. This is done per order. E.g. to detect shipment of orders and trigger captures.
            try {
                $controller = new SyncController($this->getPlugin());
                $controller->setOrder($args['oBestellung']);
                $controller->setAction(SyncController::ACTION_SHIPMENT);
                $controller->handle();
            } catch (Throwable $th) {
                Logger::error(
                    'Error ' . $th->getCode() . ':' . $th->getMessage() . ', Exception in Hook '
                    . \HOOK_BESTELLUNGEN_XML_BEARBEITESET
                );
            }
        });

        $dispatcher->listen('shop.hook.' . \HOOK_BESTELLUNGEN_XML_BEARBEITESTORNO, function (array $args) {
            // Hook into order cancelations. This is done per order.
            try {
                $controller = new SyncController($this->getPlugin());
                $controller->setOrder($args['oBestellung']);
                $controller->setAction(SyncController::ACTION_CANCEL);
                $controller->handle();
            } catch (Throwable $th) {
                Logger::error(
                    'Error ' . $th->getCode() . ':' . $th->getMessage() . ', Exception in Hook '
                    . \HOOK_BESTELLUNGEN_XML_BEARBEITESTORNO
                );
            }
        });

        $dispatcher->listen('shop.hook.' . \HOOK_IO_HANDLE_REQUEST, function (array $args) {
            // Hook into ajax request handling for apple pay merchant validation
            try {
                $controller = new ApplePayController(
                    $this->getPlugin(),
                    Shop::Container()->get(CertificationService::class),
                    $args['io']
                );
                $controller->handle();
            } catch (Throwable $th) {
                Logger::error(
                    'Error ' . $th->getCode() . ':' . $th->getMessage() . ', Exception in Hook '
                    . \HOOK_IO_HANDLE_REQUEST
                );
            }
        });

        // Backend Hooks
        if (!Shop::isFrontend()) {
            $dispatcher->listen('backend.notification', function () {
                // Payment Method Notifications
                foreach ($this->getPlugin()->getPaymentMethods()->getMethods() as $paymentMethod) {
                    $method = Method::create($paymentMethod->getModuleID());

                    if ($method instanceof NotificationInterface) {
                        $method->initBackendNotification();
                    }
                }

                // Config Notifications
                /** @var Config $config */
                $config = Shop::Container()->get(Config::class);
                $config->initBackendNotification();
            });
        }
    }

    /**
     * @inheritDoc
     */
    public function installed()
    {
        parent::installed();

        /** @var Seeder[] $seeders */
        $seeders = [
            Shop4PluginMigrationSeeder::load()
        ];

        foreach ($seeders as $seeder) {
            $seeder->run();
        }

        // Deactivate Invoice Factoring Payment Method by setting nNutzbar to 0
        foreach ($this->getPlugin()->getPaymentMethods()->getMethods() as $method) {
            if (
                ($method->getActive() || $method->getUsable()) &&
                in_array($method->getClassName(), $this->getDeprecatedPaymentMethods())
            ) {
                $this->getDB()->update(
                    'tzahlungsart',
                    'kZahlungsart',
                    $method->getMethodID(),
                    (object) ['nNutzbar' => 0, 'nActive' => 0]
                );
            }
        }

        Shop::Container()->getCache()->flushTags([
            CACHING_GROUP_CORE,
            CACHING_GROUP_LANGUAGE,
            CACHING_GROUP_LICENSES,
            CACHING_GROUP_PLUGIN,
            CACHING_GROUP_BOX
        ]);
    }

    /**
     * @inheritDoc
     */
    public function updated($oldVersion, $newVersion)
    {
        // Deactivate Invoice Factoring Payment Method by setting nNutzbar to 0
        foreach ($this->getPlugin()->getPaymentMethods()->getMethods() as $method) {
            if (
                ($method->getActive() || $method->getUsable()) &&
                in_array($method->getClassName(), $this->getDeprecatedPaymentMethods())
            ) {
                $this->getDB()->update(
                    'tzahlungsart',
                    'kZahlungsart',
                    $method->getMethodID(),
                    (object) ['nNutzbar' => 0, 'nActive' => 0]
                );
                $this->getDB()->delete('tversandartzahlungsart', 'kZahlungsart', $method->getMethodID());
            }
        }

        Shop::Container()->getCache()->flushTags([
            CACHING_GROUP_CORE,
            CACHING_GROUP_LANGUAGE,
            CACHING_GROUP_LICENSES,
            CACHING_GROUP_PLUGIN,
            CACHING_GROUP_BOX
        ]);
    }

    /**
     * @inheritDoc
     */
    public function renderAdminMenuTab(string $tabName, int $menuID, JTLSmarty $smarty): string
    {
        try {
            switch ($tabName) {
                case JtlLinkHelper::ADMIN_TAB_ORDERS:
                    $model = new OrderMappingModel(Shop::Container()->getDB());

                    // Handle Ajax Requests
                    if (Request::isAjaxRequest() && Request::getVar('controller') == 'OrderManagement') {
                        try {
                            $controller = new AdminOrdersController($this->getPlugin(), $smarty);
                            $controller->setModel($model);
                            $controller->handleAjax();
                        } catch (Throwable $th) {
                            echo json_encode([
                                'status'   => 'error',
                                'messages' => [$th->getMessage()]
                            ]);
                            die;
                        }
                    }

                    $controller = new AdminOrdersController($this->getPlugin(), $smarty);
                    $controller->setModel($model);
                    return $controller->handle();
                case JtlLinkHelper::ADMIN_TAB_APPLE_PAY:
                    $controller = new AdminApplePayController($this->getPlugin(), $smarty);
                    $controller->setCertService(Shop::Container()->get(CertificationService::class));
                    return $controller->handle();
                case JtlLinkHelper::ADMIN_TAB_SETTINGS:
                    $controller = new AdminSettingsController($this->getPlugin(), $smarty);
                    return $controller->handle();
                case JtlLinkHelper::ADMIN_TAB_KEYPAIRS:
                    $model = new KeyPairModel($this->getDB());
                    $controller = new AdminKeyPairsController($this->getPlugin(), $smarty);
                    $controller->setModel($model);

                    // Handle Ajax Requests
                    if (Request::isAjaxRequest() && Request::getVar('controller') == 'KeyPairs') {
                        try {
                            $controller->handleAjax();
                        } catch (Throwable $th) {
                            echo json_encode([
                                'status'   => 'error',
                                'messages' => [$th->getMessage()]
                            ]);
                            die;
                        }
                    }

                    return $controller->handle();
                default:
                    return parent::renderAdminMenuTab($tabName, $menuID, $smarty);
            }
        } catch (Throwable $th) {
            Logger::error('Exception in ' . $tabName . ': ' .  print_r($th, true));
            return $th->getMessage();
        }
    }

    private function getDeprecatedPaymentMethods(): array
    {
        return  [
            HeidelpayInvoiceFactoring::class,
            HeidelpayHirePurchaseDirectDebit::class,
            HeidelpayFlexiPayDirect::class
        ];
    }
}
