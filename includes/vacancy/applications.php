<?php

namespace app\vacancy;

use app\models\page;

/**
* Анкеты соискателей
*/
class applications extends page
{
	protected $other_anketa = 0;
	
	public function index()
	{
		$sql = '
			SELECT
				va.id,
				va.id_vacancy,
				va.prosmotrena,
				va.name,
				va.name2,
				va.surname,
				date_format(va.birthday, "%d-%m-%Y") AS birthday,
				va.telephone,
				va.email,
				va.date_create,
				va.ip,
				va.title_vacancy AS vacancy_stgrand
			FROM
				tcms_vacancy_applications va
			LEFT JOIN
				tcms_vacancies v ON (v.id = va.id_vacancy)
			WHERE
				va.other_anketa = ' . $this->db->check_value($this->other_anketa) . '
			ORDER BY
				va.prosmotrena ASC,
				va.date_create DESC';
		$this->db->query($sql);
		
		$fl_unview = false;
		$fl_view = false;
		
		while( $row = $this->db->fetchrow() )
		{
			if( !$row['prosmotrena'] )
			{
				$fl_unview = true;
			}
			else
			{
				$fl_view = true;
			}
			
			$this->template->append(((!$row['prosmotrena']) ? 'unview' : 'view'), array(
				'NUM'           => $row['id'],
				'ID'            => $row['id'],
				'NAME'          => $row['surname'] . " " . $row['name'] . " " . $row['name2'],
				'BIRTHDAY'      => $row['birthday'],
				'TELEPHONE'     => $row['telephone'],
				'EMAIL'         => $row['email'],
				'VACANCY_TITLE' => ($row['vacancy_stgrand'] != '') ? $row['vacancy_stgrand'] : '',
				'DATE_CREATE'   => date("d-m-y в H:i", $row['date_create']),
				'IP'            => $row['ip'],
				
				'U_EDIT'   => $this->path_menu . '&mode=edit&id=' . $row['id'],
				'U_PRINT'  => $this->path_menu . '&mode=printing&id=' . $row['id'],
				'U_DELETE' => $this->path_menu . '&mode=delete&id=' . $row['id']
			));
		}
		
		$this->db->freeresult();
		
		$this->template->assign(array(
			'IS_UNVIEW' => $fl_unview,
			'IS_VIEW' 	=> $fl_view
		));
		
		$this->template->file = 'vacancy/applications_index.html';
	}
	
	public function edit()
	{
		$id     = $this->request->variable('id', 0);
		$submit = $this->request->is_set_post('submit');
		
		if( $submit )
		{
			//Редактируем анкету
			$birthday = $this->request->post('birthday', '');
			$tmp = explode('-', $birthday);
			$birthday = !empty($tmp) ? sprintf('%d-%d-%d', $tmp[2], $tmp[1], $tmp[0]) : $birthday;
			
			$sql_ary = array(
				'prosmotrena'           => $this->request->post('prosmotrena', '') == 'on',
				'surname'               => $this->request->post('surname', ''),
				'name'                  => $this->request->post('name', ''),
				'name2'                 => $this->request->post('name2', ''),
				'nationality'           => $this->request->post('nationality', ''),
				'citizenship'           => $this->request->post('citizenship', ''),
				'birthday'              => $birthday,
				'birth_mesto'           => $this->request->post('birth_mesto', ''),
				'sex'                   => $this->request->post('sex', 1),
				'family_status'         => $this->request->post('family_status', ''),
				'address_propiska'      => $this->request->post('address_propiska', ''),
				'address_factich'       => $this->request->post('address_factich', ''),
				'address_registraciya'  => $this->request->post('address_registraciya', ''),
				'telephone'             => $this->request->post('telephone', ''),
				'email'                 => $this->request->post('email', ''),
				'sposob_svyazi'         => $this->request->post('sposob_svyazi', ''),
				'education'             => serialize(($this->request->is_set_post('education') ? 
$this->request->post('education', array(0 => '')) : array())),
				'education_description' => serialize(array(
					'date_ot' => $this->request->post('ed_date_ot', array(0 => '')),
					'date_do' => $this->request->post('ed_date_do', array(0 => '')),
					'vuz'     => $this->request->post('ed_vuz', array(0 => '')),
					'spec'    => $this->request->post('ed_spec', array(0 => ''))
				)),
				'education_additional'  => serialize(array(
					'kurs'     => $this->request->post('ed_kurs', array(0 => '')),
					'company'  => $this->request->post('ed_company', array(0 => '')),
					'time'     => $this->request->post('ed_edtime', array(0 => '')),
					'date_end' => $this->request->post('ed_dateend', array(0 => ''))
				)),
				'trudovaya'             => serialize(array(
					'date_s'  => $this->request->post('tr_date_s', array(0 => '')),
					'date_po' => $this->request->post('tr_date_po', array(0 => '')),
					'vacancy' => $this->request->post('tr_vacancy', array(0 => '')),
					'obyaz'   => $this->request->post('tr_obyaz', array(0 => '')),
					'uhod'    => $this->request->post('tr_uhod', array(0 => ''))
				)),
				'dohod'                 => $this->request->post('dohod', ''),
				'kak_dohod'             => $this->request->post('kak_dohod', ''),
				'kachestva'             => serialize(($this->request->is_set_post('kachestva') ? $this->request->post('kachestva', array(0 => '')) : array())),
				'recomendaciya'         => serialize(array(
					'fio'     => $this->request->post('rec_fio', array(0 => '')),
					'company' => $this->request->post('rec_company', array(0 => '')),
					'phone'   => $this->request->post('rec_phone', array(0 => ''))
				)),
			);
			
			$sql = '
				UPDATE
					tcms_vacancy_applications
				SET
					' . $this->db->build_array('UPDATE', $sql_ary) . '
				WHERE
					id = ' . $this->db->check_value($id);
			$this->db->query($sql);
			
			redirect($this->path_menu);				
		}

		$sql = '
			SELECT
				*,
				date_format(birthday, "%d-%m-%Y") AS birthday
			FROM
				tcms_vacancy_applications
			WHERE
				id = ' . $this->db->check_value($id);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if( !$row )
		{
			redirect($this->path_menu);
		}
			
		$checked = ' checked';
		
		switch( $row['family_status'] )
		{
			case 'холост/не замужем': $fstatus = 1; break;
			case 'женат/замужем': $fstatus = 2; break;
			case 'разведен(а)': $fstatus = 3; break;
			case 'вдова(ец)': $fstatus = 4; break;
		}

		$ed = unserialize($row['education']);
		$edu = unserialize($row['education_description']);
		
		for( $i = 0; $i < 3; $i++ )
		{
			//образование (подробнее)
			$this->template->append('edu', array(
				'DATE_OT' => isset($edu['date_ot'][$i]) ? $edu['date_ot'][$i] : "",
				'DATE_DO' => isset($edu['date_do'][$i]) ? $edu['date_do'][$i] : "",
				'VUZ'     => isset($edu['vuz'][$i]) ? $edu['vuz'][$i] : "",
				'SPEC'    => isset($edu['spec'][$i]) ? $edu['spec'][$i] : ""
			));
		}
		
		$edu_dop = unserialize($s['education_additional']);
		
		for( $i = 0; $i < 5; $i++ )
		{
			$this->template->append('edu_dop', array(
				'KURS'     => isset($edu_dop['kurs'][$i]) ? $edu_dop['kurs'][$i] : "",
				'COMPANY'  => isset($edu_dop['company'][$i]) ? $edu_dop['company'][$i] : "",
				'TIME'     => isset($edu_dop['time'][$i]) ? $edu_dop['time'][$i] : "",
				'DATE_END' => isset($edu_dop['date_end'][$i]) ? $edu_dop['date_end'][$i] : ""
			));
		}
		
		$tr = unserialize($s['trudovaya']);
		
		for( $i = 0; $i < 3; $i++ )
		{
			$this->template->append('tr', array(
				'DATE_S'  => isset($tr['date_s'][$i]) ? $tr['date_s'][$i] : "",
				'DATE_PO' => isset($tr['date_po'][$i]) ? $tr['date_po'][$i] : "",
				'VACANCY' => isset($tr['vacancy'][$i]) ? $tr['vacancy'][$i] : "",
				'OBYAZ'   => isset($tr['obyaz'][$i]) ? $tr['obyaz'][$i] : "",
				'UHOD'    => isset($tr['uhod'][$i]) ? $tr['uhod'][$i] : ""
			));
		}
		
		switch( $row['dohod'] )
		{
			case 'от 100$': $dhd = 1; break;
			case 'от 100$ до 500$': $dhd = 2; break;
			case 'от 500$ до 1000$': $dhd = 3; break;
			case 'от 1000$ до 2000$': $dhd = 4; break;
			case 'свыше 2000$': $dhd = 5; break;
		}
		
		switch( $row['kak_dohod'] )
		{
			case 'Я заслуживаю большего': $kdhd = 1; break;
			case 'Я неудовлетворен': $kdhd = 2; break;
			case 'Я доволен': $kdhd = 3; break;
			case 'Я очень доволен': $kdhd = 4; break;
			case 'Мне переплачивают': $kdhd = 5; break;
		}
		
		$kachestva = unserialize($row['kachestva']);
		$rec = unserialize($row['recomendaciya']);
		
		for( $i = 0; $i < 3; $i++ )
		{
			$this->template->append('rec', array(
				'FIO'     => isset($rec['fio'][$i]) ? $rec['fio'][$i] : "",
				'COMPANY' => isset($rec['company'][$i]) ? $rec['company'][$i] : "",
				'PHONE'   => isset($rec['phone'][$i]) ? $rec['phone'][$i] : ""
			));
		}
		
		$this->template->assign(array(
			'VACANCY_TITLE' => $row['title_vacancy'],
			'PROSMOTRENA'   => $row['prosmotrena'],
			'SURNAME'       => $row['surname'],
			'NAME'          => $row['name'],
			'NAME2'         => $row['name2'],
			'NATIONALITY'   => $row['nationality'],
			'CITIZENSHIP'   => $row['citizenship'],
			'BIRTHDAY'      => $row['birthday'],
			'BIRTH_MESTO'   => $row['birth_mesto'],
			'FSTATUS'       => $fstatus,
			'SEX'           => $row['sex'],
			'A_P'           => $row['address_propiska'],
			'A_F'           => $row['address_factich'],
			'A_R'           => $row['address_registraciya'],
			'PHONE'         => $row['telephone'],
			'EMAIL'         => $row['email'],
			'SVYAZ'         => $row['sposob_svyazi'],
		
			//образование (чекбоксы)
			'ED1' => isset($ed[0]) ? $checked : "",
			'ED2' => isset($ed[1]) ? $checked : "",
			'ED3' => isset($ed[2]) ? $checked : "",
			'ED4' => isset($ed[3]) ? $checked : "",
			'ED5' => isset($ed[4]) ? $checked : "",
	
			'DOHOD'     => $dhd,
			'KAK_DOHOD' => $kdhd,
	
			'KA1'  => isset($kachestva[0]) ? $checked : "",
			'KA2'  => isset($kachestva[1]) ? $checked : "",
			'KA3'  => isset($kachestva[2]) ? $checked : "",
			'KA4'  => isset($kachestva[3]) ? $checked : "",
			'KA5'  => isset($kachestva[4]) ? $checked : "",
			'KA6'  => isset($kachestva[5]) ? $checked : "",
			'KA7'  => isset($kachestva[6]) ? $checked : "",
			'KA8'  => isset($kachestva[7]) ? $checked : "",
			'KA9'  => isset($kachestva[8]) ? $checked : "",
			'KA10' => isset($kachestva[9]) ? $checked : "",
			'KA11' => isset($kachestva[10]) ? $checked : "",
			'KA12' => isset($kachestva[11]) ? $checked : "",
		));
		
		$this->template->file = 'vacancy/applications_edit.html';
	}
	
	public function delete()
	{
		$id = $this->request->variable('id', 0);
		
		$sql = '
			DELETE
			FROM
				tcms_vacancy_applications
			WHERE
				id = ' . $this->db->check_value($id);
		$this->db->query($sql);
		
		redirect($this->path_menu);
	}
	
	public function printing()
	{
		$id = $this->request->variable('id', 0);
		
		$sql = '
			SELECT
				*,
				date_format(birthday, "%d-%m-%Y") AS birthday
			FROM
				tcms_vacancy_applications
			WHERE
				id = ' . $this->db->check_value($id);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if( !$row )
		{
			redirect($this->path_menu);
		}
		
		switch( $row['family_status'] )
		{
			case 'холост/не замужем': $fstatus = 1; break;
			case 'женат/замужем': $fstatus = 2; break;
			case 'разведен(а)': $fstatus = 3; break;
			case 'вдова(ец)': $fstatus = 4; break;
		}

		$ed = unserialize($row['education']);
		$edu = unserialize($row['education_description']);
		
		for( $i = 0, $len = sizeof($edu['date_ot']); $i < $len; $i++ )
		{
			$this->template->append('edu', array(
				'DATE_OT' => $edu['date_ot'][$i],
				'DATE_DO' => $edu['date_do'][$i],
				'VUZ'     => $edu['vuz'][$i],
				'SPEC'    => $edu['spec'][$i]
			));
		}

		$edu_dop = unserialize($row['education_additional']);
		
		for( $i = 0, $len = sizeof($edu_dop['kurs']); $i < $len; $i++ )
		{
			$this->template->append('edu_dop', array(
				'KURS'     => $edu_dop['kurs'][$i],
				'COMPANY'  => $edu_dop['company'][$i],
				'TIME'     => $edu_dop['time'][$i],
				'DATE_END' => $edu_dop['date_end'][$i]
			));
		}
		
		$tr = unserialize($row['trudovaya']);
		
		for( $i = 0, $len = sizeof($tr['date_s']); $i < $len; $i++ )
		{
			$this->template->append('tr', array(
				'DATE_S'  => $tr['date_s'][$i],
				'DATE_PO' => $tr['date_po'][$i],
				'VACANCY' => $tr['vacancy'][$i],
				'OBYAZ'   => $tr['obyaz'][$i],
				'UHOD'    => $tr['uhod'][$i]
			));
		}

		$dhd = array();
		
		switch( $row['dohod'] )
		{
			case 'от 100$': $dhd[0] = 1; break;
			case 'от 100$ до 500$': $dhd[1] = 1; break;
			case 'от 500$ до 1000$': $dhd[2] = 1; break;
			case 'от 1000$ до 2000$': $dhd[3] = 1; break;
			case 'свыше 2000$': $dhd[4] = 1; break;
		}

		$kdhd = array();
		
		switch( $row['kak_dohod'] )
		{
			case 'Я заслуживаю большего': $kdhd[0] = 1; break;
			case 'Я неудовлетворен': $kdhd[1] = 1; break;
			case 'Я доволен': $kdhd[2] = 1; break;
			case 'Я очень доволен': $kdhd[3] = 1; break;
			case 'Мне переплачивают': $kdhd[4] = 1; break;
		}

		$kachestva = unserialize($row['kachestva']);

		$rec = unserialize($row['recomendaciya']);
		
		for( $i = 0, $len = sizeof($rec['fio']); $i < $len; $i++ )
		{
			$this->template->append('rec', array(
				'FIO'     => $rec['fio'][$i],
				'COMPANY' => $rec['company'][$i],
				'PHONE'   => $rec['phone'][$i]
			));
		}

		$this->template->assign(array(
			'VACANCY'     => $row['title_vacancy'],
			'SURNAME'     => $row['surname'],
			'NAME'        => $row['name'],
			'NAME2'       => $row['name2'],
			'NATIONALITY' => $row['nationality'],
			'CITIZENSHIP' => $row['citizenship'],
			'BIRTHDAY'    => $row['birthday'],
			'BIRTH_MESTO' => $row['birth_mesto'],
			'FSTATUS'     => $fstatus,
			'SEX'         => $row['sex'],
			'A_P'         => $row['address_propiska'],
			'A_F'         => $row['address_factich'],
			'A_R'         => $row['address_registraciya'],
			'PHONE'       => $row['telephone'],
			'EMAIL'       => $row['email'],
			'SVYAZ'       => $row['sposob_svyazi'],

			'ED_1'        => isset($ed[0]) ? '_act' : '',
			'ED_2'        => isset($ed[1]) ? '_act' : '',
			'ED_3'        => isset($ed[2]) ? '_act' : '',
			'ED_4'        => isset($ed[3]) ? '_act' : '',
			'ED_5'        => isset($ed[4]) ? '_act' : '',

			'DHD1'        => isset($dhd[0]) ? '_act' : '',
			'DHD2'        => isset($dhd[1]) ? '_act' : '',
			'DHD3'        => isset($dhd[2]) ? '_act' : '',
			'DHD4'        => isset($dhd[3]) ? '_act' : '',
			'DHD5'        => isset($dhd[4]) ? '_act' : '',

			'KDHD1'       => isset($kdhd[0]) ? '_act' : '',
			'KDHD2'       => isset($kdhd[1]) ? '_act' : '',
			'KDHD3'       => isset($kdhd[2]) ? '_act' : '',
			'KDHD4'       => isset($kdhd[3]) ? '_act' : '',
			'KDHD5'       => isset($kdhd[4]) ? '_act' : '',

			'KA1'         => isset($kachestva[0]) ? '_act' : '',
			'KA2'         => isset($kachestva[1]) ? '_act' : '',
			'KA3'         => isset($kachestva[2]) ? '_act' : '',
			'KA4'         => isset($kachestva[3]) ? '_act' : '',
			'KA5'         => isset($kachestva[4]) ? '_act' : '',
			'KA6'         => isset($kachestva[5]) ? '_act' : '',
			'KA7'         => isset($kachestva[6]) ? '_act' : '',
			'KA8'         => isset($kachestva[7]) ? '_act' : '',
			'KA9'         => isset($kachestva[8]) ? '_act' : '',
			'KA10'        => isset($kachestva[9]) ? '_act' : '',
			'KA11'        => isset($kachestva[10]) ? '_act' : '',
			'KA12'        => isset($kachestva[11]) ? '_act' : '',

			'DATE_CREATE' => date('d.m.Y г.', $row['date_create'])
		));
		
		$this->db->freeresult();
		
		$this->template->file = 'vacancy/applications_printing.html';
	}
}
