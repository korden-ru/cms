<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

namespace app;

ini_set("html_errors", "0");

// Check the upload
if( !isset($_FILES["Filedata"]) || !is_uploaded_file($_FILES["Filedata"]["tmp_name"]) || $_FILES["Filedata"]["error"] != 0 )
{
	echo "ERROR:invalid upload";
	exit(0);
}

// Get the session Id passed from SWFUpload. We have to do this to work-around the Flash Player Cookie Bug
if( isset($_POST["PHPSESSID"]) )
{
	session_id($_POST["PHPSESSID"]);
}

define('IN_ACP', 0x000001);

require(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/bootstrap.php');

if( !isset($_SESSION["file_info"]) )
{
	$_SESSION["file_info"] = array();
}

$folder      = $app['request']->post('folder', '');
$id_gallery  = $app['request']->post('id_gallery', 0);
$mysql_table = $app['request']->post('mysql_table', '');

$dirpath = SITE_DIR . 'uploads/' . $folder . '/';

$uploader = new \engine\core\upload();
$uploader->input_name = 'Filedata';
$uploader->watermark_pos_x = "right";
$uploader->watermark_pos_y = "bottom";
$uploader->watermark_delta = 0;

$saul = substr(md5(rand()*10000000),0,6);
$filename = "p".$id_gallery . '_' . $saul . '.' . $uploader->GetExtension();
$filename_noext = "p".$id_gallery . '_' . $saul;

if( $uploader->CheckFile() === true )
{
	$flag1024 = $flag226 = false;
	
	$uploader->dir_dest = $dirpath . 'sm/';
	if (!is_dir($uploader->dir_dest))
		mkdir($uploader->dir_dest, 0777, true);
	$flag226 = $uploader->CroppedImageResized(210, 158, $filename, true);
	//$flag226 = $uploader->ImageResized(220, "", $filename, true, false);
	
	$uploader->dir_dest = $dirpath;
	if (!is_dir($uploader->dir_dest))
		mkdir($uploader->dir_dest, 0777, true);
	$flag1024 = $uploader->ImageResized(1024, 1024, $filename, true, true);
	
	$uploader->dir_dest = $dirpath . 'original/';
	if (!is_dir($uploader->dir_dest))
		mkdir($uploader->dir_dest, 0777, true);
	$uploader->ImageResized(1024, 1024, $filename, true, false);

	//Если все флаги == true - картинки успешно сохранены
	if ($flag1024 && $flag226)
	{
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
			DeleteImages($dirpath, $filename);
			echo "ERROR: Галерея отсутствует в базе";	// Return the file id to the script
			exit;
		} 
		
		//Добавляем фото в галерею
		//узнаем максимальну сортировку
		$max_sort = 0;
		
		$sql = '
			SELECT
				MAX(`sort`) AS max_sort
			FROM
				' . $mysql_table . '_photos
			WHERE
				id_row = ' . $app['db']->check_value($id_gallery);
		$app['db']->query($sql);
		$max_sort = (int) $app['db']->fetchfield('max_sort') + 10;
		$app['db']->freeresult();
		
		//узнаем общее кол-во фотографий
		$total_photos = 0;
		
		$sql = '
			SELECT
				COUNT(*) AS total_photos
			FROM
				' . $mysql_table . '_photos
			WHERE
				id_row = ' . $app['db']->check_value($id_gallery);
		$app['db']->query($sql);
		$total_photos = (int) $app['db']->fetchfield('total_photos') + 1;
		$app['db']->freeresult();

		$sql_ary = array(
			'id_row' => $id_gallery,
			'title'  => $gallery['title'] . ' - Фото №' . $total_photos,
			'sort'   => $max_sort,
			'image'  => $filename
		);
		
		$sql = 'INSERT INTO ' . $mysql_table . '_photos ' . $app['db']->build_array('INSERT', $sql_ary);
		$app['db']->query($sql);
		
		$id_photo = (int) $app['db']->insert_id();
		
		if( !$id_photo )
		{
			DeleteImages($dirpath, $filename);
			echo "ERROR: Не удалось сохранить фото в базе данных";	// Return the file id to the script
			exit;
		}
		
		$file_id = "p_" . $id_gallery . "_" . $id_photo;
		$_SESSION["file_info"][$file_id] = $filename;
		echo "FILEID:" . $file_id;
		exit;
	}
}

echo "ERROR: Ошибка загрузки файла";	// Return the file id to the script
exit;

function DeleteImages($dir, $filename)
{
	unlink($dir. 'sm/' . $filename);
	unlink($dir . $filename);
}

?>