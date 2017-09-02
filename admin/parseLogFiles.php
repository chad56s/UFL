<?php

	session_start();

	include_once "../config.php";
	include_once(APP_ROOT . '/func/leagueUtils.php');
		
	if(!checkAdminLevel(0)){	
		header ("Location: " . WWW_ROOT);
		exit;
	}

	include_once "../header.php";
	include_once(OBJECT_ROOT . '/LogFileParser.php');
	include_once(OBJECT_ROOT . '/Schedule.php');
	include_once(OBJECT_ROOT . '/League.php');
	
	$parser = new LogFileParser(2009,2,3,10);
	
	echo "<div style='text-align:left; background-color:#ffffff;'>";
	if(!$parser->parse()){
		if(!$parser->canParse())
			echo "Can't parse.<br/>";
		if($parser->alreadyParsed())
			echo "Already parsed.<br/>";
		echo "the parse failed<br/>";
	}
	else
		echo "the parse succeeded<br/>";

	echo "</div>";

	include_once "../footer.php";
?>