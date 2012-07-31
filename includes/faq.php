<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

namespace acp;

use acp\models\page;

/**
* Вопрос-ответ
*/
class faq extends page
{
	function __construct()
	{
		parent::__construct();
		
		$this->form->addButton    = false;
		$this->form->sortElements = true;
	}
	
	/**
	* Список
	*/
	public function index()
	{
		$pagination = pagination(10, $this->get_entries_count(), $this->path_menu);
		
		$sql = '
			SELECT
				id,
				date,
				fio,
				email,
				phone,
				question,
				activation,
				sort,
				INET_NTOA(ip) AS ip,
				INET_NTOA(ip_orig) AS ip_orig
			FROM
				' . $this->form->table_name . '
			ORDER BY
				date DESC';
		$this->db->query_limit($sql, $pagination['on_page'], $pagination['offset']);
		$data = array();
		
		while( $row = $this->db->fetchrow() )
		{
			$row['activation'] = ( $row['activation'] ) ? '<center><img src="images/tick.png" alt=""></center>' : '';
			$row['fio'] = '<b>' . $row['fio'] . '</b><br>тел.: ' . $row['phone'];
			$row['fio'] .= ( $row['email'] ) ? '<br>' . $row['email'] : '';
			$row['ip'] = ip_template($row['ip'], $row['ip_orig']);
			$row['question'] = utf8_str_limit($row['question'], 150);
			
			unset($row['email'], $row['phone'], $row['ip_orig']);
			
			$data[] = $row;
		}
		
		$this->db->freeresult();

		$this->form->createShowTMP(array(
			'ID',
			'Дата',
			'ФИО/Телефон/E-mail',
			'Вопрос',
			'Отвечено?',
			'IP/IP_ORIG'
		), $data, array(
			25,
			60,
			150,
			'',
			60,
			120
		));
	}
	
	/**
	* Удаление
	*/
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

	/**
	* Редактирование
	*/
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
			array('name' => 'question', 'title' => 'Вопрос', 'type' => 'textarea', 'value' => $row['question'], 'height' => 100),
			array('name' => 'answer', 'title' => 'Ответ', 'type' => 'textarea', 'value' => $row['answer'], 'height' => 100),
			array('name' => 'fio', 'title' => 'Имя', 'type' => 'text', 'value' => $row['fio']),
			array('name' => 'phone', 'title' => 'Телефон', 'type' => 'text', 'value' => $row['phone']),
			array('name' => 'email', 'title' => 'Email', 'type' => 'text', 'value' => $row['email']),
			array('name' => 'date', 'title' => 'Дата создания', 'type' => 'date', 'value' => $row['date']),
			array('name' => 'activation', 'title' => 'Отвечен?', 'type' => 'checkbox', 'checked' => $row['activation'], 'value' => 1)
		);
		
		if( $submit )
		{
			$this->form->saveIntoDB($fieldset);
			
			redirect($this->path_menu);
		}
		
		$this->form->createEditTMP($fieldset);
	}
}
