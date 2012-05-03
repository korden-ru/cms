<?php

define('IN_ACP', 0x000001);

require(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/bootstrap.php');

if( !$request->is_set_post('table') || !$request->is_ajax )
{
	exit;
}

$table = $request->post('table', '');

//существует ли такая таблица и поле sort в ней
$r = $app['db']->query("SHOW TABLES LIKE '$table'");
if (!($s = $app['db']->fetchrow($r)))
	exit('Table not found');
	
$r = $app['db']->query("SHOW COLUMNS FROM `$table` LIKE 'sort'");
if (!($s = $app['db']->fetchrow($r)))
	exit('Column «sort» not found');
	
if(!isset($_POST['vorders']))
	exit('VORDERS not found');

$tmp = array(); 		// промежуточный массив
$idList = array(); 		// здесь будет массив из id строк, id строки - это id раздела сущности в бд
$valueList = array(); 	// здесь будет массив из новых порядковых номеров

// пилим сериалайз, его можно увидеть alert($(признак_таблицы).tableDnDSerialize())
$vorders = explode('&', $_POST['vorders']);
foreach($vorders as $key=>$val)
{
	// нужно допилить сериалайз, на выходе будет массив array(order=>id)
	list(, $v ) = explode('=', $val);
	if (!empty($v) && intval($v))
		$tmp[] = $v;
}

if (count($tmp))
{
	$idList = implode(',', array_values($tmp)); 	// готовим список id вида 1,2,3,4
	$valueList = implode(',', array_keys($tmp)); 	// готовим список "порядков" вида 3,1,2,4

	$app['db']->query("UPDATE `$table` SET sort = ELT(FIELD(id, $idList), $valueList) WHERE id IN ($idList)");
}

exit("Sort success");

?>