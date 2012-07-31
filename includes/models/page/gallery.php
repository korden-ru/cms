<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

namespace acp\models;

/**
* Блоки
*/
class page_gallery extends page
{
	protected $parent_row = array();
	
	function __construct()
	{
		parent::__construct();

		$this->form->addButtonText = 'Добавить новое фото';
		$this->form->sortElements  = true;
	}

	/**
	* Список
	*/
	public function index()
	{
		$id  = $this->request->variable('id', 0);
		$pid = $this->request->variable('pid', 0);
		
		$page_name = $this->get_parent_name($pid);
		
		$this->form->titleTable = (($page_name) ? '<a href="' . $this->path_menu . '">' . $page_name . '</a> | ' : '') . 'Фотографии (блоки)';

		$sql = '
			SELECT
				id,
				image,
				title,
				activation,
				sort
			FROM
				' . $this->form->table_name . '
			WHERE
				id_row = ' . $this->db->check_value($pid) . '
			ORDER BY
				sort ASC';
		$this->db->query($sql);
		$data = array();

		while( $row = $this->db->fetchrow() )
		{
			$row['activation'] = ( $row['activation'] ) ? '<center><img src="images/tick.png" alt=""></center>' : '';
			$row['image'] = ( $row['image'] ) ? '<a href="/uploads/' . $this->form->upload_folder . '/' . $row['image'] . '" onclick="return hs.expand(this);" title="' . $row['title'] . '" class="highslide"><img src="/uploads/' . $this->form->upload_folder . '/sm/' . $row['image'] . '" width="70"></a>' : '';

			$data[] = $row;
		}

		$this->db->freeresult();

		$this->form->createShowTMP(array(
			'ID',
			'Фото',
			'Название',
			'Отображается?',
		), $data, array(
			25,
			70,
			'',
			100,
		));
	}

	/**
	* Добавление
	*/
	public function add()
	{
		$pid = $this->request->variable('pid', 0);

		$sql = '
			SELECT
				COUNT(*) AS total,
				MAX(sort) AS max_sort
			FROM
				' . $this->form->table_name . '
			WHERE
				id_row = ' . $this->db->check_value($pid);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();

		$sql_ary = array(
			'id_row'     => $pid,
			'title'      => 'Фото №' . ($row['total'] + 1),
			'activation' => 0,
			'sort'       => $row['max_sort'] + 1
		);

		$sql = 'INSERT INTO ' . $this->form->table_name . ' ' . $this->db->build_array('INSERT', $sql_ary);
		$this->db->query($sql);

		redirect($this->form->U_EDIT . $this->db->insert_id());
	}

	public function delete()
	{
		$id  = $this->request->variable('id', 0);
		$pid = $this->request->variable('pid', 0);

		$sql = '
			SELECT
				image
			FROM
				' . $this->form->table_name . '
			WHERE
				id = ' . $this->db->check_value($id);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();

		if( $row )
		{
			unlink(SITE_DIR . 'uploads/' . $this->form->upload_folder . '/original/' . $row['image']);
			unlink(SITE_DIR . 'uploads/' . $this->form->upload_folder . '/sm/' . $row['image']);
			unlink(SITE_DIR . 'uploads/' . $this->form->upload_folder . '/' . $row['image']);

			$sql = '
				DELETE
				FROM
					' . $this->form->table_name . '
				WHERE
					id = ' . $this->db->check_value($id);
			$this->db->query($sql);
		}

		redirect($this->path_class . '&pid=' . $pid);
	}

	/**
	* Редактирование
	*/
	public function edit()
	{
		$id     = $this->request->variable('id', 0);
		$pid    = $this->request->variable('pid', 0);
		$submit = $this->request->is_set_post('submit');
		
		$this->get_parent_row($pid);

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

		$ajax_delete = array(
			'url'   => 'includes/ajax/delete_file.php', 
			'param' => "{ id: $id, table: '" . $this->form->table_name . "', column: 'image', dir: '" . 
$this->form->upload_folder . "' }"
		);	

		$resize = array(
			'root'     => array($this->config['thumbnail.source_width'], $this->config['thumbnail.source_height'], true, false),
			'original' => array($this->config['thumbnail.source_width'], $this->config['thumbnail.source_height'], false, false),
			'sm'       => array($this->config['thumbnail.width'], $this->config['thumbnail.height'], false, $this->config['thumbnail.crop'])
		);
		
		$fieldset = array(
			array('name' => 'title', 'title' => 'Наименование фотографии', 'type' => 'text', 'value' => $row['title']),
			array('name' => 'preview', 'title' => 'Краткое описание фотографии', 'type' => 'textarea', 'value' => $row['preview'], 'height' => 100),
			array('name' => 'text', 'title' => 'Полное описание фотографии', 'type' => 'textarea', 'value' => $row['text'], 'height' => 200),
			array('name' => 'viewed_text', 'title' => 'Отображать полное описание?', 'type' => 'checkbox', 'value' => 1, 'checked' => $row['viewed_text']),
			array('name' => 'activation', 'title' => 'Отображается НА САЙТЕ?', 'type' => 'checkbox', 'value' => 1, 'checked' => $row['activation']),
			array('name' => 'image', 'title' => 'Фотография', 'type' => 'file', 'old' => 'old_image', 'value' => $row['image'], 'resize' => $resize, 'ajax_delete' => $ajax_delete),


			array('type' => 'code', 	'html' => '<fieldset><legend>Информация для продвижения сайта</legend>'),
			array('name' => 'seo_title', 'title' => 'SEO | Заголовок страницы', 'type' => 'text', 'value' => $row['seo_title']),
			array('name' => 'seo_keys', 'title' => 'SEO | Ключевые слова', 'type' => 'text', 'value' => $row['seo_keys']),
			array('name' => 'seo_desc', 'title' => 'SEO | Описание страницы', 'type' => 'text', 'value' => $row['seo_desc']),
			array('type' => 'button', 	'name' => 'autoins', 'title' => 'Подобрать информацию для продвижения сайта', 'value' => 'Автоподбор', 'onclick' => "AjaxClickFormButton('title', ['seo_title','seo_keys','seo_desc'])"),
			array('type' => 'code', 	'html' => '</fieldset>'),


			array('name' => 'modifyurl', 'title' => 'Url', 'type' => 'hidden', 'value' => modifyUrl($row['title']))
		);

		if( $submit )
		{
			$this->form->saveIntoDB($fieldset);
			
			redirect($this->path_class . '&pid=' . $pid);
		}

		$this->form->createEditTMP($fieldset);
	}
	
	protected function get_parent_name($pid)
	{
		return '';
	}
	
	protected function get_parent_row($pid)
	{
		return array();
	}
}
