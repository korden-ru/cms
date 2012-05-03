<?php

	if(!defined('IN_ACP'))
		exit('Incorrect file access');

	function sort_1_level($array,$sort_field='sort',$parent_field='parent',$id_field='id')
	{
		$parent=-1;
		$res=array();
		for($i=0;$i<sizeof($array);$i++)
		{
			if($array[$i][$parent_field]==0)
			{
				$res[]=$array[$i];
				for($j=$i+1;$j<sizeof($array);$j++)
				{
					if($array[$i][$id_field]==$array[$j][$parent_field])
					{
						$res[]=$array[$j];
					}
				}
			}
		}

		return $res;
	}

	/* Check GROUP permissions
	----------------------------------------*/
	if(!empty($user->userPerms) && (isset($user->userPerms[0]) && $user->userPerms[0] != 'ALL'))
	{
		$qWhere = ' AND (';
		$bWhere = ' AND (';

		$i = 1;
		$count = count($user->userPerms);
		foreach($user->userPerms as $key => $moduleId)
		{
			$orWhere = $i!= $count?' OR ':null;

			$qWhere .= 'a.id = '.$moduleId.$orWhere;
			$bWhere .= 'b.id = '.$moduleId.$orWhere;
			$i++;
		}

		$qWhere .= ') ';
		$bWhere .= ') ';
	}
	else
		$qWhere = $bWhere = null;
	
	$sql='SELECT a.* 
		FROM '.SQL_PREFIX.'modules AS a 
		WHERE a.tab=0 '.$qWhere.'
		ORDER BY a.sort ASC';
	$result=$app['db']->query($sql);
	while($row=$app['db']->fetchrow($result))
	{
		$template->append('main_tabs',array(
						
							'ACTCLASS'	=> $row['id']==$tab?'id="activetab"':'',
							'ID'		=> $row['id'],
							'TITLE'		=> $row['title']
							));
	}
		
	$app['db']->freeresult();
		
		
	$sql='SELECT a.*, (SELECT COUNT(*) FROM '.SQL_PREFIX.'modules as b WHERE a.id = b.parent AND b.tab='.$tab.''.$bWhere.') as pcount  
		FROM '.SQL_PREFIX.'modules  AS a
		WHERE a.tab='.$tab.''.$qWhere.'
		ORDER BY a.parent ASC, a.sort ASC';
	$result=$app['db']->query($sql);	
	$found=false;
	while($row=$app['db']->fetchrow($result))
	{
		$menus[]=$row;
		$found=true;
	}
	$app['db']->freeresult();
	$template->append('left_menu',array(),true);
	if($found)
	{
		$menus=sort_1_level($menus);
		foreach($menus as $m)
		{
			if($m['id']==$menu)
			{
				$template->assign(array('MODULE_TITLE' => $m['title']));
			}

			$template->append('left_menu',array(
							'PCOUNT'	=> $m['pcount'],
							'ACTIVE'	=> ($m['id']==$menu)?'id="activemenu"':'',
							'HREF'		=> $m['class'] && $m['mode']? $config['acp.root_path'] . '?tab='.$tab.'&menu='.$m['id']:'',
							'TITLE'		=> $m['title']
							));
		}		
	}
	
	/* NOTES
	------------------------------------------*/
	
	$submit = isset($_POST['submit_notes'])?true:false;
	
	if($submit)
	{
		$notes = $request->post('notes', '');
		$app['db']->query('UPDATE '.SQL_PREFIX.'notes SET text = \''.htmlspecialchars_decode($notes).'\'');
		redirect($config['acp.root_path']);
	}
	
	$q = 'SELECT text
			FROM '.SQL_PREFIX.'notes';
	$r = $app['db']->query($q);
	$s = $app['db']->fetchrow($r);
	$template->assign(array('NOTES' => htmlspecialchars($s['text'])));
	
	
	
?>