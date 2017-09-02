<?php
      
	include_once('config.php');
	include_once('header.php');
	
	include_once(DBMGR_ROOT . '/DataMgr.php');
	
	function computePts($stats){
		$pts = 0;
		
		$pts = $pts + .2 * floor($stats->PassYards / 5);
		if($stats->PassYards > 100)
			$pts = $pts + floor(($stats->PassYards - 100)/100);
		$pts = $pts + .25 * $stats->PassCompletions;
		$pts = $pts + 6 * $stats->TDPasses;
		$pts = $pts - 2.5 * $stats->INTThrown;
		$pts = $pts + .5 * floor($stats->RushingYards / 5);
		if($stats->RushingYards >= 100)
			$pts = $pts + 1 + floor(($stats->RushingYards - 50) / 50);
		$pts = $pts + 6 * $stats->RushTD;
		$pts = $pts + .5 * floor($stats->ReceivingYards / 5);
		if($stats->ReceivingYards >= 100)
			$pts = $pts + 1 + floor(($stats->ReceivingYards - 50) / 50);
		$pts = $pts + 6 * $stats->ReceivingTDs;
		$pts = $pts - 2.5 * $stats->Fumbles;
		$pts = $pts + 2 * $stats->TwoPointConversions;
		$pts = $pts + floor(($stats->KickReturnYards / 15));
		$pts = $pts + 6 * $stats->KickReturnTDs;
		
		$pts = $pts + $stats->Catches / 2;
		
		return $pts;
		
	}
	
	function writeHeaderRow($playerName){
		echo "<tr><th class='lvl2'><strong>$playerName</strong></th>";
		echo "<th class='lvl2'>Pass Yds</th><th class='lvl2'>Comp</th>";
		echo "<th class='lvl2'>Pass TD</th><th class='lvl2'>Ints</th>";
		echo "<th class='lvl2'>Rush Yds</th><th class='lvl2'>Rush TD</th>";
		echo "<th class='lvl2'>Rec</th><th class='lvl2'>Rec Yds</th><th class='lvl2'>Rec TD</th>";
		echo "<th class='lvl2'>Kick Ret Yds</th><th class='lvl2'>Kick Ret TD</th>";
		echo "<th class='lvl2'>Fumb</th><th class='lvl2'>2-pt</th><th class='lvl2'>Fantasy Pts</th></tr>";
	}
	
	function writeStatLine($stats){
		$style = '';
		
		if($stats->GameStarted == 0)
			$style = 'background-color:#ff0000;';
		
		echo "<tr style='$style'><td>";
		echo getWeekString($stats->Week);
		echo "</td><td>$stats->PassYards</td><td>$stats->PassCompletions</td>";
		echo "<td>$stats->TDPasses</td><td>$stats->INTThrown</td>";
		echo "<td>$stats->RushingYards</td><td>$stats->RushTD</td>";
		echo "<td>$stats->Catches</td><td>$stats->ReceivingYards</td><td>$stats->ReceivingTDs</td>";
		echo "<td>$stats->KickReturnYards</td><td>$stats->KickReturnTDs</td>";
		echo "<td>$stats->Fumbles</td><td>$stats->TwoPointConversions</td><td>" . computePts($stats) . "</td>";
		
	}
	
	function writeDefensiveStats($team){
		
		$dm = createNewDataManager();
		
		if($team == 1){
			$defTeam = 1;
			$defTeamStr = "St. Louis";
		}
		else{
			$defTeam = 29;
			$defTeamStr = "Cincinnati";
		}
		
		$sql = "SELECT a.*, b.oppscore
						FROM (
							SELECT week,team,year,
							SUM(ints) as ints, SUM(intreturnyards) as intRetYds, SUM(intreturntds) as intRetTDs,
							SUM(passesblocked) as passBlocked, SUM(forcedfumbles) as forceFumbles, SUM(fumblerecoveries) as fumbleRecoveries,
							SUM(puntreturntds) as puntRetTDs, SUM(puntreturnyards) as puntRetYds, 
						  SUM(sacks)/10 as sacks, SUM(miscTD) as miscTDs
							
							FROM fof_playergamestats 
							WHERE team = $defTeam 
							AND WEEK >= 6 
							AND YEAR = 2010
							GROUP BY team, week, year) a 
						JOIN fof_teamschedule b
						ON a.team = b.teamID
						AND a.year = b.year
						AND a.week = b.week
						ORDER BY a.WEEK 
						";
		
		echo "<tr><th class='lvl1' colspan=99>DEFENSE</th></tr>";
		echo "<tr><th class='lvl2'>$defTeamStr</th><th class='lvl2'>Ints</th>
						<th class='lvl2'>Int Ret Yds</th><th class='lvl2'>Int Ret TDs</th>
						<th class='lvl2'>Force Fumb</th><th class='lvl2'>Fumb. Recs.</th>
						<th class='lvl2'>Punt Ret Yds.</th><th class='lvl2'>Punt Ret TDs</th>
						<th class='lvl2'>Sacks</th><th class='lvl2'>Misc TDs</th>
						<th class='lvl2'>Opp. Score</th><th class='lvl2'>Fantasy Pts</th>
		</tr>";

		
		$result = $dm->runQuery($sql);
		$totalPts = 0;
		while($r = mysql_fetch_object($result)){
			$weekPts = $r->ints * 2;
			$weekPts = $weekPts + floor($r->intRetYds / 25);
			$weekPts = $weekPts + 6 * $r->intRetTDs;
			$weekPts = $weekPts + $r->forceFumbles;
			$weekPts = $weekPts + $r->fumbleRecoveries;
			$weekPts = $weekPts + floor($r->puntRetYds / 15);
			$weekPts = $weekPts + 6 * $r->puntRetTDs;
			$weekPts = $weekPts + $r->sacks;
			$weekPts = $weekPts + 6 * $r->miscTDs;
			
			
			if($r->oppscore == 0 )
				$weekPts = $weekPts + 12;
			elseif($r->oppscore < 7)
				$weekPts = $weekPts + 8;
			elseif($r->oppscore < 14)
				$weekPts = $weekPts + 5;
			elseif($r->oppscore < 21)
				$weekPts = $weekPts + 2;
			elseif($r->oppscore < 28)
				$weekPts = $weekPts + 0;
			elseif($r->oppscore < 35)
				$weekPts = $weekPts - 2;
			else
				$weekPts = $weekPts - 5;
				
			$totalPts = $totalPts + $weekPts;
			
			
			echo "<tr><td>";
			echo getWeekString($r->week);
			echo "</td><td>$r->ints</td><td>$r->intRetYds</td>";
			echo "<td>$r->intRetTDs</td><td>$r->forceFumbles</td>";
			echo "<td>$r->fumbleRecoveries</td><td>$r->puntRetYds</td>";
			echo "<td>$r->puntRetTDs</td>";
			echo "<td>$r->sacks</td>";
			echo "<td>$r->miscTDs</td><td>$r->oppscore</td><td>$weekPts</tr>";
		}
		
		echo "<tr><th class='lvl3'>Total Pts</th><th colspan=99 class='lvl3' style='text-align: right'>$totalPts</th></tr>";
		
		return $totalPts;
		
	}
	
	$dataMgr = createNewDataManager();
	
	$sql = "SELECT a.teamid, a.playerid, a.startweek, a.endweek, c.lastname, c.firstname, d.positiongroup
						FROM fantasy_teams a
						JOIN fof_playeractive b
						ON a.playerid = b.id
						JOIN fof_playerhistorical c
						ON a.playerid = c.id
						JOIN fof_mappings d
						ON b.positiongroup = d.id
						ORDER BY a.teamId, b.positiongroup, a.startweek";
						
	$players = $dataMgr->runQuery($sql);
	$fTeam = 0;
	$pGroup = null;
	
	$endPG = 0;
	$endTeam = 0;
	
	while($row = mysql_fetch_object($players)){
		
		
		if($endTeam == 1 && $fTeam != $row->teamid){
			echo "</table>";
			
			echo "<h1>TOTAL TEAM POINTS: $teamPts</h1><hr/>";
			
		}
		
		if($row->teamid != $fTeam){
			$fTeam = $row->teamid;
			
			echo "<h2>";
			if($fTeam == 1)
				echo "Chad";
			else
				echo "Clif";
			echo "</h2>";
			echo "<table class='dataTable center'>";
			
			
			
			$endTeam = 1;
			
			$teamPts = writeDefensiveStats($row->teamid);
			
		}
		
		if($pGroup != $row->positiongroup){
			$pGroup = $row->positiongroup;
			echo "<tr><th colspan=99 class='lvl1'>$pGroup</th></tr>";
			$endPG = 1;
		}
		
  	writeHeaderRow($row->firstname . " " . $row->lastname);
		
		
		$sql = "SELECT *
						  FROM fof_playergamestats
						WHERE playerid = $row->playerid
						AND year = 2010
						AND week >= $row->startweek";
						if(!is_null($row->endweek))
							$sql = $sql . " AND week <= $row->endweek";
						$sql = $sql . " ORDER BY week";
		
			$playerPts = 0;
			$stats = $dataMgr->runQuery($sql);
			while($statLine = mysql_fetch_object($stats)){
				writeStatLine($statLine);
				$playerPts = $playerPts + computePts($statLine);
			}
			$teamPts = $teamPts + $playerPts;
			
			echo "<tr><th class='lvl3'>Total Pts</th><th colspan=99 class='lvl3' style='text-align: right;'>$playerPts</th></tr>";
		
		
	}
	
	echo "</table>";
	echo "<h1>TOTAL TEAM POINTS: $teamPts</h1>";
	
	include_once('footer.php');
	
?>