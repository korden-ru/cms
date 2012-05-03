<?php 

define('IN_ACP', 0x000001);

require(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/bootstrap.php');

$ip = $app['request']->variable('ip', '');

if( !$ip )
{
	exit('Данные отсутствуют');
}

$xml_data = simplexml_load_file("http://ipgeobase.ru:7020/geo?ip=$ip");

if( $xml_data && isset($xml_data->ip->city) )
{
	$text = '<table class="table_tip">';
	$text .= '
	<tr><td width=70>Код страны:</td><td><b>' . $xml_data->ip->country . '</b></td></tr>
	<tr><td>Город:</td><td><b>' . $xml_data->ip->city . '</b></td></tr>
	<tr><td>Область:</td><td><b>' . $xml_data->ip->region . '</b></td></tr>
	<tr><td>Округ:</td><td><b>' . $xml_data->ip->district . '</b></td></tr>
	';
	$text .= '</table>
	<p style="font-size: .8em !important; margin-top: 7px;"><a href="http://ip-whois.net/ip_geo.php?ip=' . $ip . '" target="_blank">Больше информации »</a></p>
	';
	
	exit($text);
}

exit('Данные отсутствуют');

?>