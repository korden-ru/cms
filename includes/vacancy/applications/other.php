<?php

namespace app\vacancy;

/**
* Анкеты соискателей
*/
class applications_other extends applications
{
	function __construct()
	{
		parent::__construct();
		
		$this->other_anketa = 1;
	}
}
