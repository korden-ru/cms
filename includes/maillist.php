<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

namespace acp;

use acp\models\page;

/**
* Рассылки
*/
class maillist extends page
{
	public function __construct()
	{
		parent::__construct();

		$this->form->addButton  = false;
		$this->form->titleTable = 'Новая рассылка';
	}
	
	public function edit()
	{
		$submit = $this->request->is_set_post('submit');

		if ($submit)
		{
			$recipients = $this->request->post('recipients', 0);
			$title      = $this->request->post('title', '');
			$text       = htmlspecialchars_decode($this->request->post('text', ''));
			
			$sql = '
				SELECT
					*
				FROM
					tcms_maillist_signature';
			$this->db->query($sql);
			$signature = $this->db->fetchfield('signature');
			$this->db->freeresult();
			
			if( $recipients > 0 )
			{
				$sql = '
					SELECT
						mu.*,
						m.email,
						m.code
					FROM
						tcms_maillist_group_users mu
					LEFT JOIN
						tcms_maillist m ON (m.id = mu.user_id)
					WHERE
						mu.group_id = ' . $this->db->check_value($recipients);
			}
			else
			{
				$sql = '
					SELECT
						*
					FROM
						tcms_maillist
					WHERE
						activation = 1';
			}
			
			$result = $this->db->query($sql);
			$this->data['site_id'] = 1;
			$this->obtain_handlers_urls();
			$sql_ary = array();
			$messenger = new \engine\core\email();
			$messenger->from($this->config['email.noreply'], $this->config['sitename']);
			
			while ($row = $this->db->fetchrow($result))
			{
				$messenger->to($row['email']);
				$messenger->subject($title);
				$messenger->message($text . '<br><br><a href="' . ilink('http://' . $_SERVER['SERVER_NAME'] . $this->get_handler_url('maillist::unsubscribe', array($row['code']))) . '">Отписаться от рассылки</a><br><br>' . $signature);
				$messenger->send();
			}
			
			$this->db->freeresult($result);
			// $this->db->multi_insert('tcms_maillist_spool', $sql_ary);
			
			$this->template->file = 'maillist.html';
			$this->page_header();
			$this->page_footer();
		}
		
		$sql = '
			SELECT
				id,
				title
			FROM
				tcms_maillist_groups
			ORDER BY
				id ASC';
		$this->db->query($sql);
		$groups = array('Все' => 0);
		
		while ($row = $this->db->fetchrow())
		{
			$groups[$row['title']] = $row['id'];
		}
		
		$this->db->freeresult();

		$fieldset = array(
			array('type' => 'text', 'name' => 'title', 'title' => 'Тема рассылки', 'value' => ''),
			array('type' => 'select', 'name' => 'recipients', 'title' => 'Получатели', 'options' => $groups, 'value' => 0),
			array('type' => 'textarea', 'name' => 'text', 'title' => 'Сообщение', 'value' => '', 'height' => 300),
		);

		$this->form->createEditTMP($fieldset);
	}
}
