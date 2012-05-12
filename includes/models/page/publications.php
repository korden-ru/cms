<?php
/**
*
* @package cms.korden.net
* @copyright (c) 2012 vacuum
*
*/

namespace acp\models;

class page_publications extends page
{
	protected $publication_type = 0;

	function __construct()
	{
		global $app;

		parent::__construct();
		
		$class_name = get_class($this);
		$table_name = mb_substr($class_name, mb_strrpos($class_name, '\\') + 1);
		
		if( !isset($app['publication.types'][$table_name]) )
		{
			trigger_error('Тип публикаций не найден.');
		}
		
		$this->publication_type = $app['publication.types'][$table_name];
	}
	
	/**
	* Список
	*/
	public function index()
	{
		$pagination = pagination(10, $this->get_entries_count(), $this->path_menu);
		
		$sql = '
			SELECT
				a.id,
				a.image,
				a.date,
				a.title,
				a.activation,
				a.page_type,
				(SELECT COUNT(b.id) FROM tcms_publications_gallery b WHERE b.id_row = a.id) AS total_photos
			FROM
				tcms_publications a
			WHERE
				a.site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				a.type = ' . $this->db->check_value($this->publication_type) . '
			ORDER BY
				a.date DESC';
		$this->db->query_limit($sql, $pagination['on_page'], $pagination['offset']);
		$data = array();
		
		while( $row = $this->db->fetchrow() )
		{
			if( $row['page_type'] )
			{
				$row['add_buttons'][] = '<input class="button1" style="width:100%;" type="button" value="Фотоотчет (' . $row['total_photos'] . ')" onclick="Redirect(arguments, \'' . $this->path_menu . '&class=' . $this->class_name . '_gallery&pid=' . $row['id'] . '\');" />';
			}
			
			$row['activation'] = $row['activation'] ? '<center><img src="images/tick.png" alt=""></center>' : '';
			
			switch( $row['page_type'] )
			{
				case 0: $row['page_type'] = 'Текстовая'; break;
				case 1: $row['page_type'] = 'Текстовая с галереей'; break;
				case 2: $row['page_type'] = 'Текстовая с блоками'; break;
				case 3: $row['page_type'] = 'Блоки'; break;
				case 4: $row['page_type'] = 'Содержит товары'; break;
			}
			
			$row['page_type']  = '<center>' . $row['page_type'] . '</center>';
			
			$row['image'] = $row['image'] ? '<a href="/uploads/' . $this->form->upload_folder . '/' . $row['image'] . '" onclick="return hs.expand(this);" title="' . htmlspecialchars($row['title']) . '" class="highslide"><img src="/uploads/' . $this->form->upload_folder . '/sm/' . $row['image'] . '" width="70"></a>' : '';
				
			unset($row['total_photos']);

			$data[] = $row;
		}
		
		$this->db->freeresult();
		
		// $this->form->add_ajax_checkbox('topnews');
		$this->form->paginator = true;
		$this->form->createShowTMP(array(
			'ID',
			'Фото',
			'Дата',
			'Наименование',
			'<center><img src="images/i_show_in_site.png" title="Отображается на сайте?" alt=""></center>',
			'Тип страницы'
		), $data, array(
			25,
			70,
			80,
			'',
			25,
			100
		));
	}
	
	/**
	* Добавление
	*/
	public function add()
	{
		$sql = '
			SELECT
				COUNT(*) AS total
			FROM
				tcms_publications
			WHERE
				site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				type = ' . $this->db->check_value($this->publication_type);
		$this->db->query($sql);
		$total = $this->db->fetchfield('total') + 1;
		$this->db->freeresult();
		
		$sql = 'INSERT INTO tcms_publications ' . $this->db->build_array('INSERT', $this->get_insert_data($total));
		$this->db->query($sql);
		
		redirect($this->form->U_EDIT . $this->db->insert_id());
	}
	
	/**
	* Удаление
	*/
	public function delete()
	{
		$id = $this->request->variable('id', 0);
		
		$sql = '
			SELECT
				image
			FROM
				tcms_publications
			WHERE
				id = ' . $this->db->check_value($id) . '
			AND
				site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				type = ' . $this->db->check_value($this->publication_type);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if( $row )
		{
			unlink(SITE_DIR . 'uploads/' . $this->form->upload_folder . '/original/' . $row['image']);
			unlink(SITE_DIR . 'uploads/' . $this->form->upload_folder . '/sm/' . $row['image']);
			unlink(SITE_DIR . 'uploads/' . $this->form->upload_folder . '/main/' . $row['image']);
			unlink(SITE_DIR . 'uploads/' . $this->form->upload_folder . '/' . $row['image']);
			
			$sql = '
				DELETE
				FROM
					tcms_publications
				WHERE
					id = ' . $this->db->check_value($id) . '
				AND
					type = ' . $this->db->check_value($this->publication_type);
			$this->db->query($sql);
		}
		
		redirect($this->path_menu);
	}

	/**
	* Редактирование
	*/
	public function edit()
	{
		global $app;
		
		$id     = $this->request->variable('id', 0);
		$submit = $this->request->is_set_post('submit');
		
		$sql = '
			SELECT
				*
			FROM
				tcms_publications
			WHERE
				id = ' . $this->db->check_value($id) . '
			AND
				site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				type = ' . $this->db->check_value($this->publication_type);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();

		$ajax_delete = array(
			'url'   => 'includes/ajax/delete_file.php', 
			'param' => "{ id: $id, table: 'tcms_publications', column: 'image', dir: '" . $this->form->upload_folder . "' }"
		);	
			
		$resize = array(
			'root'     => array($this->config['thumbnail.source_width'], $this->config['thumbnail.source_height'], true, false),
			'original' => array($this->config['thumbnail.source_width'], $this->config['thumbnail.source_height'], false, false),
			'sm'       => array($this->config['thumbnail.width'], $this->config['thumbnail.height'], false, $this->config['thumbnail.crop'])
		);
		
		$fieldset = array(
			array('name' => 'date', 'title' => 'Дата (ДД.ММ.ГГГГ)', 'type' => 'date', 'value' => $row['date']),
			array('name' => 'title', 'title' => 'Заголовок', 'type' => 'text', 'value' => $row['title']),
			//array('name' => 'title_small', 'title' => 'Краткий заголовок для ТОП-новости', 'type' => 'text', 'value' => $row['title_small'], 'prim' => '2-3 слова. Если не указан, будет использовано поле «Заголовок»'),
			array('name' => 'modifyurl', 'title' => 'ЧПУ (человекопонятный URL)', 'type' => 'hidden', 'value' => modifyUrl($row['title']." ".$id)),
			array('name' => 'preview', 'title' => 'Краткое описание', 'type' => 'textarea', 'value' => $row['preview'], 'height' => 100),
			array('name' => 'text', 'title' => 'Текст', 'type' => 'textarea', 'value' => $row['text'], 'height' => 200),
			array('name' => 'image', 'title' => 'Изображение', 'type' => 'file', 'value' => $row['image'], 'old' => 'old_image', 'resize' => $resize, 'ajax_delete' => $ajax_delete),
			
			
			array('type' => 'code', 	'html' => '<fieldset><legend>Информация для продвижения сайта</legend>'),
			array('type' => 'text', 	'name' => 'seo_title', 'title' => 'SEO | Заголовок страницы', 'value' => $row['seo_title']),
			array('type' => 'text', 	'name' => 'seo_keys', 'title' => 'SEO | Ключевые слова', 'value' => $row['seo_keys']),
			array('type' => 'text', 	'name' => 'seo_desc', 'title' => 'SEO | Описание страницы', 'value' => $row['seo_desc']),
			array('type' => 'button', 	'name' => 'autoins', 'title' => 'Подобрать информацию для продвижения сайта', 'value' => 'Автоподбор', 'onclick' => "AjaxClickFormButton('title', ['seo_title','seo_keys','seo_desc'])"),
			array('type' => 'code', 	'html' => '</fieldset>'),
			
			
			array('type' => 'code', 	'html' => '<fieldset><legend>Тип страницы</legend>'),
			array('type' => 'select', 	'name' => 'page_type', 'title' => 'Тип страницы', 'options' => $app['page.types'], 'value' => $row['page_type']),
			array('type' => 'select', 	'name' => 'text_position', 'title' => 'Расположение текста', 'options' => array('Сверху' => 0, 'Снизу' => 1), 'value' => $row['text_position']),
			array('type' => 'text', 	'name' => 'gallery_title', 'title' => 'Наименование галереи', 'value' => $row['gallery_title'], 'prim' => 'для страниц типа «Текстовая с галереей»'),
			array('type' => 'code', 	'html' => '</fieldset>'),
			
			
			array('type' => 'code', 	'html' => '<fieldset><legend>Дополнительные настройки</legend>'),
			array('type' => 'checkbox', 'name' => 'activation', 'title' => 'Отображается НА САЙТЕ?', 'type' => 'checkbox', 'value' => 1, 'checked' => $row['activation']),
			//array('type' => 'checkbox', 'name' => 'topnews', 'title' => 'Новость является ТОПовой?', 'type' => 'checkbox', 'value' => 1, 'checked' => $row['topnews']),
			array('type' => 'code', 	'html' => '</fieldset>')
				
			//array('name' => 'type_news', 'title' => 'Является НОВОСТЬЮ или АКЦИЕЙ?', 'type' => 'select', 'value' => $row['type_news'], 'options' => array('Новость' => 0, 'Акция' => 1))*/
		);
		
		if( $submit )
		{
			$this->form->saveIntoDB($fieldset);
			
			redirect($this->path_menu);
		}
		
		$this->form->createEditTMP($fieldset);
	}
	
	/**
	* Подсчет количества записей
	*/
	protected function get_entries_count()
	{
		$sql = '
			SELECT
				COUNT(*) AS total
			FROM
				tcms_publications
			WHERE
				site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				type = ' . $this->db->check_value($this->publication_type);
		$this->db->query($sql);
		$total = $this->db->fetchfield('total');
		$this->db->freeresult();
		
		return $total;
	}
	
	protected function get_insert_data($total)
	{
		return array();
	}
}
