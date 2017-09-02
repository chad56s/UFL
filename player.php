<?php
    
	include_once('config.php');
	include_once(UI_ROOT . '/basicLeaguePage.php');
	include_once(OBJECT_ROOT . '/Player.php');
	
	$lge = getLeague();
	$player = null;
	
	if(isset($_GET['playerID']) AND is_numeric($_GET['playerID']))
		$player = new Player($_GET['playerID']);
	
	ob_start();
	
	if(is_object($player)){
	
		if($lge->validTeam($player->getCurrentTeam()))
			$team = $lge->getTeam($player->getCurrentTeam());
		
		echo "<div class='section center'>";
		
				
		$draftRound = $player->getProp("DraftRound");
		$draftString = "Undrafted";
		$yearRetired = $player->getProp("YearRetired");
		
		if($draftRound != 0)
			$draftString = $player->getProp("YearDraft")." (Round ". $player->getProp("DraftRound") . " Pick " . $player->getProp("DraftPick") . ")";
		
		$content = "<h3>" .
			$player->getPlayerName(false)."</h3>" .
			"<span>" .$player->getPlayerPosition($yearRetired > 0) . "</span><br/>".
			
			"<span>".$player->getProp("CollegeName")."</span></br>".
			"<span>Drafted: " . $draftString . "</span><br/>";
			
		if($yearRetired > 0)
			$content .= "<span>Retired: " . $yearRetired . "</span><br/>";
			
		$pog = $player->getProp("PoG");
		$pow = $player->getProp("PoWWins");
		if($pog > 0)
			$content .= "<span>Player of the Game: $pog times</span><br/>";
		if($pow > 0)
			$content .= "<span>Player of the Week: $pow times</span><br/>";

		if($team)
			$content .= "Team: " . $team->getFullName();
				
		echo createTeamPanelWithHelmet($player->getCurrentTeam(), $content);
		
		
		$player->printYearlyStats();
		echo "<br>";
		$player->printTransactions();
		echo "<br>";
		$player->printInjuries();
		
		
		
		echo "</div>";
	}
	
	$page = new BasicLeaguePage();
	$page->pagePrint(ob_get_clean());
?>