<?php

namespace acp;

use acp\models\page;

/**
* Обратная связь
*/
class feedback extends page
{
	function __construct()
	{
		parent::__construct();
		
		$this->form->addButton  = false;
		$this->form->titleTable = 'Отзывы';
	}

	/**
	* Отзывы
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
				org,
				phone,
				text,
				comment,
				ip,
				activation
			FROM
				' . $this->form->table_name . '
			ORDER BY
				date DESC';
		$this->db->query_limit($sql, $pagination['on_page'], $pagination['offset']);
		$data = array();
		
		while( $row = $this->db->fetchrow() )
		{
			if( $row['activation'] )
			{
				$row['activation'] = '<center><img src="images/tick.png" alt=""></center>';
			}
			else
			{
				$row['activation'] = '';
				$row['style_tr'] = 'background-color: #a4ff9f;';
			}
			
			$row['ip'] = '<a class="jt" style="font-size: .8em !important;border-bottom: 1px dotted #000; text-decoration: none; cursor: help;" href="#" rel="includes/ajax/getipinfo.php?ip=' . $row['ip'] . '" id="ipinfo' . $row['id'] . '" title="Информация о IP">' . $row['ip'] . '</a>';
			$row['email'] = sprintf('E-mail: %s<br>Тел.: %s', $row['email'], $row['phone']);
			$row['text'] = utf8_str_limit($row['text'], 200);
			
			// switch( $row['type'] )
			// {
			// 	case 0: $row['type'] = '<center><img src="images/plus_button.png" alt="Положительный"></center>'; break;
			// 	case 1: $row['type'] = '<center><img src="images/minus_button.png" alt="Отрицательный"></center>'; break;
			// }
			
			unset($row['org'], $row['phone']);
			
			$data[] = $row;
		}
		
		$this->db->freeresult();
		
		$this->form->tooltip = true;
		$this->form->paginator = true;
		
		$this->form->createShowTMP(array(
			'ID',
			'Дата',
			'ФИО',
			'Контакты',
			'Сообщение',
			'Заметки',
			'<center>IP</center>',
			'<center><img src="images/i_show_in_site.png" title="Просмотрен?" alt=""></center>'
		), $data, array(
			25,
			60,
			110,
			150,
			'',
			150,
			60,
			25
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
		
		if( !$row )
		{
			redirect($this->path_menu);
		}

		$sql = '
			UPDATE
				' . $this->form->table_name . '
			SET
				activation = 1
			WHERE
				id = ' . $this->db->check_value($id);
		$this->db->query($sql);
		
		$fieldset = array(
			array('name' => 'date', 'title' => 'Дата отзыва', 'type' => 'noinput', 'value' => date("d.m.Y в H:i", $row['date'])),
			array('name' => 'fio', 'title' => 'Имя', 'type' => 'text', 'value' => $row['fio']),
			array('name' => 'email', 'title' => 'E-mail', 'type' => 'text', 'value' => $row['email']),
			array('name' => 'phone', 'title' => 'Контактный телефон', 'type' => 'text', 'value' => $row['phone']),
			// array('name' => 'org', 'title' => 'Организация', 'type' => 'text', 'value' => $row['org']),
			array('name' => 'text', 'title' => 'Сообщение', 'type' => 'textbig', 'value' => $row['text']),
			array('name' => 'comment', 'title' => 'Комментирование', 'type' => 'textbig', 'value' => $row['comment'])
		);
			
		if( $submit )
		{
			$this->form->saveIntoDB($fieldset);
			
			redirect($this->path_menu);
		}

		$this->form->createEditTMP($fieldset);
	}
}
