<?php 

session_start();

include_once('../config.php');
include_once(APP_ROOT . '/func/leagueUtils.php');

function writeNewsFromId($id,$news){
	$aNewsItem = explode('_',$id,4);
	
	$type = $aNewsItem[0];
	$year = $aNewsItem[1];
	$week = $aNewsItem[2];
	
	if(count($aNewsItem) > 3)
		$id = $aNewsItem[3];
	else
		$id = "";
	
	writeNews($year,$week,$type,$id,stripslashes($news));
}


if(isset($_POST['editNews']) && isset($_SESSION['username']))
{
	
	writeNewsFromId($_POST['id'],$_POST['editNews']);
	echo "News updated!";
	
}

elseif (isset($_SESSION['username'])){
	
	echo "error!  No request made";
}

else {
	echo "error! Not logged in";
}



?>
