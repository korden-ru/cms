<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

define('IN_ACP', 0x000001);

require(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/bootstrap.php');

if( !$app['request']->is_set_post('value') || !$app['request']->is_ajax )
{
	exit;
}

exit(seo_url($app['request']->post('value', '')));

?>