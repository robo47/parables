<?php
error_reporting( E_ALL | E_STRICT );

define('SCRIPTSTART' , microtime(true));
define('APPLICATION_ENVIRONMENT', 'testing');
define('BASE_PATH', realpath(dirname(__FILE__) . '/../'));
define('TESTS_PATH', BASE_PATH . '/tests/');

$pathes = array();
$pathes[] = BASE_PATH . '/library/';
$pathes[] = BASE_PATH; // for Parables
$pathes[] = TESTS_PATH;
$pathes[] = get_include_path();

// Include path
set_include_path(implode($pathes, PATH_SEPARATOR));

// Load Autoloader
require_once 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::getInstance()
                      ->setFallbackAutoloader(true);
