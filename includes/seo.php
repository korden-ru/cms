<?php

namespace acp;

use acp\models\page;

/**
* SEO инструменты
*/
class seo extends page
{
	public $seo_config = array();
	
	/**
	* Предустановки
	*/
	public function _setup()
	{
		$sql = '
			SELECT
				*
			FROM
				' . $this->form->table_name;
		$this->db->query($sql);
		$this->seo_config = $this->db->fetchrow();
		$this->db->freeresult();
	}
	
	public function index()
	{
		redirect($this->path_menu . '&mode=counters');
	}
	
	/**
	* Счетчики
	*/
	public function counters()
	{
		$submit = $this->request->is_set_post('submit');
		
		if( $submit )
		{
			$counters = htmlspecialchars_decode($this->request->variable('counters', ''));
			
			$sql = '
				UPDATE
					' . $this->form->table_name . '
				SET
					counters = ' . $this->db->check_value($counters);
			$this->db->query($sql);
			
			redirect($this->path_menu . '&mode=counters');
		}
		
		$this->form->U_ACTION = $this->path_menu . '&mode=counters';
		$this->form->createEditTMP(array(
			array('type' => 'code', 'html' => '<fieldset><legend>Вставьте HTML-код счетчика(ов)</legend><textarea name="counters" style="width: 99%;" rows="20">' . $this->seo_config['counters'] . '</textarea></fieldset>')
		));
	}
	
	/**
	* Google Webmaster
	*/
	public function google_web()
	{
		$submit = $this->request->is_set_post('submit');
		
		if( $submit )
		{
			$google_web = htmlspecialchars_decode($this->request->variable('google_web', ''));
			
			$sql = '
				UPDATE
					' . $this->form->table_name . '
				SET
					google_web = ' . $this->db->check_value($google_web);
			$this->db->query($sql);
			
			redirect($this->path_menu . '&mode=google_web');
		}

		$site_url = rtrim($this->config['site_url'], '/');
		$site_url = str_ireplace('http://', '', $site_url);
		
		$html = '
		<style type="text/css">a.hr { text-decoration: underline }</style>
		<fieldset><legend>Подключение к Google Webmaster Tools</legend>
		
		<p>Для подтверждения прав на управление сайтом в Google Webmaster Tools:</p>
		<ol style="margin-left: 25px;">
			<li>Перейдите на сайт <a class="hr" href="http://www.google.com/webmasters/tools/" target="_blank">http://www.google.com/webmasters/tools/</a></li>
			<li>Добавьте свой сайт, перейдя по ссылке «Добавить сайт».</li>
			<li>В форме подтверждения прав на управление сайтом выберите <strong>метод подтверждения «Добавить мета-тег»</strong>.</li>
			<li>После выбора способа подтверждения Google выдаст Вам код вида<br> &lt;meta name="google-site-verification" content="<strong>код подтверждения</strong>" /&gt;</li>
			<li>Скопируйте код подтверждения и введите в форму ниже.</li>
		</ol>
		<br/>
		<dl>
			<dt><label for="google_web">Код подтверждения для Google: </label></dt>
			<dd><input type="text" style="width: 98%" id="google_web" name="google_web" value="' . $this->seo_config['google_web'] . '"></dd>
		</dl>
		</fieldset>';
		
		$this->form->createEditTMP(array(
			array('type' => 'code', 'html' => $html)
		));
	}

	/**
	* Яндекс-Метрика
	*/
	public function metrika()
	{
		$submit = $this->request->is_set_post('submit');
		
		if( $submit )
		{
			$metrika = htmlspecialchars_decode($this->request->variable('metrika', ''));
			
			$sql = '
				UPDATE
					' . $this->form->table_name . '
				SET
					metrika = ' . $this->db->check_value($metrika);
			$this->db->query($sql);
			
			redirect($this->path_menu . '&mode=metrika');
		}
		
		$this->form->U_ACTION = $this->path_menu . '&mode=metrika';
		$this->form->createEditTMP(array(
			array('type' => 'code', 'html' => '<fieldset><legend>Вставьте HTML-код Яндекс-Метрики</legend><textarea name="metrika" style="width: 99%;" rows="20">' . $this->seo_config['metrika'] . '</textarea></fieldset>')
		));
	}
	
	/**
	* Яндекс-Вебмастер
	*/
	public function yandex_web()
	{
		$submit = $this->request->is_set_post('submit');
		
		if( $submit )
		{
			$yandex_web = htmlspecialchars_decode($this->request->variable('yandex_web', ''));
			
			$sql = '
				UPDATE
					' . $this->form->table_name . '
				SET
					yandex_web = ' . $this->db->check_value($yandex_web);
			$this->db->query($sql);
			
			redirect($this->path_menu . '&mode=yandex_web');
		}
		
		$site_url = rtrim($this->config['site_url'], '/');
		$site_url = str_ireplace('http://', '', $site_url);
		
		$html = '
		<style type="text/css">a.hr { text-decoration: underline }</style>
		<fieldset><legend>Подключение к Яндекс.Вебмастер</legend>
		<p>Для подтверждения прав на управление сайтом в Яндекс.Вебмастер:</p>
		<ol style="margin-left: 25px;">
			<li><a class="hr" href="http://passport.yandex.ru/passport?mode=register" target="_blank">Зарегистрируйтесь в службе Яндекс.Паспорт</a>.<br>Если Вы уже зарегистрированы в Яндекс, переходите к <strong>пункту 2</strong>.</li>
			<li><a class="hr" href="http://passport.yandex.ru/passport?mode=auth" target="_blank">Выполните вход в Яндекс</a>, введя Ваш логин и пароль.</li>
			<li>Добавьте сайт '.$site_url.' в Яндекс.Вебмастер по ссылке <a class="hr" href="http://webmaster.yandex.ru/site/add.xml?hostname='.$site_url.'&amp;do=add" target="_blank">http://webmaster.yandex.ru/site/add.xml?hostname='.$site_url.'&amp;do=add</a></li>
			<li>Вам будет предложено два варианта подтверждения прав, выберите второй — «<strong>Разместить мета-тэг</strong>». Не нажимайте «Проверить».</li>
			<li>В выбранном пункте есть следующая строка:<br> &lt;meta name=\'yandex-verification\' content=\'<strong>код подтверждения</strong>\' /&gt;</li>
			<li>Скопируйте код подтверждения и введите в форму ниже, затем нажмите «Отправить».</li>
			<li>После сохранения настроек нажмите кнопку «Проверить» на странице Яндекс.Вебмастера.</li>
		</ol>
		<br/>
		<dl>
			<dt><label for="yandex_web">Код подтверждения для Яндекса:</label></dt>
			<dd><input type="text" style="width: 98%" id="yandex_web" name="yandex_web" value="' . $this->seo_config['yandex_web'] . '"></dd>
		</dl>
		<br/>
		<p>После подключения Яндекс.Вебмастер Вы сможете:</p> 
    	<ul style="margin-left: 25px;">
	    	<li><a class="hr" href="http://webmaster.yandex.ru/site/region.xml" target="_blank">указать регион вашего сайта</a>;</li>
	    	<li><a class="hr" href="http://webmaster.yandex.ru/site/addresses.xml" target="_blank">указать контактные данные и сферу деятельности</a>.</li>
	    </ul>
		<p>Это увеличит приток пользователей из Яндекса, а также позволит отображать адрес и телефон Вашей компании на страницах результатов поиска Яндекса.</p>
		</fieldset>';
		
		$this->form->createEditTMP(array(
			array('type' => 'code', 'html' => $html)
		));
	}
}
