<?php

namespace acp;

use acp\models\page_gallery;

/**
* Блоки
*/
class pages_gallery extends page_gallery
{
	/**
	* Название родительского элемента
	*/
	protected function get_parent_name($pid)
	{
		if( empty($this->parent_row) )
		{
			$this->get_parent_row($pid);
		}
		
		return $this->parent_row['page_name'];
	}
	
	protected function get_parent_row($pid)
	{
		$sql = '
			SELECT
				*
			FROM
				' . PAGES_TABLE . '
			WHERE
				page_id = ' . $this->db->check_value($pid);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		$this->parent_row = $row;
		
		return $row;
	}
}
