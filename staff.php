<?php
    
	include_once('config.php');
	include_once(UI_ROOT . '/basicLeaguePage.php');

	include_once(OBJECT_ROOT . '/Staff.php');
	
	$staff = null;
	
	if(isset($_GET['coachID']) AND is_numeric($_GET['coachID']))
		$staff = new Coach($_GET['coachID']);
	elseif(isset($_GET['scoutID']) AND is_numeric($_GET['scoutID']))
		$staff = new Scout($_GET['scoutID']);
	
	ob_start();	
	if(is_object($staff)){
		$staff->printTransactions();
	}
	
	$page = new BasicLeaguePage();
	$page->pagePrint(ob_get_clean());
?>