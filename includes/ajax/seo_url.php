<?php

define('IN_ACP', 0x000001);

require(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/bootstrap.php');

if( !$request->is_set_post('value') || !$request->is_ajax )
{
	exit;
}

exit(seo_url($request->post('value', '')));

?>