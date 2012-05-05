<?php

namespace acp;

session_start();

define('IN_ACP', 0x000001);

require(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/bootstrap.php');

/* Login section
-------------------------------*/
$login	= $app['request']->is_set_post('loginsubmit');
$logout	= $app['request']->is_set('logoutsubmit');
	
if( $logout )
{
	$app['user']->logout();
	$_SESSION['fckeditor'] = '';
	redirect('/');
}

$app['template']->assign(array(
	'LOGIN'     => '',
	'USER_ROLE' => 0
));

if( false === $app['user']->check() && $login )
{
	switch( $app['user']->login() )
	{
		case 'login_already':
		
			$app['template']->assign(array(
				'LOGIN'     => $_COOKIE['login'],
				'USER_ROLE' => $app['user']->role
			));

			$_SESSION['fckeditor'] = $app['config']['fckeditor.secret'];
			
		break;
		case 'login_fail':
		
			$app['template']->assign('ERROR', 'Неверный логин или пароль');
		
		break;
		case 'login_activate':
		
			$app['template']->assign('ERROR', 'Ваш аккаунт не активирован');
		
		break;
		default:

			$app['template']->assign(array(
				'LOGIN'     => $_COOKIE['login'],
				'USER_ROLE' => $app['user']->role
			));
			$_SESSION['fckeditor'] = $app['config']['fckeditor.secret'];
	}
	
	redirect($app['config']['acp.root_path']);
}
else
{
	$app['template']->assign(array(
		'LOGIN'     => $_COOKIE['login'],
		'USER_ROLE' => $app['user']->role
	));
	
	$_SESSION['fckeditor'] = $app['config']['fckeditor.secret'];
}

$tab = $app['request']->variable('tab', 1);
$menu = $app['request']->variable('menu', '');
$mode = $class = '';

/* Permissions - get user GROUP permissions
-----------------------------------------------*/
if(isset($app['user']->id) && $app['user']->id > 0)
{
	$q = 'SELECT title, permissions
		FROM '.SQL_PREFIX.'users_groups
		WHERE id = '.$app['user']->group;
	$r = $app['db']->query($q);
	if($r->num_rows < 1)
		exit('User group not found!');
	
	$s = $app['db']->fetchrow($r);
	$userPerms = $s['permissions'];
	$app['template']->assign('userLevel', $s['title']);
	
	$app['user']->userPerms = explode(',', $userPerms);
	
}
else
	$app['user']->userPerms = array();

if($menu && (in_array($menu, $app['user']->userPerms) || (isset($app['user']->userPerms[0]) && $app['user']->userPerms[0] == 'ALL')))
{
	$sql='SELECT * FROM '.SQL_PREFIX.'modules WHERE id='.$menu;
	$result=$app['db']->query($sql);
	$row=$app['db']->fetchrow($result);
	$app['db']->freeresult();
	if($row)
	{
		$mode=$row['mode'];
		$class=$row['class'];
	}
}

$mode   = $app['request']->variable('mode', $mode);
$class  = $app['request']->variable('class', $class);

$paths['menu_path']   = $app['config']['acp.root_path'] . '?tab=' . $tab . '&menu=' . $menu;
$paths['module_path'] = $app['config']['acp.root_path'] . '?tab=' . $tab . '&menu=' . $menu;
$paths['class_path']  = $app['config']['acp.root_path'] . '?tab=' . $tab . '&menu=' . $menu . '&class=' . $class;
$paths['mode_path']   = $app['config']['acp.root_path'] . '?tab=' . $tab . '&menu=' . $menu . '&class=' . $class . '&mode=' . $mode;

$app['template']->assign('ACTIVE_MENU', $paths['mode_path']);
$app['template']->assign('cms_version', $app::VERSION);

if($app['user']->id && $app['user']->role >= 4)
{
	if( $class )
	{
		$class_name = '\\acp\\' . $class;
		$handler = new $class_name();
		$handler->_set_cache($app['cache'])
			->_set_config($app['config'])
			->_set_db($app['db'])
			->_set_request($app['request'])
			->_set_template($app['template'])
			->_set_user($app['user']);
		
		if( method_exists($handler, $mode) )
		{
			require(SITE_DIR . '/acp/includes/acp.php');
			
			/* Предустановки */
			if( method_exists($handler, '_setup') )
			{
				call_user_func(array($handler, '_setup'));
			}

			call_user_func(array($handler, $mode));

			$handler->page_header();
			$handler->page_footer();
		}
		else
		{
			$app['template']->assign('ERROR', 'Метод ' . $mode . ' не найден в классе ' . $class_name);
		}
	}
	
	require(SITE_DIR . '/acp/includes/acp.php');
	$app['template']->display('acp.html');
}
elseif( $app['user']->id && $app['user']->role < 4 )
{
	$app['template']->assign('ERROR', 'Вашего уровня доступа недостаточно для входа в админцентр');
	$app['template']->display('login.html');
}
else
{
	$app['template']->display('login.html');
}
