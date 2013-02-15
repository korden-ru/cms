<?php
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

namespace acp;

use acp\models\page_gallery;

class news_gallery extends page_gallery
{
	function __construct()
	{
		parent::__construct();
		
		$this->form->table_name = 'tcms_publications_gallery';
	}

	protected function get_parent_name($pid)
	{
		if( empty($this->parent_row) )
		{
			$this->get_parent_row($pid);
		}
		
		return $this->parent_row['title'];
	}
	
	protected function get_parent_row($pid)
	{
		$sql = '
			SELECT
				*
			FROM
				tcms_publications
			WHERE
				id = ' . $this->db->check_value($pid);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		$this->parent_row = $row;
		
		return $row;
	}
}
