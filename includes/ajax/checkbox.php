<?php

define('IN_ACP', 0x000001);

require(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/bootstrap.php');

if( !$request->is_set_post('id') || !$request->is_set_post('table') || !$request->is_set_post('value') || !$request->is_set_post('input_name') || !$request->is_ajax )
{
	exit('error');
}

$id         = $request->post('id', 0);
$input_name = $request->post('input_name', '');
$table      = $request->post('table', '');
$value      = $request->post('value', 0);

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