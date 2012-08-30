<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

namespace acp;

define('IN_ACP', true);
session_start();

require(__DIR__ . '/../../public_html/bootstrap.php');

/* Маршрутизация запроса */
$app['router']->_init('', '\\acp\\')->handle_request();
