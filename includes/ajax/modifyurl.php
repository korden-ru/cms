<?php

define('IN_ACP', 0x000001);

require(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/bootstrap.php');

if( !$app['request']->is_set_post('value') || !$app['request']->is_ajax )
{
	exit;
}

exit(modifyUrl($app['request']->post('value', '')));

?>