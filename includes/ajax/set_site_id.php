<?php

define('IN_ACP', 0x000001);

require(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/bootstrap.php');

if( !$app['request']->is_set_post('site_id') || !$app['request']->is_ajax )
{
	exit;
}

$site_id = $app['request']->post('site_id', 1);

$app['user']->set_cookie('site_id', $site_id, '');

exit;
