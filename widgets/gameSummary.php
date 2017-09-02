<?php
  
	include_once('../config.php');
	include_once(OBJECT_ROOT . '/gameSummary.php');
	
	if(isset($_GET['ajaxWidgetGameID']))
	{
		$grev = new GameReview();
		$grev->showReview($_GET['ajaxWidgetGameID']);
	}

?>