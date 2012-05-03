<?php

namespace acp;

use acp\models\page;

/**
* Управление группами подписчиков
*/
class maillist_groups extends page
{
	public function __construct()
	{
		parent::__construct();
		
		$this->form->addButtonText = 'Добавить новую группу';
		$this->form->titleTable    = 'Просмотр групп';
	}
	
	public function index()
	{
		$sql = '
			SELECT
				mg.id,
				mg.title,
				(SELECT COUNT(mu.id) FROM tcms_maillist_group_users mu WHERE mu.group_id = mg.id) AS total_users
			FROM
				' . $this->form->table_name . ' mg
			ORDER BY
				mg.id ASC';
		$this->db->query($sql);
		$data = array();
		
		while( $row = $this->db->fetchrow() )
		{
			$data[] = $row;
		}
		
		$this->form->createShowTMP(array(
			'ID',
			'Группа',
			'Подписчики',
		), $data, array(
			25,
			'',
			100,
		));
	}
	
	public function add()
	{
		$sql_ary = array('title' => '');
		
		$sql = 'INSERT INTO ' . $this->form->table_name . ' ' . $this->db->build_array('INSERT', $sql_ary);
		$this->db->query($sql);
		
		redirect($this->form->U_EDIT . $this->db->insert_id());
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
		
		$sql = '
			DELETE
			FROM
				tcms_maillist_group_users
			WHERE
				group_id = ' . $this->db->check_value($id);
		$this->db->query($sql);
		
		redirect($this->path_menu);
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
		$group = $this->db->fetchrow();
		$this->db->freeresult();
		
		if( $submit )
		{
			$title       = $this->request->post('title', '');
			$subscribers = $this->request->post('subscribers', array('' => ''));
			
			$sql = '
				UPDATE
					' . $this->form->table_name . '
				SET
					title = ' . $this->db->check_value($title) . '
				WHERE
					id = ' . $this->db->check_value($id);
			$this->db->query($sql);
			$sql_ary = array();
			
			foreach( $subscribers as $key => $value )
			{
				$sql_ary[] = array(
					'group_id' => $id,
					'user_id'  => $value
				);
			}
			
			$sql = '
				DELETE
				FROM
					tcms_maillist_group_users
				WHERE
					group_id = ' . $this->db->check_value($id);
			$this->db->query($sql);
			$this->db->multi_insert('tcms_maillist_group_users', $sql_ary);
			
			redirect($this->path_menu);
		}
		
		$sql = '
			SELECT
				m.*,
				mu.user_id AS checked
			FROM
				tcms_maillist m
			LEFT JOIN
				tcms_maillist_group_users mu ON (mu.user_id = m.id AND mu.group_id = ' . $this->db->check_value($id) . ')
			WHERE
				m.activation = 1
			ORDER BY
				m.date_subscribed ASC';
		$this->db->query($sql);
		
		while( $row = $this->db->fetchrow() )
		{
			$this->template->append('subscribers', $row);
		}
		
		$this->db->freeresult();
		
		$this->template->assign('group', $group);
		$this->template->file = 'maillist_group_users.html';
	}
}
