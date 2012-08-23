<?php

namespace acp;

use acp\models\page;

/**
* Баннеры
*/
class banners extends page
{
	function __construct()
	{
		parent::__construct();
		
		$this->form->addButtonText = 'Добавить новый баннер';
		$this->form->titleTable    = 'Просмотр баннеров';
	}

	public function index()
	{
		$sql = '
			SELECT
				a.id,
				a.title,
				a.url,
				b.title AS type_title,
				a.activation
			FROM
				' . $this->form->table_name . ' a
			LEFT JOIN
				' . BANNERS_TYPES_TABLE . ' b ON (b.id = a.type_id)
			WHERE
				a.site_id = ' . $this->db->check_value($this->site_id) . '
			ORDER BY
				a.type_id ASC,
				a.id ASC';
		$this->db->query($sql);
		$data = array();
		
		while( $row = $this->db->fetchrow() ) 
		{
			$row['activation'] = $row['activation'] ? '<center><img src="images/tick.png"></center>' : '';
			
			$data[] = $row;
		}

		$widths = array("25", "100", "", "150", "25");
		$this->form->createShowTMP(array(
			'ID',
			'Баннер',
			'URL',
			'Тип',
			'<center><img src="images/i_show_in_site.png" title="Отображается на сайте?"></center>
		'), $data, array(
			25,
			200,
			"",
			100,
			25,
		));
	}
	
	public function add()
	{
		$sql = '
			SELECT
				COUNT(id) AS total
			FROM
				' . $this->form->table_name;
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		$sql_ary = array(
			'site_id' => $this->site_id,
			'title'   => 'Новый баннер №' . ($row['total'] + 1),
		);
		
		$sql = 'INSERT INTO ' . $this->form->table_name . ' ' . $this->db->build_array('INSERT', $sql_ary);
		$this->db->query($sql);
		
		redirect($this->form->U_EDIT . $this->db->insert_id());
	}
	
	public function delete()
	{
		$id = $this->request->variable('id', 0);
		
		$sql = '
			SELECT
				image
			FROM
				' . $this->form->table_name . '
			WHERE
				site_id = ' . $this->db->check_value($id) . '
			AND
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
					site_id = ' . $this->db->check_value($this->site_id) . '
				AND
					id = ' . $this->db->check_value($id);
			$this->db->query($sql);
		}
		
		redirect($this->path_menu);
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
				site_id = ' . $this->db->check_value($this->site_id) . '
			AND
				id = ' . $this->db->check_value($id);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		$sql = '
			SELECT
				*
			FROM
				' . BANNERS_TYPES_TABLE . '
			WHERE
				activation = 1
			ORDER BY
				sort ASC';
		$this->db->query($sql);
		$types = array('' => 0);
		
		while( $type = $this->db->fetchrow() )
		{
			$types[sprintf('%s (%dx%d)', $type['title'], $type['width'], $type['height'])] = $type['id'];
		}
		
		$this->db->freeresult();

		$fieldset = array(
			array('type' => 'text', 'name' => 'title', 'title' => 'Название баннера', 'value' => $row['title']),
			array('type' => 'select', 'name' => 'type_id', 'title' => 'Тип баннера', 'options' => $types, 'value' => $row['type_id']),
			array('type' => 'text', 'name' => 'url', 'title' => 'URL', 'value' => $row['url'], 'prim' => 'Ссылка вида http://example.com/'),
			array('type' => 'file', 'name' => 'image', 'title' => 'Баннер', 'value' => $row['image'], 'old' => 'old_image'),
			
			
			array('type' => 'checkbox', 'name' => 'activation', 'title' => 'Отображается НА САЙТЕ?', 'value' => 1, 'checked' => $row['activation']),
			array('type' => 'hidden', 'name' => 'modifyurl', 'title' => '', 'value' => ''),
		);
	
		if( $submit )
		{
			$this->form->saveIntoDB($fieldset);
			$this->remove_cache();
			
			redirect($this->path_menu);
		}

		$this->form->createEditTMP($fieldset);
	}

	/**
	* Удаление кэшированного списка баннеров
	*/
	private function remove_cache()
	{
		$site_info = get_site_info_by_id($this->site_id);
		
		$sql = '
			SELECT
				*
			FROM
				' . BANNERS_TYPES_TABLE;
		$this->db->query($sql);
		
		while( $row = $this->db->fetchrow() )
		{
			$this->cache->_delete(sprintf('%s_banners_%d_%s', $site_info['domain'], $row['id'], $site_info['language']));
		}
		
		$this->db->freeresult();
		$this->cache->delete('banners_types');
	}
}
