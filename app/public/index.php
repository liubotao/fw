<?php

date_default_timezone_set('Asia/Shanghai');
header('Content-Type: text/html; charset=utf-8');

$base_uri = DIRECTORY_SEPARATOR == '/' ? dirname($_SERVER["SCRIPT_NAME"]) : str_replace('\\', '/', dirname($_SERVER["SCRIPT_NAME"]));
define("BASE_URI", $base_uri == '/' ? '' : $base_uri);
define('DS', DIRECTORY_SEPARATOR);
unset($base_uri);
define('APP_NAME', 'SHOP');
define('APP_PATH', realpath(dirname(__FILE__)) . '/../');
define('SYS_PATH', APP_PATH . "../system/");
define('SYS_EXT_PATH', APP_PATH . "../system-ext/");
define('DEBUG', true);
define('LOCAL_TIME_OFFSET', 0);
define('APP_LOCAL_TIMESTAMP', time() + LOCAL_TIME_OFFSET);
define('ENVIRONMENT', 'dev');

$GLOBAL_LOAD_PATH = array(
        APP_PATH,
        SYS_EXT_PATH
);

require_once(SYS_PATH . "ClassLoader.php");
Application::instance()->run();