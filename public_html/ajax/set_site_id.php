<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

define('IN_ACP', true);

require(__DIR__ . '/../../../public_html/bootstrap.php');

if( !$app['request']->is_set_post('site_id') || !$app['request']->is_ajax )
{
	exit;
}

$site_id = $app['request']->post('site_id', 1);

$app['user']->set_cookie('site_id', $site_id, '');

exit;
