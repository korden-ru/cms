<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

namespace acp;

use acp\models\page;

/**
* Подписчики
*/
class maillist_users extends page
{
	public function __construct()
	{
		parent::__construct();
		
		$this->form->addButtonText = 'Добавить нового подписчика';
		$this->form->table_name    = 'tcms_maillist';
		$this->form->titleTable    = 'Просмотр подписчиков';
	}
	
	public function index()
	{
		$pagination = pagination(20, $this->get_entries_count(), $this->path_menu);

		$sql = '
			SELECT
				id,
				email,
				title,
				name,
				activation
			FROM
				' . $this->form->table_name . '
			ORDER BY
				date_subscribed DESC';
		$this->db->query_limit($sql, $pagination['on_page'], $pagination['offset']);
		$data = array();
		
		while( $row = $this->db->fetchrow() )
		{
			$row['activation'] = ( $row['activation'] ) ? '<center><img src="images/tick.png" alt=""></center>' : '';
			
			$data[] = $row;
		}
		
		$this->form->paginator = true;
		$this->form->createShowTMP(array(
			'ID',
			'Email',
			'Имя',
			'Имя пользователя',
			'<center><img src="images/i_show_in_site.png" title="Отображается на сайте?" alt=""></center>
		'), $data, array(
			25,
			'',
			'',
			'',
			25
		));
	}
	
	public function add()
	{
		$sql_ary = array(
			'date_subscribed' => time(),
			'activation'      => 0,
			'code'            => make_random_string()
		);
		
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
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		$fieldset = array(
			array('name' => 'title', 'title' => 'Имя', 'type' => 'text', 'value' => $row['title']),
			array('name' => 'email', 'title' => 'Электронный адрес', 'type' => 'text', 'value' => $row['email']),
			array('name' => 'activation', 'title' => 'Подписка активирована?', 'type' => 'checkbox', 'value' => 1, 'checked' => $row['activation']),
		);
		
		if( $submit )
		{
			$this->form->saveIntoDB($fieldset);
			
			redirect($this->path_menu);
		}

		$this->form->createEditTMP($fieldset);
	}

	/**
	* Подсчет количества записей
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
