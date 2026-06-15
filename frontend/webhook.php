<?php declare(strict_types = 1);

use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Controllers\WebhookController;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\Logger;

try {
    $controller = new WebhookController(Shop::Container()->get(Config::PLUGIN_ID));
    $controller->handle();
} catch (Throwable $exc) {
    Logger::error(
    'Exception in FRONTEND_LINK webhook.php: ' . $exc->getMessage() . PHP_EOL . $exc->getTraceAsString()
    );
    http_response_code(403);
}

exit;
