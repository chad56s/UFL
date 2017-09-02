<?php 

	session_start();
	
	include_once('../config.php');
	include_once(APP_ROOT . '/func/leagueUtils.php');
	
	function getNewsFromId($id){
		$aNewsItem = explode('_',$id,4);
		
		$type = $aNewsItem[0];
		$year = $aNewsItem[1];
		$week = $aNewsItem[2];
		
		if(count($aNewsItem) > 3)
			$id = $aNewsItem[3];
		else
			$id = "";
			
		return getNewsItem($year,$week,$type,$id);
	}
	
	if(isset($_GET['id'])){
		//echo $_GET['id']."<BR/>";
		echo getNewsFromId($_GET['id']);
	}
	else
	{
		echo "no id?!";
	}
	
?>
