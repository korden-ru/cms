<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

define('IN_ACP', 0x000001);

require(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/bootstrap.php');

if( !$app['request']->is_set_post('id') || !$app['request']->is_set_post('table') || !$app['request']->is_set_post('column') || !$app['request']->is_set_post('dir') || !$app['request']->is_ajax )
{
	die('error');
}

$id     = $app['request']->post('id', 0);
$table  = $app['request']->post('table', '');
$column = $app['request']->post('column', '');
$dir    = $app['request']->post('dir', '');

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