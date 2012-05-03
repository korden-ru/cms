<?php

define('IN_ACP', 0x000001);

require(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/bootstrap.php');

if( !$request->is_set_post('id') || !$request->is_set_post('table') || !$request->is_set_post('column') || !$request->is_set_post('dir') || !$request->is_ajax )
{
	die('error');
}

$id     = $request->post('id', 0);
$table  = $request->post('table', '');
$column = $request->post('column', '');
$dir    = $request->post('dir', '');

$sql = '
	SELECT
		' . $column . '
	FROM
		' . $table . '
	WHERE
		id = ' . $app['db']->check_value($id);
$app['db']->query($sql);
$row = $app['db']->fetchrow();
$app['db']->freeresult();

if( !$row )
{
	/* Проверка существования записи */
	die('error');
}

$sql = '
	UPDATE
		' . $table . '
	SET
		' . $column . ' = ""
	WHERE
		id = ' . $app['db']->check_value($id);
$app['db']->query($sql);

unlink(SITE_DIR . 'uploads/' . $dir . '/original/' . $row[$column]);
unlink(SITE_DIR . 'uploads/' . $dir . '/sm/' . $row[$column]);
unlink(SITE_DIR . 'uploads/' . $dir . '/' . $row[$column]);

die('success');

?>