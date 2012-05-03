<?php

namespace acp;

session_start();

define('IN_ACP', 0x000001);

$_SESSION['user_login'] = isset($_SESSION['user_login']) ? $_SESSION['user_login'] : '';
$_SESSION['user_passwd'] = isset($_SESSION['user_passwd']) ? $_SESSION['user_passwd'] : '';

require(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/bootstrap.php');

/* Login section
-------------------------------*/
$login	= $request->is_set_post('loginsubmit');
$logout	= $request->is_set('logoutsubmit');
	
if( $logout )
{
	$user->logout();
	$_SESSION['fckeditor'] = '';
	redirect('/');
}

$template->assign(array(
	'LOGIN'     => '',
	'USER_ROLE' => 0
));

if( false === $user->check() && $login )
{
	$res = $user->login();
	
	switch( $user->login() )
	{
		case 'login_already':
		
			$template->assign(array(
				'LOGIN'     => $_COOKIE['login'],
				'USER_ROLE' => $user->role
			));

			$_SESSION['fckeditor'] = $app['config']['fckeditor.secret'];
			
		break;
		case 'login_fail':
		
			$template->assign('ERROR', 'Неверный логин или пароль');
		
		break;
		case 'login_activate':
		
			$template->assign('ERROR', 'Ваш аккаунт не активирован');
		
		break;
		default:

			$template->assign(array(
				'LOGIN'     => $_COOKIE['login'],
				'USER_ROLE' => $user->role
			));
			$_SESSION['fckeditor'] = $app['config']['fckeditor.secret'];
	}
	
	redirect($config['acp.root_path']);
}
else
{
	$template->assign(array(
		'LOGIN'     => $_COOKIE['login'],
		'USER_ROLE' => $user->role
	));
	
	$_SESSION['fckeditor'] = $app['config']['fckeditor.secret'];
}

$tab = $request->variable('tab', 1);
$menu = $request->variable('menu', '');
$mode = $class = '';

/* Permissions - get user GROUP permissions
-----------------------------------------------*/
if(isset($user->id) && $user->id > 0)
{
	$q = 'SELECT title, permissions
		FROM '.SQL_PREFIX.'users_groups
		WHERE id = '.$user->group;
	$r = $app['db']->query($q);
	if($r->num_rows < 1)
		exit('User group not found!');
	
	$s = $app['db']->fetchrow($r);
	$userPerms = $s['permissions'];
	$template->assign('userLevel', $s['title']);
	
	$user->userPerms = explode(',', $userPerms);
	
}
else
	$user->userPerms = array();

if($menu && (in_array($menu, $user->userPerms) || (isset($user->userPerms[0]) && $user->userPerms[0] == 'ALL')))
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

$mode   = $request->variable('mode', $mode);
$class  = $request->variable('class', $class);

$paths['menu_path']   = $config['acp.root_path'] . '?tab=' . $tab . '&menu=' . $menu;
$paths['module_path'] = $config['acp.root_path'] . '?tab=' . $tab . '&menu=' . $menu;
$paths['class_path']  = $config['acp.root_path'] . '?tab=' . $tab . '&menu=' . $menu . '&class=' . $class;
$paths['mode_path']   = $config['acp.root_path'] . '?tab=' . $tab . '&menu=' . $menu . '&class=' . $class . '&mode=' . $mode;

$template->assign('ACTIVE_MENU', $paths['mode_path']);
$template->assign('cms_version', $app::VERSION);

if($user->id && $user->role >= 4)
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
			$template->assign('ERROR', 'Метод ' . $mode . ' не найден в классе ' . $class_name);
		}
	}
	
	require(SITE_DIR . '/acp/includes/acp.php');
	$template->display('acp.html');
}
elseif( $user->id && $user->role < 4 )
{
	$template->assign('ERROR', 'Вашего уровня доступа недостаточно для входа в админцентр');
	$template->display('login.html');
}
else
{
	$template->display('login.html');
}
