<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

namespace acp;

use acp\models\page;

/**
* Галерея
*/
class gallery extends page
{
	function __construct()
	{
		parent::__construct();
		
		$this->form->addButtonText = 'Добавить новую галерею';
		$this->form->sortElements  = true;
		$this->form->titleTable    = 'Просмотр галерей';
	}

	/**
	* Список галерей
	*/
	public function index()
	{
		$sql = '
			SELECT
				a.id,
				a.image,
				a.title,
				a.activation,
				a.sort,
				(SELECT COUNT(b.id) FROM ' . $this->form->table_name . '_photos b WHERE b.id_row = a.id) AS total_photos
			FROM
				' . $this->form->table_name . ' a
			ORDER BY
				a.sort ASC';
		$this->db->query($sql);
		$data = array();
		
		while( $row = $this->db->fetchrow() )
		{
			$row['activation'] = ( $row['activation'] ) ? '<center><img src="images/tick.png" alt=""></center>' : '';
			$row['add_buttons'][] = '<input class="button1" style="width:100%;" type="button" value="Фото (' . $row['total_photos'] . ')" onclick="Redirect(arguments, \'' . $this->path_menu . '&class=gallery_photos&pid=' . $row['id'] . '\');">';
			$row['title'] = htmlspecialchars($row['title']);
			$row['image'] = $row['image'] ? '<a href="/uploads/' . $this->form->upload_folder . '/' . $row['image'] . '" class="fancybox-gallery" rel="gallery" title="' . $row['title'] . '"><img src="/uploads/' . $this->form->upload_folder . '/sm/' . $row['image'] . '" width="70"></a>' : '';
			
			unset($row['total_photos']);
			
			$data[] = $row;
		}
		
		$this->form->createShowTMP(array(
			'ID',
			'Фото',
			'Наименование',
			'<center><img src="images/i_show_in_site.png" title="Отображается на сайте?" alt=""></center>
		'), $data, array(
			25,
			70,
			'',
			25
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
				' . $this->form->table_name;
		$this->db->query($sql);
		$total = $this->db->fetchfield('total') + 1;
		$this->db->freeresult();
		
		$sql_ary = array(
			'title'      => 'Галерея №' . $total,
			'modifyurl'  => modifyUrl('Галерея №' . $total),
			'activation' => 0,
			'sort'       => $total
		);
		
		$sql = 'INSERT INTO ' . $this->form->table_name . ' ' . $this->db->build_array('INSERT', $sql_ary);
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
				' . $this->form->table_name . '
			WHERE
				id = ' . $this->db->check_value($id);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if( $row )
		{
			if( $row['image'] )
			{
				unlink(SITE_DIR . 'uploads/' . $this->form->upload_folder . '/original/' . $row['image']);
				unlink(SITE_DIR . 'uploads/' . $this->form->upload_folder . '/sm/' . $row['image']);
				unlink(SITE_DIR . 'uploads/' . $this->form->upload_folder . '/' . $row['image']);
			}
			
			$sql = '
				DELETE
				FROM
					' . $this->form->table_name . '
				WHERE
					id = ' . $this->db->check_value($id);
			$this->db->query($sql);
			
			/**
			* Удаление фотографий внутри галереи
			*/
			$sql = '
				SELECT
					image
				FROM
					' . $this->form->table_name . '_photos
				WHERE
					id_row = ' . $this->db->check_value($id);
			$this->db->query($sql);
			
			while( $row = $this->db->fetchrow() )
			{
				if( !$row['image'] )
				{
					continue;
				}
				
				unlink(SITE_DIR . 'uploads/' . $this->form->upload_folder . '/original/' . $row['image']);
				unlink(SITE_DIR . 'uploads/' . $this->form->upload_folder . '/sm/' . $row['image']);
				unlink(SITE_DIR . 'uploads/' . $this->form->upload_folder . '/' . $row['image']);
			}
			
			$this->db->freeresult();
			
			$sql = '
				DELETE
				FROM
					' . $this->form->table_name . '_photos
				WHERE
					id_row = ' . $this->db->check_value($id);
			$this->db->query($sql);
		}
		
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
		
		$ajax_delete = array(
			'url'   => 'includes/ajax/delete_file.php', 
			'param' => "{ id: $id, table: '" . $this->form->table_name . "', column: 'image', dir: '" . $this->form->upload_folder . "' }"
		);	
			
		$resize = array(
			'root'     => array($this->config['thumbnail.source_width'], $this->config['thumbnail.source_height'], true, false),
			'original' => array($this->config['thumbnail.source_width'], $this->config['thumbnail.source_height'], false, false),
			'sm'       => array($this->config['thumbnail.width'], $this->config['thumbnail.height'], false, $this->config['thumbnail.crop'])
		);
		
		$fieldset = array(
			array('name' => 'title', 'title' => 'Наименование галереи', 'type' => 'text', 'value' => $row['title']),
			array('name' => 'description', 'title' => 'Краткое описание', 'type' => 'textarea', 'value' => $row['description'], 'height' => 160),
			array('name' => 'image', 'title' => 'Изображение', 'type' => 'file', 'value' => $row['image'], 'old' => 'old_image', 'resize' => $resize, 'ajax_delete' => $ajax_delete),


			array('type' => 'code', 	'html' => '<fieldset><legend>Информация для продвижения сайта</legend>'),
			array('type' => 'text', 	'name' => 'seo_title', 'title' => 'SEO | Заголовок страницы', 'value' => $row['seo_title']),
			array('type' => 'text', 	'name' => 'seo_keys', 'title' => 'SEO | Ключевые слова', 'value' => $row['seo_keys']),
			array('type' => 'text', 	'name' => 'seo_desc', 'title' => 'SEO | Описание страницы', 'value' => $row['seo_desc']),
			array('type' => 'button', 	'name' => 'autoins', 'title' => 'Подобрать информацию для продвижения сайта', 'value' => 'Автоподбор', 'onclick' => "AjaxClickFormButton('title', ['seo_title','seo_keys','seo_desc'])"),
			array('type' => 'code', 	'html' => '</fieldset>'),

			
			array('name' => 'activation', 'title' => 'Отображается НА САЙТЕ?', 'type' => 'checkbox', 'value' => 1, 'checked' => $row['activation']),
			array('name' => 'modifyurl', 'title' => 'Url', 'type' => 'hidden', 'value' => modifyUrl($row['title']." ".$row['id']))
		);
		
		if( $submit )
		{
			$this->form->saveIntoDB($fieldset);
			
			redirect($this->path_menu);
		}

		$this->form->createEditTMP($fieldset);
	}
	
	/**
	* Список фотографий в галерее
	*/
	public function photos()
	{
		$id = $this->request->variable('id', 0);
		
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
		
		if( !$row )
		{
			redirect($this->path_menu);
		}
		
		$sql = '
			SELECT
				id,
				image,
				title
			FROM
				' . $this->form->table_name . '_photos
			WHERE
				id_row = ' . $this->db->check_value($id) . '
			ORDER BY
				sort ASC,
				id ASC';
		$this->db->query($sql);
		$photos = array();
		
		while( $data = $this->db->fetchrow() )
		{
			$photos[] = array(
				'id'       => $data['id'],
				'filename' => $data['image'],
				'about'    => $data['title']
			);
		}
		
		$this->db->freeresult();
		
		$this->template->assign(array(
			'mysql_table'  => $this->form->table_name,
			'folder'       => $this->form->upload_folder,
			'title_url'    => $this->form->U_EDIT . $id,
			'title'        => $row['title'],
			'id_gallery'   => $id,
			'photos'       => $photos,
			'action_title' => $this->path_menu . '&mode=set_photo_titles&id=' . $id,
			'phpsessid'    => session_id()
		));
		
		$this->template->file = 'swfupload.html';
	}

	/**
	* Установка подписей к фотографиям
	*/
	public function set_photo_titles()
	{
		$id          = $this->request->variable('id', 0);
		$photo_about = $this->request->post('photo_about', array('' => ''));
		$submit      = $this->request->is_set_post('save_about');
		
		if( $submit )
		{
			foreach( $photo_about as $k => $v )
			{
				$sql = '
					UPDATE
						' . $this->form->table_name . '_photos
					SET
						title = ' . $this->db->check_value($v) . '
					WHERE
						id = ' . $this->db->check_value($k);
				$this->db->query($sql);
			}
		}
		
		redirect($this->path_menu . '&mode=photos&id=' . $id);
	}
}
