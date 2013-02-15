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
		
		$this->form->addButton = false;
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
				ip,
				activation
			FROM
				' . $this->form->table_name . '
			ORDER BY
				date DESC';
		$this->db->query_limit($sql, $pagination['on_page'], $pagination['offset']);
		$data = array();
		
		while ($row = $this->db->fetchrow())
		{
			$row['activation'] = $row['activation'] ? '<center><img src="images/tick.png" alt=""></center>' : '';
			$row['fio'] = '<b>' . $row['fio'] . '</b><br>тел.: ' . $row['phone'];
			$row['fio'] .= $row['email'] ? '<br>' . $row['email'] : '';
			$row['question'] = utf8_str_limit($row['question'], 150);
			
			unset($row['email'], $row['phone']);
			
			$data[] = $row;
		}
		
		$this->db->freeresult();

		$this->form->createShowTMP(array(
			'ID',
			'Дата',
			'ФИО/Телефон/E-mail',
			'Вопрос',
			'IP',
			'<center><img src="images/i_show_in_site.png" title="Отображается на сайте?"></center>',
		), $data, array(
			25,
			60,
			150,
			'',
			105,
			25,
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
			array('name' => 'question', 'title' => 'Вопрос', 'type' => 'textarea', 'value' => $row['question'], 'height' => 170),
			array('name' => 'answer', 'title' => 'Ответ', 'type' => 'textarea', 'value' => $row['answer'], 'height' => 170),
			array('name' => 'fio', 'title' => 'Имя', 'type' => 'text', 'value' => $row['fio']),
			array('name' => 'phone', 'title' => 'Телефон', 'type' => 'text', 'value' => $row['phone']),
			array('name' => 'email', 'title' => 'Email', 'type' => 'text', 'value' => $row['email']),
			array('name' => 'date', 'title' => 'Дата создания', 'type' => 'date', 'value' => $row['date']),
			array('name' => 'activation', 'title' => 'Отобразить на сайте?', 'type' => 'checkbox', 'checked' => $row['activation'], 'value' => 1),
			array('name' => 'sendmail', 'title' => 'Отправить ответ по электронной почте', 'type' => 'checkbox', 'value' => 1, 'checked' => false),
		);
		
		if ($submit)
		{
			$email    = $this->request->post('email', '');
			$fio      = $this->request->post('fio', '');
			$question = htmlspecialchars_decode($this->request->post('question', ''));
			$answer   = htmlspecialchars_decode($this->request->post('answer', ''));
			$sendmail = $this->request->post('sendmail', 0);
			
			unset($_POST['sendmail'], $_REQUEST['sendmail']);
			unset($fieldset[sizeof($fieldset) - 1]);
			
			if ($answer && $sendmail)
			{
				$this->template->assign(array(
					'email'    => $email,
					'date'     => $row['date'],
					'fio'      => $fio,
					'question' => html_entity_decode($question),
					'answer'   => html_entity_decode($answer),
				));

				$messenger = new \engine\core\email();
				$messenger->from($this->config['contacts.email'], $this->config['sitename']);
				$messenger->to($email);
				$messenger->subject('Re: Вопрос #' . $id);
				$messenger->set_mailtype('text');
				$messenger->set_wordwrap(false);
				$messenger->message($this->template->fetch('email/faq_reply.html'));
				$messenger->send();
			}

			$this->form->saveIntoDB($fieldset);
			
			redirect($this->path_menu);
		}
		
		$this->form->createEditTMP($fieldset);
	}
}
