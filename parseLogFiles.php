<?php

	session_start();

	include_once "../config.php";
	include_once(APP_ROOT . '/func/leagueUtils.php');
		
	if(!checkAdminLevel(0)){	
		header ("Location: " . WWW_ROOT);
		exit;
	}

	include_once "../header.php";
	include_once(OBJECT_ROOT . '/logFileParser.php');
	
	
	$parser = new LogFileParser(2008,17,11,23);
	
	echo "<div style='text-align:left; background-color:#ffffff;'>";
	if(!$parser->parse())
		echo "the parse failed<br/>";
	else
		echo "the parse succeeded<br/>";

	echo "</div>";

	include_once "../footer.php";
?>