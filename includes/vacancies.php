<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

namespace acp;

use acp\models\page;

/**
* Вакансии
*/
class vacancies extends page
{
	function __construct()
	{
		parent::__construct();

		$this->form->addButtonText = 'Добавить новую вакансию';
		$this->form->sortElements  = true;
		$this->form->titleTable    = 'Вакансии';
	}
	
	public function index()
	{
		$sql = '
			SELECT
				id,
				title,
				info,
				activation,
				sort
			FROM
				' . $this->form->table_name . '
			ORDER BY
				sort ASC';
		$this->db->query($sql);
		$data = array();
		
		while( $row = $this->db->fetchrow() )
		{
			$row['activation'] = ( $row['activation'] ) ? '<center><img src="images/tick.png" alt=""></center>' : '';
			$row['info'] = utf8_str_limit(strip_tags($row['info']));
			
			$data[] = $row;
		}
		
		$this->db->freeresult();

		$this->form->createShowTMP(array(
			'ID',
			'Наименование',
			'Описание',
			'<center><img src="images/i_show_in_site.png" title="Отображается на сайте?" alt=""></center>
		'), $data, array(
			25,
			200,
			'',
			25
		));
	}
	
	public function add()
	{
		$sql = '
			SELECT
				COUNT(id) AS total,
				MAX(sort) AS max_sort
			FROM
				' . $this->form->table_name;
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		$sql_ary = array(
			'title' => 'Новая вакансия №' . ($row['total'] + 1),
			'sort'  => $row['max_sort'] + 1
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
		
		redirect($this->path_class);
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
			array('type' => 'text', 'name' => 'title', 'title' => 'Наименование вакансии', 'value' => $row['title']),
			array('type' => 'textarea', 'name' => 'info', 'title' => 'Краткое описание вакансии', 'value' => $row['info'], 'height' => 200),


			array('type' => 'code', 'html' => '<fieldset><legend>Информация для продвижения сайта</legend>'),
			array('type' => 'text', 'name' => 'seo_title', 'title' => 'SEO | Заголовок страницы', 'value' => $row['seo_title']),
			array('type' => 'text', 'name' => 'seo_keys', 'title' => 'SEO | Ключевые слова', 'value' => $row['seo_keys']),
			array('type' => 'text', 'name' => 'seo_desc', 'title' => 'SEO | Описание страницы', 'value' => $row['seo_desc']),
			array('type' => 'button', 'name' => 'autoins', 'title' => 'Подобрать информацию для продвижения сайта', 'value' => 'Автоподбор', 'onclick' => "AjaxClickFormButton('title', ['seo_title','seo_keys','seo_desc'])"),
			array('type' => 'code', 'html' => '</fieldset>'),


			array('type' => 'checkbox', 'name' => 'activation', 'title' => 'Отображается НА САЙТЕ?', 'value' => 1, 'checked' => $row['activation']),
			array('name' => 'modifyurl', 'title' => '', 'type' => 'hidden', 'value' => ''),
		);

		if( $submit )
		{
			$this->form->saveIntoDB($fieldset);
			
			redirect($this->path_menu);
		}
		
		$this->form->createEditTMP($fieldset);
	}
}
