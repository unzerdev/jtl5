<?php

use JTL\Shop;
use Plugin\s360_unzer_shop5\src\Controllers\SyncWorkflowController;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\Logger;

try {
    $controller = new SyncWorkflowController(Shop::Container()->get(Config::PLUGIN_ID));
    $controller->handle();
} catch (Exception $exc) {
    Logger::error(
        $exc->getCode() . ':' . $exc->getMessage() . ', Exception in FRONTEND_LINK sync-workflow.php'
    );
    http_response_code(403);
}

exit;
