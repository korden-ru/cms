<?php

namespace acp;

use acp\models\page_publications;

/**
* Публикации
*/
class publications extends page_publications
{
	function __construct()
	{
		parent::__construct();
		
		$this->form->addButtonText = 'Добавить публикацию';
		$this->form->titleTable = 'Просмотр публикаций';
	}

	/**
	* Данные для вставки
	*/
	protected function get_insert_data($total)
	{
		return array(
			'site_id'    => $this->site_id,
			'type'       => $this->publication_type,
			'title'      => 'Публикация №' . $total,
			'activation' => 0,
			'date'       => time(),
			'modifyurl'  => modifyUrl('Публикация №' . $total),
		);
	}
}
