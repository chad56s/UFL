<?php


	session_start();

	include_once ('config.php');
	include_once(OBJECT_ROOT . '/League.php');

	
	$lge = getLeague();
	$y = $lge->getCurYear();
	$w = $lge->getCurWeek();
	$s = $lge->getCurStageId();
	
	
	if($s <= STAGE_ULTIMATE_BOWL && $s >= STAGE_PRESEASON_START)
		include ('gotw.php');
	elseif ($s == STAGE_OFFSEASON || $s == STAGE_SEASON_END)
		include ('awards.php');
	elseif($s < STAGE_PRESEASON_START)
		include ('transactions.php');
	
	

?>