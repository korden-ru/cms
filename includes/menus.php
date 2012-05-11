<?php

namespace acp;

use acp\models\page;

/**
* Меню
*/
class menus extends page
{
	function __construct()
	{
		parent::__construct();

		$this->form->addButtonText = 'Добавить новое меню';
		$this->form->sortElements  = true;
		$this->form->titleTable    = 'Меню';
	}
	
	public function index()
	{
		$sql = '
			SELECT
				id,
				title,
				activation,
				sort
			FROM
				' . $this->form->table_name . '
			ORDER BY
				sort ASC';
		$this->db->query($sql);
		$data = array();
		
		while( $row = $this->db->fetchrow() )
		{
			$row['activation'] = ( $row['activation'] ) ? '<center><img src="images/tick.png" alt=""></center>' : '';
			
			$data[] = $row;
		}
		
		$this->db->freeresult();

		$this->form->createShowTMP(array(
			'ID',
			'Название',
			'<center><img src="images/i_show_in_site.png" title="Отображается на сайте?" alt=""></center>
		'), $data, array(
			25,
			'',
			25
		));
	}
	
	public function add()
	{
		$sql = '
			SELECT
				COUNT(id) AS total,
				MAX(sort) AS max_sort
			FROM
				' . $this->form->table_name;
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		$sql_ary = array(
			'title' => 'Новое меню №' . ($row['total'] + 1),
			'sort'  => $row['max_sort'] + 1
		);
		
		$sql = 'INSERT INTO ' . $this->form->table_name . ' ' . $this->db->build_array('INSERT', $sql_ary);
		$this->db->query($sql);
		
		$menu_id = $this->db->insert_id();
		
		$sql = 'ALTER TABLE ' . PAGES_TABLE . ' ADD display_in_menu_' . $menu_id . ' tinyint(1) UNSIGNED DEFAULT \'0\' NOT NULL';
		$this->db->query($sql);
		$this->remove_cache();
		
		redirect($this->form->U_EDIT . $menu_id);
	}
	
	public function delete()
	{
		$id = $this->request->variable('id', 0);
		
		$sql = '
			DELETE
			FROM
				' . $this->form->table_name . '
			WHERE
				id = ' . $this->db->check_value($id);
		$this->db->query($sql);
		
		$sql = 'ALTER TABLE ' . PAGES_TABLE . ' DROP COLUMN display_in_menu_' . $id;
		$this->db->query($sql);
		$this->remove_cache();
		
		redirect($this->path_class);
	}
	
	public function edit()
	{
		$id     = $this->request->variable('id', 0);
		$submit = $this->request->is_set_post('submit');
		
		$sql = '
			SELECT
				*
			FROM
				' . $this->form->table_name . '
			WHERE
				id = ' . $this->db->check_value($id);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		$fieldset = array(
			array('type' => 'text', 'name' => 'title', 'title' => 'Название меню', 'value' => $row['title']),
			array('type' => 'text', 'name' => 'alias', 'title' => 'Алиас', 'value' => $row['alias']),
			array('type' => 'checkbox', 'name' => 'activation', 'title' => 'Отображается НА САЙТЕ?', 'value' => 1, 'checked' => $row['activation']),
		);

		if( $submit )
		{
			$this->form->saveIntoDB($fieldset);
			$this->remove_cache();
			
			redirect($this->path_menu);
		}
		
		$this->form->createEditTMP($fieldset);
	}
	
	/**
	* Удаление кэшированного списка меню
	*/
	private function remove_cache()
	{
		$site_info = get_site_info_by_id($this->site_id);
		
		$sql = '
			SELECT
				*
			FROM
				' . $this->form->table_name;
		$this->db->query($sql);
		
		while( $row = $this->db->fetchrow() )
		{
			$this->cache->_delete(sprintf('%s_menu_%d_%s', $site_info['domain'], $row['id'], $site_info['language']));
		}
		
		$this->db->freeresult();
		
		$this->cache->delete('menus');
	}
}
