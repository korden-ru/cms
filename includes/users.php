<?php

namespace acp;

use acp\models\page;

/**
* Пользователи
*/
class users extends page
{
	/**
	* Список
	*/
	public function index()
	{
		$sql = '
			SELECT
				u.*,
				ug.title AS group_title
			FROM
				' . $this->form->table_name . ' u
			LEFT JOIN
				' . $this->form->table_name . '_groups ug ON (ug.id = u.group)
			ORDER BY
				u.ban ASC';
		$this->db->query($sql);
		
		while( $row = $this->db->fetchrow() )
		{
			if( $this->user->id != 1 && $row['id'] == 1 )
			{
				continue;
			}
			
			$this->template->append('users', array(
				'ID'          => $row['id'],
				'NAME'        => $row['name'],
				'LOGIN'       => $row['login'],
				'GROUP_TITLE' => $row['group_title'],
				'BAN'         => $row['ban'],
				'ROLE'        => $row['role'],
				'MAIL'        => $row['mail'],
				'REGTIME'     => date('d.m.Y', $row['regtime']),
				'IN_ASSOC'    => $row['in_assoc'],
				'U_APPROVE'   => $this->path_menu . '&mode=approve&id=' . $row['id'],
				'U_EDIT'      => $this->path_menu . '&mode=edit&id=' . $row['id'],
				'U_DECLINE'   => $this->path_menu . '&mode=delete&id=' . $row['id'],
				'U_DELETE'    => $this->path_menu . '&mode=delete&id=' . $row['id']
			));
		}
		
		$this->db->freeresult();
		$this->template->file = 'users.html';	
	}
	
	/**
	* Добавление
	*/
	public function add()
	{
		$sql_ary = array(
			'name'    => 'New user',
			'role'    => 0,
			'`group`' => 3,
			'regtime' => time()
		);
		
		$sql = 'INSERT INTO ' . $this->form->table_name . ' ' . $this->db->build_array('INSERT', $sql_ary);
		$this->db->query($sql);
		
		redirect($this->form->U_EDIT . $this->db->insert_id());
	}
	
	/**
	* Одобрение
	*/
	public function approve()
	{
		$id = $this->request->variable('id', 0);
		
		$sql = '
			UPDATE
				' . $this->form->table_name . '
			SET
				ban = 0
			WHERE
				id = ' . $this->db->check_value($id);
		$this->db->query($sql);
		
		redirect($this->path_menu);
	}
	
	/**
	* Удаление
	*/
	public function delete()
	{
		$id = $this->request->variable('id', 0);
		
		if( $id != 1 )
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
		$id       = $this->request->variable('id', 0);
		$password = $this->request->post('password', '');
		$submit   = $this->request->is_set_post('submit');
		
		if( $submit )
		{
			$sql = '
				SELECT
					password
				FROM
					' . $this->form->table_name . '
				WHERE
					id = ' . $this->db->check_value($id);
			$this->db->query($sql);
			$row = $this->db->fetchrow();
			$this->db->freeresult();

			$old_password = $row['password'];
			$new_password = ( empty($password) ) ? $old_password : md5($password);
			
			$set_group = $this->request->post('group', 0);
			$set_group = ( $this->user->group != 1 && $set_group === 1 ) ? 3 : $set_group;
			
			$sql_ary = array(
				'password'          => $new_password,
				'role'              => $this->request->post('role', ''),
				'mail'              => $this->request->post('mail', ''),
				'ban'               => 0,
				'login'             => $this->request->post('login', ''),
				'name'              => $this->request->post('name', ''),
				'short_description' => $this->request->post('short', ''),
				'description'       => $this->request->post('desc', ''),
				'contact_name'      => $this->request->post('contact_name', ''),
				'contact_phone'     => $this->request->post('contact_phone', ''),
				'`group`'           => $set_group,
			);
			
			$sql = '
				UPDATE
					' . $this->form->table_name . '
				SET
					' . $this->db->build_array('UPDATE', $sql_ary) . '
				WHERE
					id = ' . $this->db->check_value($id);
			$this->db->query($sql);
			
			redirect($this->path_menu);
		}

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
		
		if( !$row )
		{
			redirect($this->menu_path);
		}
		
		$this->template->assign('HIDDEN_FIELDS', hidden_fields(array('old_image' => $row['image'])));
		$this->template->assign(array(
			'NAME'          => htmlspecialchars($row['name']),
			'LOGIN'         => $row['login'],
			'BAN'           => 0,
			'SHORT_DESC'    => $row['short_description'],
			'DESC'          => $row['description'],
			'MAIL'          => $row['mail'],
			'ROLE'          => $row['role'],
			'GROUP'         => $row['group'],
			'CONTACT_NAME'  => $row['contact_name'],
			'CONTACT_PHONE' => $row['contact_phone'],
			'U_ACTION'      => $this->form->U_EDIT . $id
		));

		$where = ( $this->user->group != 1 ) ? ' WHERE id >= ' . $this->user->group : '';
		
		$sql = '
			SELECT
				*
			FROM
				' . $this->form->table_name . '_groups ug
			' . $where . '
			ORDER BY
				id ASC';
		$this->db->query($sql);
		
		while( $row = $this->db->fetchrow() )
		{
			$this->template->append('groups', $row);
		}
		
		$this->db->freeresult();
		$this->template->file = 'users_edit.html';
	}
}
