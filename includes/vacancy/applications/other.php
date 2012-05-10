<?php

namespace acp\vacancy;

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
