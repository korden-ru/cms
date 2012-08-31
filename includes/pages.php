<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

namespace acp;

use acp\models\page;

/**
* Управление страницами
*/
class pages extends page
{
	function __construct()
	{
		parent::__construct();
		
		$this->forms->addButtonText = 'Добавить страницу';
		$this->forms->titleTable    = 'Просмотр страниц';
		$this->forms->set_primary_id('page_id');
	}
	
	/**
	* Список страниц
	*/
	public function index()
	{
		$data = $menu_source = $menu_final = array();
		
		$sql = '
			SELECT
				page_id,
				page_name,
				page_url,
				page_formats,
				page_enabled,
				page_display,
				page_protected,
				page_type,
				is_dir,
				parent_id,
				page_handler,
				handler_method,
				page_redirect,
				1 AS arrows
			FROM
				' . PAGES_TABLE . '
			WHERE
				site_id = ' . $this->db->check_value($this->site_id) . '
			ORDER BY
				left_id ASC';
		$this->db->query($sql);
		
		while( $row = $this->db->fetchrow() ) 
		{
			$menu_source[] = $row;
		}
		
		$this->db->freeresult();
		
		$menu_final = $this->generate_menu($menu_source, 0);
		
		foreach( $menu_final as $key => $row )
		{
			if( $row['page_protected'] == 3 && $this->user->group != 1 )
			{
				continue;
			}
			
			if( $this->user->group == 1 )
			{
				$row['page_url'] = $row['is_dir'] ? sprintf('/%s/', $row['page_url']) : sprintf('%s.%s', $row['page_url'], $row['page_formats']);
			}
			
			$row['page_url']     = mb_strlen($row['page_url']) > 25 ? mb_substr($row['page_url'], 0, 25) . '...' : $row['page_url'];
			$row['page_enabled'] = $row['page_enabled'] ? '<center><img src="images/tick.png" alt=""></center>' : '';
			$row['page_enabled'] = $row['page_enabled'] && $row['page_redirect'] ? '<center><img src="images/road_sign.png" alt="" title="Редирект &rarr; ' . htmlspecialchars($row['page_redirect']) . '"></center>' : $row['page_enabled'];
			$row['page_display'] = 2 == $row['page_display'] ? '<center><img src="images/tick.png" alt=""></center>' : '';
			
			if( $row['page_protected'] >= 1 && $this->user->group != 1 )
			{
				$row['skip_delete'] = true;
			}
			
			if( $row['page_protected'] >= 2 && $this->user->group != 1 )
			{
				$row['skip_edit'] = true;
			}
			
			if( $this->user->group == 1 && $row['page_handler'] )
			{
				$row['page_name'] .= '<br><span style="color: #aaa;">' . $row['page_handler'] . '::' . $row['handler_method'] . '()</span>';
			}

			/**
			* Информация о блоках
			*/
			switch( $row['page_type'] )
			{
				case 1:
				case 2:
				case 3:
				
					$sql = '
						SELECT
							COUNT(*) as total
						FROM
							' . PAGES_TABLE . '_gallery
						WHERE
							id_row = ' . $this->db->check_value($row['page_id']);
					$this->db->query($sql);
					$total = $this->db->fetchfield('total');
					$this->db->freeresult();
					
					$row['add_buttons'][] = sprintf('<a href="%s" class="btn btn-mini btn-block">%s</a>', $this->path_menu . '&class=pages_gallery&pid=' . $row['page_id'], 'Блоки (' . $total . ')');
					
				break;
				// case 4:
				// 
				// 	$sql = '
				// 		SELECT
				// 			COUNT(*) as total
				// 		FROM
				// 			' . PRODUCTS_TABLE . '
				// 		WHERE
				// 			id_row = ' . $this->db->check_value($row['page_id']);
				// 	$this->db->query($sql);
				// 	$total = $this->db->fetchfield('total');
				// 	$this->db->freeresult();
				// 
				// 	$row['add_buttons'][] = sprintf('<a href="%s" class="btn btn-mini btn-block">%s</a>', $this->path_menu . '&class=products&pid=' . $row['page_id'], 'Товары (' . $total . ')');
				// 
				// break;
			}
			
			switch( $row['page_type'] )
			{
				case 0: $row['page_type'] = 'Текстовая'; break;
				case 1: $row['page_type'] = 'Текстовая с галереей'; break;
				case 2: $row['page_type'] = 'Текстовая с блоками'; break;
				case 3: $row['page_type'] = 'Блоки'; break;
				case 4: $row['page_type'] = 'Содержит товары'; break;
			}

			$row['page_type'] = '<center>' . $row['page_type'] . '</center>';
			
			$row['page_name'] .= $this->print_menu($menu_final, $key);
			
			$row['arrows'] = '<center><a href="' . $this->path_menu . '&mode=move_up&id=' . $row['page_id'] . '"><img src="images/arrow_090.png" alt="" title="Переместить выше"></a> <a href="' . $this->path_menu . '&mode=move_down&id=' . $row['page_id'] . '"><img src="images/arrow_270.png" alt="" title="Переместить ниже"></a></center>';

			unset($row['parent_id'], $row['submenu'], $row['page_handler'], $row['handler_method'], $row['page_redirect'], $row['page_formats'], $row['is_dir'], $row['page_protected']);
			
			$data[] = $row;
		}
		
		$this->forms->createShowTMP(array(
			'ID',
			'Название',
			'URL',
			'<center><img src="images/i_show_in_site.png" title="Отображается на сайте?"></center>',
			'<center><span title="Отображается в главном меню?">ГМ</span></center>',
			'Тип страницы',
			'Позиция'
		), $data, array(
			25,
			'',
			100,
			25,
			25,
			100,
			60)
		);
	}

	/**
	* Добавление страницы
	*/
	public function add()
	{
		$page_data = array(
			'site_id'   => $this->site_id,
			'parent_id' => 0,
			'page_name' => 'Новая страница'
		);
		
		$this->update_page_data($page_data);
		$this->remove_cache_file();
		
		redirect($this->forms->U_EDIT . $page_data['page_id']);
	}
	
	/**
	* Удаление
	*/
	public function delete()
	{
		$id     = $this->request->variable('id', 0);
		$submit = $this->request->is_set_post('submit');
		
		$this->delete_page($id);
		$this->remove_cache_file();

		redirect($this->path_menu);
	}

	/**
	* Редактирование
	*/
	public function edit($paths)
	{
		global $app;

		$id     = $this->request->variable('id', 0);
		$submit = $this->request->is_set_post('submit');
		
		$sql = '
			SELECT
				*
			FROM
				' . PAGES_TABLE . '
			WHERE
				page_id = ' . $this->db->check_value($id);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();

		$s_cat_option = '<option value="0"' . (($row['parent_id'] == 0) ? ' selected="selected"' : '') . '>Родительский пункт меню</option>';
		
		$fieldset = array(
			array('type' => 'text',     'name' => 'page_name', 'title' => 'Название страницы', 'value' => $row['page_name'], 'prim' => 'Будет использовано в наименовании пункта меню'),
			array('type' => 'text',     'name' => 'page_url', 'title' => 'URL-адрес страницы', 'value' => $row['page_url'], 'chpu_button' => 'page_name', 'chpu_rus_button' => 'page_name'),
			array('type' => 'text',     'name' => 'page_handler', 'title' => 'Файл-обработчик', 'value' => $row['page_handler'], 'perms' => array(1)),
			array('type' => 'text',     'name' => 'handler_method', 'title' => 'Метод обработчика', 'value' => $row['handler_method'], 'perms' => array(1)),
			array('type' => 'textarea', 'name' => 'page_text', 'title' => 'Текст страницы', 'value' => $row['page_text']),
			
			
			array('type' => 'code', 	'html' => '<fieldset><legend>Поместить в меню</legend>'),
			array('type' => 'select', 	'name' => 'parent_id', 'title' => 'Родительская страница', 'raw' => $s_cat_option . $this->make_page_select($row['parent_id'], $row['page_id'], false, true)),
		);
		
		$sql = '
			SELECT
				*
			FROM
				' . MENUS_TABLE . '
			WHERE
				activation = 1
			ORDER BY
				sort ASC';
		$this->db->query($sql);
		
		while( $menu = $this->db->fetchrow() )
		{
			$fieldset[] = array('type' => 'checkbox', 'name' => 'display_in_menu_' . $menu['id'], 'title' => $menu['title'], 'value' => 1, 'checked' => $row['display_in_menu_' . $menu['id']]);
		}
		
		$this->db->freeresult();
		
		$fieldset = array_merge($fieldset, array(
			array('type' => 'code', 	'html' => '</fieldset>'),
			
			
			array('type' => 'code', 	'html' => '<fieldset><legend>Информация для продвижения сайта</legend>'),
			array('type' => 'select',   'name' => 'page_noindex', 'title' => 'Разрешить индексирование', 'options' => array('Да' => 0, 'Нет' => 1), 'value' => $row['page_noindex']),
			array('type' => 'text', 	'name' => 'page_title', 'title' => 'SEO | Заголовок страницы', 'value' => $row['page_title']),
			array('type' => 'text', 	'name' => 'page_keywords', 'title' => 'SEO | Ключевые слова', 'value' => $row['page_keywords']),
			array('type' => 'text', 	'name' => 'page_description', 'title' => 'SEO | Описание страницы', 'value' => $row['page_description']),
			array('type' => 'button', 	'name' => 'autoins', 'title' => 'Подобрать информацию для продвижения сайта', 'value' => 'Автоподбор', 'onclick' => "AjaxClickFormButton('page_name', ['page_title','page_keywords','page_description'])"),
			array('type' => 'code', 	'html' => '</fieldset>'),
			
			
			array('type' => 'code', 	'html' => '<fieldset><legend>Тип страницы</legend>'),
			array('type' => 'select',   'name' => 'is_dir', 'title' => 'Файл или папка', 'options' => array('Папка' => 1, 'Файл' => 0), 'value' => $row['is_dir'], 'perms' => array(1)),
			array('type' => 'select', 	'name' => 'page_type', 'title' => 'Тип страницы', 'options' => $app['page.types'], 'value' => $row['page_type']),
			array('type' => 'select', 	'name' => 'text_position', 'title' => 'Расположение текста', 'options' => array('Сверху' => 0, 'Снизу' => 1), 'value' => $row['text_position']),
			array('type' => 'text', 	'name' => 'gallery_title', 'title' => 'Наименование галереи', 'value' => $row['gallery_title'], 'prim' => 'для страниц типа «Текстовая с галереей»'),
			array('type' => 'code', 	'html' => '</fieldset>'),
			
			
			array('type' => 'code', 	'html' => '<fieldset><legend>Дополнительные настройки</legend>'),
			array('type' => 'checkbox', 'name' => 'page_enabled', 'title' => 'Отображается НА САЙТЕ?', 'value' => 1, 'checked' => $row['page_enabled']),
			array('type' => 'select',   'name' => 'page_display', 'title' => 'Отображается В ГЛАВНОМ МЕНЮ?', 'options' => array('Нет' => 0, 'Да (глобально)' => 2, 'Да (только в подразделах)' => 1), 'value' => $row['page_display']),
			array('type' => 'select', 'name' => 'page_protected', 'title' => 'Защитить страницу в системе управления', 'options' => array('Нет' => 0, 'От удаления' => 1, 'От редактирования' => 2, 'От просмотра' => 3), 'value' => $row['page_protected'], 'perms' => array(1)),
			array('type' => 'text',     'name' => 'page_redirect', 'title' => 'Редирект', 'value' => $row['page_redirect'], 'prim' => 'Для осуществления редиректа у страницы не должен быть назначен файл- и метод-обработчики', 'perms' => array(1)),
			array('type' => 'code', 	'html' => '</fieldset>')
		));

		if( $submit )
		{
			$row = $this->get_page_row($id);
			$parent_id = $this->request->variable('parent_id', 0);

			if( $row['parent_id'] != $parent_id )
			{
				/* Балансировка дерева */
				$this->move_page($id, $parent_id);
			}

			$this->forms->saveIntoDB($fieldset);
			$this->remove_cache_file();
			
			redirect($this->path_menu);
		}

		$this->forms->createEditTMP($fieldset);
	}
	
	/**
	* Перемещение страницы вниз
	*/
	public function move_down()
	{
		$id = $this->request->variable('id', 0);
		
		$row = $this->get_page_row($id);

		if( !$row )
		{
			trigger_error('NO_PAGE', E_USER_WARNING);
		}

		$this->move_page_by($row, 'move_down', 1);
		
		redirect($this->path_menu);
	}
	
	/**
	* Перемещение страницы вверх
	*/
	public function move_up()
	{
		$id = $this->request->variable('id', 0);
		
		$row = $this->get_page_row($id);

		if( !$row )
		{
			trigger_error('NO_PAGE', E_USER_WARNING);
		}

		$this->move_page_by($row, 'move_up', 1);
		
		redirect($this->path_menu);
	}
	
	private function generate_menu($menu_source, $parent_id = 0)
	{
		$menu = array();
		
		foreach( $menu_source as $k => $v )
		{
			if( $v['parent_id'] == $parent_id )
			{
				$menu[$v['page_id']] = $v;
				$menu[$v['page_id']]['submenu'] = array();
				
				//есть ли подменю
				$flag = false;
				foreach( $menu_source as $kk => $vv )
				{
					if( $vv['parent_id'] == $v['page_id'] )
					{	
						$flag = true;
						break;
					}
				}
				
				if( $flag )
				{
					$menu[$v['page_id']]['submenu'] = $this->generate_menu($menu_source, $v['page_id']);
				}
			}
		}
		
		return $menu;
	}
	
	private function reqursive_print_menu($mas, $offset = 10)
	{
		$str = '';
		
		if( !count($mas) )
		{
			return '';
		}
		
		foreach( $mas as $k => $v )
		{
			if( $v['page_protected'] == 3 && $this->user->group != 1 )
			{
				continue;
			}
			
			$link_e    = $this->config['acp.root_path'] . '?tab=1&menu=4&mode=edit&id=' . $v['page_id'];
			$link_d    = $this->config['acp.root_path'] . '?tab=1&menu=4&mode=delete&id=' . $v['page_id'];
			$link_down = $this->config['acp.root_path'] . '?tab=1&menu=4&mode=move_down&id=' . $v['page_id'];
			$link_up   = $this->config['acp.root_path'] . '?tab=1&menu=4&mode=move_up&id=' . $v['page_id'];
			
			$str .= '<div style="position: relative; font-size: 12px; margin:2px 5px 2px ' . $offset . 'px; padding: 5px 105px 5px 5px; border: 1px solid #a5a5a5; background-color: #eee; border-radius: 3px;">';
			$str .= $v['page_name'];
			
			if( $this->user->group == 1 && $v['page_handler'] )
			{
				$str .= '<br><span style="color: #aaa;">' . $v['page_handler'] . '::' . $v['handler_method'] . '()</span>';
			}
			
			$str .= '<div style="position:absolute; right: 10px; top: 2px; z-index: 20; text-align: right;width:100px;">';
			$str .= '<a href="' . $link_up . '"><img src="images/arrow_090.png" alt="" title="Переместить выше"></a> ';
			$str .= '<a href="' . $link_down . '"><img src="images/arrow_270.png" alt="" title="Переместить ниже"></a> ';
			$str .= $v['page_protected'] < 2 || $this->user->group == 1 ? '<a href="'.$link_e.'"><img src="images/pencil.png" title="Редактировать"></a> ' : '';
			if ($v['page_type'] != 0)
			{
				if ($v['page_type'] != 5)
				{
					$link_p = $this->config['acp.root_path'] . '?tab=1&menu=4&class=pages_gallery&pid=' . $v['page_id'];
					$str .= '<a href="'.$link_p.'"><img src="images/_photo.png"></a> ';
				}
				else 
				{
					$link_p = $this->config['acp.root_path'] . '?tab=1&menu=4&class=products&pid=' . $v['page_id'];
					$str .= '<a href="'.$link_p.'"><img src="images/_block.png"></a> ';
				}
			}
			$str .= $v['page_protected'] < 1 || $this->user->group == 1 ? '<a href="'.$link_d.'" onclick="return confirm(\'Будет удалена вся информация связанная с этой записью! Продолжить?\');"><img src="images/cross_script.png" title="Удалить"></a>' : '';
			$str .= '</div></div>';	
			$str .= $this->reqursive_print_menu($v['submenu'], $offset + 20);
		}
		
		return $str;
	}

	function print_menu($menu, $id)
	{
		foreach( $menu as $k => $v )
		{
			if( $k == $id )
			{
				return $this->reqursive_print_menu($v['submenu']);
				break;
			}
		}
		
		return '';
	}
	
	/**
	* Удаление страницы
	*/
	private function delete_page($page_id)
	{
		$row = $this->get_page_row($page_id);

		$branch = $this->get_page_branch($page_id, 'children', 'descending', false);

		if( sizeof($branch) )
		{
			return array('CANNOT_REMOVE_PAGE');
		}

		$diff = 2;
		$sql = '
			DELETE
			FROM
				' . PAGES_TABLE . '
			WHERE
				site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				page_id = ' . $page_id;
		$this->db->query($sql);

		$row['right_id'] = (int) $row['right_id'];
		$row['left_id'] = (int) $row['left_id'];

		/* Синхронизация дерева */
		$sql = '
			UPDATE
				' . PAGES_TABLE . '
			SET
				right_id = right_id - ' . $diff . '
			WHERE
				site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				left_id < ' . $row['right_id'] . '
			AND
				right_id > ' . $row['right_id'];
		$this->db->query($sql);

		$sql = '
			UPDATE
				' . PAGES_TABLE . '
			SET
				left_id = left_id - ' . $diff . ',
				right_id = right_id - ' . $diff . '
			WHERE
				site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				left_id > ' . $row['right_id'];
		$this->db->query($sql);

		return array();
	}

	/**
	* Данные раздела (ветви дерева страниц)
	*/
	public function get_page_branch($page_id, $type = 'all', $order = 'descending', $include_self = true)
	{
		switch( $type )
		{
			case 'parents':  $condition = 'p1.left_id BETWEEN p2.left_id AND p2.right_id'; break;
			case 'children': $condition = 'p2.left_id BETWEEN p1.left_id AND p1.right_id'; break;
			default:         $condition = 'p2.left_id BETWEEN p1.left_id AND p1.right_id OR p1.left_id BETWEEN p2.left_id AND p2.right_id';
		}

		$rows = array();

		$sql = '
			SELECT
				p2.*
			FROM
				' . PAGES_TABLE . ' p1
			LEFT JOIN
				' . PAGES_TABLE . ' p2 ON (' . $condition . ')
			WHERE
				p1.site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				p2.site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				p1.page_id = ' . $this->db->check_value($page_id) . '
			ORDER BY
				p2.left_id ' . (($order == 'descending') ? 'ASC' : 'DESC');
		$this->db->query($sql);

		while( $row = $this->db->fetchrow() )
		{
			if( !$include_self && $row['page_id'] == $page_id )
			{
				continue;
			}

			$rows[] = $row;
		}

		$this->db->freeresult();

		return $rows;
	}

	/**
	* Данные страницы
	*/
	private function get_page_row($page_id)
	{
		$sql = '
			SELECT
				*
			FROM
				' . PAGES_TABLE . '
			WHERE
				site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				page_id = ' . $this->db->check_value($page_id);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();

		if( !$row )
		{
			trigger_error('NO_PAGE', E_USER_WARNING);
		}

		return $row;
	}
	
	/**
	* Список страниц (Jumpbox)
	*/
	private function make_page_select($select_id = false, $ignore_id = false, $ignore_emptycat = true, $ignore_noncat = false)
	{
		$sql = '
			SELECT
				page_id,
				parent_id,
				left_id,
				right_id,
				is_dir,
				page_enabled,
				page_name,
				page_handler
			FROM
				' . PAGES_TABLE . '
			WHERE
				site_id = ' . $this->db->check_value($this->site_id) . '
			ORDER BY
				left_id ASC';
		$this->db->query($sql);

		$right = 0;
		$padding_store = array('0' => '');
		$page_list = $padding = '';

		while( $row = $this->db->fetchrow() )
		{
			if( $row['left_id'] < $right )
			{
				$padding .= '&nbsp; &nbsp;';
				$padding_store[$row['parent_id']] = $padding;
			}
			elseif( $row['left_id'] > $right + 1 )
			{
				$padding = isset($padding_store[$row['parent_id']]) ? $padding_store[$row['parent_id']] : '';
			}

			$right = $row['right_id'];

			/* Пропускаем ненужные страницы */
			if( (is_array($ignore_id) && in_array($row['page_id'], $ignore_id)) || $row['page_id'] == $ignore_id )
			{
				continue;
			}

			/* Пустые папки */
			if( $row['is_dir'] && ($row['left_id'] + 1 == $row['right_id']) && $ignore_emptycat )
			{
				continue;
			}
			
			/* Пропускаем страницы, оставляем только папки */
			if( !$row['is_dir'] && $ignore_noncat )
			{
				continue;
			}

			$selected = ( is_array($select_id) ) ? ((in_array($row['page_id'], $select_id)) ? ' selected="selected"' : '') : (($row['page_id'] == $select_id) ? ' selected="selected"' : '');

			$page_list .= '<option value="' . $row['page_id'] . '"' . $selected . ((!$row['page_enabled']) ? ' class="disabled"' : '') . '>' . $padding . $row['page_name'] . '</option>';
		}

		$this->db->freeresult();

		unset($padding_store);

		return $page_list;
	}

	/**
	* Перемещение страницы в дереве
	*/
	private function move_page($from_page_id, $to_parent_id)
	{
		$moved_pages = $this->get_page_branch($from_page_id, 'children', 'descending');
		$from_data = $moved_pages[0];
		$diff = sizeof($moved_pages) * 2;

		$moved_ids = array();
		for( $i = 0, $len = sizeof($moved_pages); $i < $len; ++$i )
		{
			$moved_ids[] = $moved_pages[$i]['page_id'];
		}

		/* Синхронизация родителей */
		$sql = '
			UPDATE
				' . PAGES_TABLE . '
			SET
				right_id = right_id - ' . $diff . '
			WHERE
				site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				left_id < ' . (int) $from_data['right_id'] . '
			AND
				right_id > ' . (int) $from_data['right_id'];
		$this->db->query($sql);

		/* Синхронизация правой части дерева */
		$sql = '
			UPDATE
				' . PAGES_TABLE . '
			SET
				left_id = left_id - ' . $diff . ',
				right_id = right_id - ' . $diff . '
			WHERE
				site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				left_id > ' . (int) $from_data['right_id'];
		$this->db->query($sql);

		if( $to_parent_id > 0 )
		{
			$to_data = $this->get_page_row($to_parent_id);

			/* Синхронизация новых родителей */
			$sql = '
				UPDATE
					' . PAGES_TABLE . '
				SET
					right_id = right_id + ' . $diff . '
				WHERE
					site_id = ' . $this->db->check_value($this->site_id) . '
				AND
					' . (int) $to_data['right_id'] . ' BETWEEN left_id AND right_id
				AND
					' . $this->db->in_set('page_id', $moved_ids, true);
			$this->db->query($sql);

			/* Синхронизация правой части дерева */
			$sql = '
				UPDATE
					' . PAGES_TABLE . '
				SET
					left_id = left_id + ' . $diff . ',
					right_id = right_id + ' . $diff . '
				WHERE
					site_id = ' . $this->db->check_value($this->site_id) . '
				AND
					left_id > ' . (int) $to_data['right_id'] . '
				AND
					' . $this->db->in_set('page_id', $moved_ids, true);
			$this->db->query($sql);

			/* Синхронизация перемещенной ветви */
			$to_data['right_id'] += $diff;
			
			if( $to_data['right_id'] > $from_data['right_id'] )
			{
				$diff = '+ ' . ($to_data['right_id'] - $from_data['right_id'] - 1);
			}
			else
			{
				$diff = '- ' . abs($to_data['right_id'] - $from_data['right_id'] - 1);
			}
		}
		else
		{
			$sql = '
				SELECT
					MAX(right_id) AS right_id
				FROM
					' . PAGES_TABLE . '
				WHERE
					site_id = ' . $this->db->check_value($this->site_id) . '
				AND
					' . $this->db->in_set('page_id', $moved_ids, true);
			$this->db->query($sql);
			$row = $this->db->fetchrow();
			$this->db->freeresult();

			$diff = '+ ' . (int) ($row['right_id'] - $from_data['left_id'] + 1);
		}

		$sql = '
			UPDATE
				' . PAGES_TABLE . '
			SET
				left_id = left_id ' . $diff . ',
				right_id = right_id ' . $diff . '
			WHERE
				site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				' . $this->db->in_set('page_id', $moved_ids);
		$this->db->query($sql);
	}

	/**
	* Перемещение страницы на $steps уровней вверх/вниз
	*/
	private function move_page_by($page_row, $action = 'move_up', $steps = 1)
	{
		/**
		* Fetch all the siblings between the page's current spot
		* and where we want to move it to. If there are less than $steps
		* siblings between the current spot and the target then the
		* page will move as far as possible
		*/
		$sql = '
			SELECT
				page_id,
				left_id,
				right_id,
				page_name
			FROM
				' . PAGES_TABLE . '
			WHERE
				site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				parent_id = ' . (int) $page_row['parent_id'] . '
			AND
				' . (($action == 'move_up') ? 'right_id < ' . (int) $page_row['right_id'] . ' ORDER BY right_id DESC' : 'left_id > ' . (int) $page_row['left_id'] . ' ORDER BY left_id ASC') . '
			LIMIT
				0, ' . $steps;
		$this->db->query($sql);
		$target = array();

		while( $row = $this->db->fetchrow() )
		{
			$target = $row;
		}

		$this->db->freeresult();

		if( !sizeof($target) )
		{
			/* Страница уже в самом верху или низу дерева */
			return false;
		}

		/**
		* $left_id and $right_id define the scope of the nodes that are affected by the move.
		* $diff_up and $diff_down are the values to substract or add to each node's left_id
		* and right_id in order to move them up or down.
		* $move_up_left and $move_up_right define the scope of the nodes that are moving
		* up. Other nodes in the scope of ($left_id, $right_id) are considered to move down.
		*/
		if( $action == 'move_up' )
		{
			$left_id = (int) $target['left_id'];
			$right_id = (int) $page_row['right_id'];

			$diff_up = (int) ($page_row['left_id'] - $target['left_id']);
			$diff_down = (int) ($page_row['right_id'] + 1 - $page_row['left_id']);

			$move_up_left = (int) $page_row['left_id'];
			$move_up_right = (int) $page_row['right_id'];
		}
		else
		{
			$left_id = (int) $page_row['left_id'];
			$right_id = (int) $target['right_id'];

			$diff_up = (int) ($page_row['right_id'] + 1 - $page_row['left_id']);
			$diff_down = (int) ($target['right_id'] - $page_row['right_id']);

			$move_up_left = (int) ($page_row['right_id'] + 1);
			$move_up_right = (int) $target['right_id'];
		}

		$sql = '
			UPDATE
				' . PAGES_TABLE . '
			SET
				left_id = left_id + CASE
					WHEN left_id BETWEEN ' . $move_up_left . ' AND ' . $move_up_right . '
					THEN -' . $diff_up . '
					ELSE ' . $diff_down . '
				END,
				right_id = right_id + CASE
					WHEN right_id BETWEEN ' . $move_up_left . ' AND ' . $move_up_right . '
					THEN -' . $diff_up . '
					ELSE ' . $diff_down . '
				END
			WHERE
				site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				left_id BETWEEN ' . $left_id . ' AND ' . $right_id . '
			AND
				right_id BETWEEN ' . $left_id . ' AND ' . $right_id;
		$this->db->query($sql);
		$this->remove_cache_file();

		return $target['page_name'];
	}
	
	/**
	* Удаление кэша страниц
	*/
	private function remove_cache_file()
	{
		$site_info = get_site_info_by_id($this->site_id);
		
		$sql = '
			SELECT
				*
			FROM
				' . MENUS_TABLE;
		$this->db->query($sql);
		
		while( $row = $this->db->fetchrow() )
		{
			$this->cache->_delete(sprintf('%s_menu_%d_%s', $site_info['domain'], $row['id'], $site_info['language']));
		}
		
		$this->db->freeresult();
		
		$this->cache->_delete(sprintf('%s_handlers_%s', $site_info['domain'], $site_info['language']));
		$this->cache->_delete(sprintf('%s_menu_%s', $site_info['domain'], $site_info['language']));
	}
	
	/**
	* Обновление/создание страницы
	*
	* @param bool $run_inline Если true, то возвращать ошибки, а не останавливать работу
	*/
	private function update_page_data(&$page_data, $run_inline = false)
	{
		if( !isset($page_data['page_id']) )
		{
			/* Если page_id не указан, то создаем новую страницу */
			if( $page_data['parent_id'] )
			{
				$sql = '
					SELECT
						left_id,
						right_id
					FROM
						' . PAGES_TABLE . '
					WHERE
						site_id = ' . $this->db->check_value($this->site_id) . '
					AND
						page_id = ' . (int) $page_data['parent_id'];
				$this->db->query($sql);
				$row = $this->db->fetchrow();
				$this->db->freeresult();

				if( !$row )
				{
					if( $run_inline )
					{
						return 'PARENT_NO_EXIST';
					}

					trigger_error('PARENT_NOT_EXIST', E_USER_WARNING);
				}

				// Workaround
				$row['left_id'] = (int) $row['left_id'];
				$row['right_id'] = (int) $row['right_id'];

				$sql = '
					UPDATE
						' . PAGES_TABLE . '
					SET
						left_id = left_id + 2,
						right_id = right_id + 2
					WHERE
						site_id = ' . $this->db->check_value($this->site_id) . '
					AND
						left_id > ' . $row['right_id'];
				$this->db->query($sql);

				$sql = '
					UPDATE
						' . PAGES_TABLE . '
					SET
						right_id = right_id + 2
					WHERE
						site_id = ' . $this->db->check_value($this->site_id) . '
					AND
						' . $row['left_id'] . ' BETWEEN left_id AND right_id';
				$this->db->query($sql);

				$page_data['left_id'] = (int) $row['right_id'];
				$page_data['right_id'] = (int) $row['right_id'] + 1;
			}
			else
			{
				$sql = '
					SELECT
						MAX(right_id) AS right_id
					FROM
						' . PAGES_TABLE . '
					WHERE
						site_id = ' . $this->db->check_value($this->site_id);
				$this->db->query($sql);
				$row = $this->db->fetchrow();
				$this->db->freeresult();

				$page_data['left_id'] = (int) $row['right_id'] + 1;
				$page_data['right_id'] = (int) $row['right_id'] + 2;
			}

			$sql = 'INSERT INTO ' . PAGES_TABLE . ' ' . $this->db->build_array('INSERT', $page_data);
			$this->db->query($sql);

			$page_data['page_id'] = $this->db->insert_id();
		}
		else
		{
			$row = $this->get_page_row($page_data['page_id']);

			if( $page_data['is_dir'] && !$row['is_dir'] )
			{
				/* Нельзя сделать папку страницей */
				$branch = $this->get_page_branch($page_data['page_id'], 'children', 'descending', false);

				if( sizeof($branch) )
				{
					return array('NO_DIR_TO_PAGE');
				}
			}

			if( $row['parent_id'] != $page_data['parent_id'] )
			{
				$this->move_page($page_data['page_id'], $page_data['parent_id']);
			}

			$update_ary = $page_data;
			unset($update_ary['page_id']);

			$sql = '
				UPDATE
					' . PAGES_TABLE . '
				SET
					' . $this->db->build_array('UPDATE', $update_ary) . '
				WHERE
					site_id = ' . $this->db->check_value($this->site_id) . '
				AND
					page_id = ' . (int) $page_data['page_id'];
			$this->db->query($sql);
		}

		return array();
	}
}
