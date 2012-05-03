<?php
/**
*
* @package src.ivacuum.ru
* @copyright (c) 2012 vacuum
*
*/

namespace acp\models;

use engine\models\page as base_page;

/**
* Модуль системы управления
*/
class page extends base_page
{
	public $class_name;
	public $form;
	public $path_class;
	public $path_menu;
	public $path_mode;
	public $path_module;
	
	function __construct()
	{
		global $app, $paths;
		
		parent::__construct();
		
		$this->config   = $app['config'];
		$this->db       = $app['db'];
		$this->request  = $app['request'];
		$this->template = $app['template'];
		$this->user     = $app['user'];
		
		$class_name = get_class($this);
		$class_name = mb_substr($class_name, strrpos($class_name, '\\') + 1);
		
		$this->class_name  = $class_name;
		$this->path_class  = $paths['class_path'];
		$this->path_menu   = $paths['menu_path'];
		$this->path_mode   = $paths['mode_path'];
		$this->path_module = $paths['module_path'];
		
		$id  = $this->request->variable('id', 0);
		$pid = $this->request->variable('pid', 0);

		$this->form = new \engine\core\forms($app['template']);
		$this->form->addButton     = true;
		$this->form->addButtonText = 'Добавить';
		$this->form->table_row_id  = $id;

		$this->form->table_name    = SQL_PREFIX . $class_name;
		$this->form->upload_folder = $class_name;
		
		if( $pid )
		{
			$this->form->U_ACTION = $paths['class_path'] . '&mode=edit&pid=' . $pid . '&id=' . $id;
			$this->form->U_EDIT   = $paths['class_path'] . '&mode=edit&pid=' . $pid . '&id=';
			$this->form->U_DEL    = $paths['class_path'] . '&mode=delete&pid=' . $pid . '&id=';
			
			$this->template->assign('U_ADD', $this->path_class . '&mode=add&pid=' . $pid);
		}
		else
		{
			$this->form->U_ACTION = $paths['menu_path'] . '&mode=edit&id=' . $id;
			$this->form->U_EDIT   = $paths['class_path'] . '&mode=edit&id=';
			$this->form->U_DEL    = $paths['class_path'] . '&mode=delete&id=';
			
			$this->template->assign('U_ADD', $this->path_menu . '&mode=add');
		}
	}
	
	/**
	* Количество записей в таблице
	*/
	protected function get_entries_count()
	{
		$sql = '
			SELECT
				COUNT(*) AS total
			FROM
				' . $this->form->table_name;
		$this->db->query($sql);
		$total = $this->db->fetchfield('total');
		$this->db->freeresult();
		
		return $total;
	}
}
