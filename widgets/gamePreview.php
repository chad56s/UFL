<?php
  
	include_once('../config.php');
	include_once(OBJECT_ROOT . '/gamePreview.php');
	
	if(isSet($_GET['ajaxWidgetGameID']))
	{
		$gprev = new GamePreview();
		$gprev->showPreview($_GET['ajaxWidgetGameID']);
		
	}

	
?>