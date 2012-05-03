<?php

namespace acp;

use acp\models\page;

/**
* Настройки сайта
*/
class config extends page
{
	function __construct()
	{
		parent::__construct();
		
		$this->form->addButton = false;
	}

	/**
	* Список настроек
	*/
	public function index()
	{
		$sql = '
			SELECT
				*
			FROM
				tcms_config
			WHERE
				site_id = 1
			ORDER BY
				sort';
		$this->db->query($sql);
		
		while( $row = $this->db->fetchrow() )
		{
			$this->template->append('config', $row);
		}
		
		$this->db->freeresult();
		
		$this->template->assign('U_ACTION', $this->path_menu . '&mode=edit');
		$this->template->file = 'config.html';
	}
	
	/**
	* Редактирование настроек сайта
	*/
	public function edit()
	{
		$config = $this->request->post('config', array('' => ''));
		$submit	= $this->request->is_set_post('submit');
		
		if( $submit )
		{
			foreach( $config as $key => $value )
			{
				$this->config->set($key, htmlspecialchars_decode($value));
			}
		}
		
		redirect($this->path_menu);
	}
}
