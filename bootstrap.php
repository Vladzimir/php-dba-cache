<?php
ini_set('default_charset', 'UTF-8');
date_default_timezone_set('UTC');

ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

set_include_path(
    dirname(dirname(__FILE__))
    .DIRECTORY_SEPARATOR
    .'php-dba-cache'
    .PATH_SEPARATOR
    .dirname(__FILE__)
    .PATH_SEPARATOR
    .get_include_path()
);

$root = dirname(__FILE__) . DIRECTORY_SEPARATOR;
require_once $root . 'app' . DIRECTORY_SEPARATOR . 'config.php';
require_once $root . 'src' . DIRECTORY_SEPARATOR . 'Cache.php';
require_once $root . 'src' . DIRECTORY_SEPARATOR . 'Pack.php';
require_once $root . 'src' . DIRECTORY_SEPARATOR . 'Sweep.php';
require_once $root . 'src' . DIRECTORY_SEPARATOR . 'Capsule.php';
