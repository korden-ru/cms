<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

namespace acp;

use acp\models\page_publications;

/**
* Статьи
*/
class articles extends page_publications
{
	function __construct()
	{
		parent::__construct();
		
		$this->form->addButtonText = 'Добавить новую статью';
		$this->form->titleTable = 'Просмотр статей сайта';
	}

	/**
	* Данные для вставки
	*/
	protected function get_insert_data($total)
	{
		return array(
			'site_id'    => $this->site_id,
			'type'       => $this->publication_type,
			'title'      => 'Статья №' . $total,
			'activation' => 0,
			'date'       => time(),
			'modifyurl'  => modifyUrl('Статья №' . $total),
		);
	}
}
