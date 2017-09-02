<?php
	include_once ('config.php');

	
	include_once(OBJECT_ROOT . '/League.php');
	include_once(OBJECT_ROOT . '/Transaction.php');
	
	include_once(UI_ROOT . '/basicLeaguePage.php');

	$ufl = getLeague();
	
	$curYear = $ufl->getCurYear();
	$curStageId = $ufl->getCurStageID();
	$lastTransStageId = getLastTransactionStage();
	
	$year = $curYear;
	$week = $curWeek;
	$teamId = -1;
	
	if(isset($_GET['teamId']) AND $ufl->validTeam($_GET['teamId']))
		$teamId = $_GET['teamId']; 
	if($teamId != -1)
		$lastTransStageId = -1;
		
	if(isset($_GET['year']) AND $ufl->validYear($_GET['year']))
		$year = $_GET['year'];
		
	if(isset($_GET['stage']) AND is_numeric($_GET['stage']))
		$stage = $_GET['stage'];
	else
		$stage = $lastTransStageId;

	$t = new Transaction();
	$inj = new Injury();
	
	$t->getTransactions($year, $stage, $teamId);
	$inj->getInjuries($year, $stage, $teamId);
		
ob_start();
	
	$t->printTransactions();
	
	echo "<br/>";
	
	$inj->printInjuries();

	$page = new BasicLeaguePage(FLAG_INC_TEAM_SELECTOR | FLAG_INC_YEAR_SELECTOR | FLAG_INC_STAGE_SELECTOR);
	$page->setYear($year);
	$page->setTeamId($teamId);
	$page->setStage($stage);
	
	$page->pagePrint(ob_get_clean());

?>