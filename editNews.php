<?php 

session_start();

include_once('config.php');
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
		
	echo $year . $week . $type . $id;
	return getNews($year,$week,$type,$id);
}

function writeNewsFromId($id,$news){
	$aNewsItem = explode('_',$id,4);
	
	$type = $aNewsItem[0];
	$year = $aNewsItem[1];
	$week = $aNewsItem[2];
	
	if(count($aNewsItem) > 3)
		$id = $aNewsItem[3];
	else
		$id = "";
	
	writeNews($year,$week,$type,$id,$news);
}
?>
<html>
	<head>
		<body>
			
		
<?php
if(isset($_POST['editNews']))
{
	
	writeNewsFromId($_POST['id'],$_POST['editNews']);
	echo "News updated!";
	
}

elseif(isset($_SESSION['username']))
{
	if(isset($_GET['id']))
	{
		
		$news = getNewsFromId($_GET['id']);
		echo "
		<form action='editNews.php' method='POST'>
			<input type='hidden' name='id' value='" . $_GET['id'] . "'>
			<textarea rows='20' cols='100' name='editNews'>" . $news . "</textarea>
			<br/>
			<input type='submit' name='submit' value='submit'>
		</form>
		";
	}
	else{
		echo "error!  No id";
	}
}
else{
	
	echo "error!  Not logged in";
}

?>

	
		</body>
		
	</head>
</html>