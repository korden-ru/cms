<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

namespace acp;

use acp\models\page;

/**
* Группы пользователей
*/
class users_groups extends page
{
	function __construct()
	{
		parent::__construct();
		
		$this->form->addButtonText = 'Добавить группу';
	}
	/**
	* Список
	*/
	public function index()
	{
		$sql = '
			SELECT
				id,
				title
			FROM
				' . $this->form->table_name . '
			WHERE
				id >= ' . $this->user->group . '
			ORDER BY
				id ASC';
		$this->db->query($sql);
		$data = array();
		
		while( $row = $this->db->fetchrow() )
		{
			$data[] = $row;
		}
		
		$this->db->freeresult();
		$this->form->createShowTMP(array('ID', 'Название'), $data);
	}
	
	/**
	* Добавление
	*/
	public function add()
	{
		$sql_ary = array('title' => 'Новая группа');
		
		$sql = 'INSERT INTO ' . $this->form->table_name . ' ' . $this->db->build_array('INSERT', $sql_ary);
		$this->db->query($sql);

		redirect($this->form->U_EDIT . $this->db->insert_id());
	}
	
	/**
	* Удаление
	*/
	public function delete()
	{
		$id = $this->request->variable('id', 0);
		
		if( $id != 1 && $id != 3 )
		{
			$sql = '
				DELETE
				FROM
					' . $this->form->table_name . '
				WHERE
					id = ' . $this->db->check_value($id);
			$this->db->query($sql);
		}

		redirect($this->path_menu);
	}
	
	/**
	* Редактирование
	*/
	public function edit()
	{
		$id     = $this->request->variable('id', 0);
		$submit = $this->request->is_set_post('submit');

		if( $submit )
		{
			if( $id == 1 || $id == 3 )
			{
				redirect($this->path_menu);
			}
			
			$group_title = $this->request->post('title', '');
			
			$sql = '
				UPDATE
					' . $this->form->table_name . '
				SET
					title = ' . $this->db->check_value($group_title) . ',
					permissions = \'';

	        unset($_POST['submit'], $_POST['title']);

			$i = 1;
			$count = count($_POST);
			
			foreach( $_POST as $key => $value )
			{
				$zap = ($i != $count) ? ', ' : '';
				
				if( is_int($key) )
				{
					$sql .= $key . $zap;
				}
				
				$i++;
			}
			
			$sql .= '\' WHERE id = ' . $this->db->check_value($id);
			$this->db->query($sql);

			redirect($this->path_menu);
		}
		
		$this->template->assign('userPerms', $this->user->userPerms);

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
		
		$this->template->assign(array(
			'groupName'  => $row['title'],
			'groupPerms' => explode(',', $row['permissions'])
		));
		
		$sql = '
			SELECT
				id,
				title
			FROM
				' . MODULES_TABLE . '
			WHERE
				parent = 0
			AND
				tab = 0
			ORDER BY
				id ASC';
		$this->db->query($sql);
		
		while( $row = $this->db->fetchrow() )
		{
			$this->template->append('pTabs', $row);
		}
		
		$this->db->freeresult();
		
		$sql = '
			SELECT
				id,
				title,
				tab
			FROM
				' . MODULES_TABLE . '
			WHERE
				parent = 0
			ORDER BY
				id ASC';
		$this->db->query($sql);
		
		while( $row = $this->db->fetchrow() )
		{
			$this->template->append('pTitles', $row);
		}
		
		$this->db->freeresult();
		
		$sql = '
			SELECT
				id,
				title,
				parent
			FROM
				' . MODULES_TABLE . '
			WHERE
				tab != 0
			AND
				parent != 0
			ORDER BY
				tab ASC';
		$this->db->query($sql);
		
		while( $row = $this->db->fetchrow() )
		{
			$this->template->append('pModules', $row);
		}
		
		$this->db->freeresult();
		$this->template->file = 'users_groups.html';
	}
}
