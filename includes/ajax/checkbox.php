<?php

define('IN_ACP', 0x000001);

require(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/bootstrap.php');

if( !$app['request']->is_set_post('id') || !$app['request']->is_set_post('table') || !$app['request']->is_set_post('value') || !$app['request']->is_set_post('input_name') || !$app['request']->is_ajax )
{
	exit('error');
}

$id         = $app['request']->post('id', 0);
$input_name = $app['request']->post('input_name', '');
$table      = $app['request']->post('table', '');
$value      = $app['request']->post('value', 0);

$sql = '
	UPDATE
		' . $table . '
	SET
		' . $input_name . ' = ' . $app['db']->check_value($value) . '
	WHERE
		id = ' . $app['db']->check_value($id);
$app['db']->query($sql);

exit('success');

?>