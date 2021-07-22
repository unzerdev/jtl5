<?php
declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Foundation;

use JTL\Plugin\Helper;
use JTL\Services\Container;
use Plugin\s360_unzer_shop5\src\Charges\ChargeHandler;
use Plugin\s360_unzer_shop5\src\Charges\ChargeMappingModel;
use Plugin\s360_unzer_shop5\src\Orders\OrderMappingModel;
use Plugin\s360_unzer_shop5\src\Payments\HeidelpayApiAdapter;
use Plugin\s360_unzer_shop5\src\Payments\PaymentHandler;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\JtlLinkHelper;
use Plugin\s360_unzer_shop5\src\Utils\SessionHelper;

/**
 * Service Provider Class
 *
 * Register Services in the JTL DI Container.
 *
 * @package Plugin\s360_unzer_shop5\src\Foundation
 */
class ServiceProvider
{
    /**
     * @var Container
     */
    private $app;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->app = $container;
    }

    /**
     * Register Services.
     *
     * @return void
     */
    public function register(): void
    {
        // Wrap Helper in a singleton because it does not cache it (=> many initializations during a request!)
        $this->app->setSingleton(Config::PLUGIN_ID, function () {
            return Helper::getPluginById(Config::PLUGIN_ID);
        });

        $this->app->setFactory(SessionHelper::class, function () {
            $session = new SessionHelper();
            return $session;
        });

        $this->app->setSingleton(Config::class, function () {
            return new Config();
        });

        $this->app->setSingleton(HeidelpayApiAdapter::class, function (Container $app) {
            return new HeidelpayApiAdapter(
                $app->get(Config::class),
                $app->get(SessionHelper::class),
                new JtlLinkHelper()
            );
        });

        $this->app->setFactory(ChargeHandler::class, function (Container $app) {
            return new ChargeHandler(new ChargeMappingModel($app->getDB()));
        });

        $this->app->setFactory(PaymentHandler::class, function (Container $app) {
            return new PaymentHandler(
                $app->get(Config::PLUGIN_ID),
                $app->get(Config::class),
                $app->get(SessionHelper::class),
                $app->get(HeidelpayApiAdapter::class),
                $app->get(ChargeHandler::class),
                new OrderMappingModel($app->getDB())
            );
        });
    }
}
