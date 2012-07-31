<?php	
/**
* @package cms.korden.net
* @copyright (c) 2012
*/

namespace acp;

use acp\models\page;

class phpinfo extends page
{
	public function index()
	{
		ob_start();
		phpinfo();
		$phpinfo = ob_get_clean();
		
		$this->template->assign('PAGE_TEXT', $phpinfo);
		$this->template->file = 'phpinfo.html';
	}
}
