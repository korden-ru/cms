<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

namespace acp;

use acp\models\page_publications;

/**
* Услуги сайта
*/
class services extends page_publications
{
	function __construct()
	{
		parent::__construct();
		
		$this->form->addButtonText = 'Добавить новую услугу';
		$this->form->titleTable = 'Просмотр услуг';
	}
	
	/**
	* Данные для вставки
	*/
	protected function get_insert_data($total)
	{
		return array(
			'title'      => 'Услуга №' . $total,
			'activation' => 0,
			'date'       => time(),
			'modifyurl'  => modifyUrl('Услуга №' . $total),
		);
	}
}
