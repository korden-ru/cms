<?php

define('IN_ACP', 0x000001);

require(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/bootstrap.php');

if( !$app['request']->is_ajax )
{
	exit('error_valid');
}

if( !$app['request']->is_set_post('id_gallery') || !$app['request']->is_set_post('id_photo') || !$app['request']->is_set_post('mysql_table') || !$app['request']->is_set_post('folder') )
{
	exit('error');
}

$folder      = $app['request']->post('folder', '');
$id_gallery  = $app['request']->post('id_gallery', 0);
$id_photo    = $app['request']->post('id_photo', 0);
$mysql_table = $app['request']->post('mysql_table', '');

//проверяем есть ли такая галерея
$sql = '
	SELECT
		title
	FROM
		' . $mysql_table . '
	WHERE
		id = ' . $app['db']->check_value($id_gallery);
$app['db']->query($sql);
$gallery = $app['db']->fetchrow();
$app['db']->freeresult();

if( !$gallery )
{
	exit('error');
}

$dirpath = SITE_DIR . 'uploads/' . $folder . '/';

//проверяем есть ли фотка в базе
$sql = '
	SELECT
		image
	FROM
		' . $mysql_table . '_photos
	WHERE
		id = ' . $app['db']->check_value($id_photo);
$app['db']->query($sql);
$photo = $app['db']->fetchrow();
$app['db']->freeresult();

if( !$photo )
{
	exit('error');
}

//удаляем фотку
$sql = '
	DELETE
	FROM
		' . $mysql_table . '_photos
	WHERE
		id = ' . $app['db']->check_value($id_photo);
$app['db']->query($sql);

//удаляем фотки на диске
unlink($dirpath . 'original/' . $photo['image']);
unlink($dirpath . 'sm/' . $photo['image']);
unlink($dirpath . $photo['image']);

exit('success');

?>