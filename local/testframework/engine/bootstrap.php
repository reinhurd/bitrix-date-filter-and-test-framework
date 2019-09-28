<?php
$autoloadPath1 = __DIR__ . '/../../../autoload.php';
$autoloadPath2 = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath1)) {
    require_once $autoloadPath1;
} else {
    require_once $autoloadPath2;
}

//bitrix connector
$_SERVER["DOCUMENT_ROOT"] = dirname(__FILE__, 4) . '/';
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
define('DOCUMENT_ROOT', $_SERVER["DOCUMENT_ROOT"]);
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define("BX_CRONTAB", true);
define('BX_WITH_ON_AFTER_EPILOG', true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"]."bitrix/modules/main/include/prolog_before.php");

define("LOG_FILENAME", 'php://stderr');

$GLOBALS["DB"]->debug = true;
