<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

namespace acp;

use acp\models\page;

/**
* Подпись к письмам
*/
class maillist_signature extends page
{
	public function __construct()
	{
		parent::__construct();

		$this->form->addButton  = false;
		$this->form->titleTable = 'Подпись к письмам';
	}
	
	public function edit()
	{
		$submit = $this->request->is_set_post('submit');

		if( $submit )
		{
			$signature = htmlspecialchars_decode($this->request->post('signature', ''));
			
			$sql = '
				UPDATE
					tcms_maillist_signature
				SET
					signature = ' . $this->db->check_value($signature);
			$this->db->query($sql);
			
			redirect($this->path_menu);
		}
		
		$sql = '
			SELECT
				*
			FROM
				tcms_maillist_signature';
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		$fieldset = array(
			array('type' => 'textarea', 'name' => 'signature', 'title' => 'Подпись', 'value' => $row['signature'], 'height' => 300),
		);

		$this->form->createEditTMP($fieldset);
	}
}
