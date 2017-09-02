<?php
    
	include_once('config.php');
	
	include_once(OBJECT_ROOT . '/League.php');
	include_once(OBJECT_ROOT . '/Team.php');
	
	include_once(INCLUDE_ROOT . '/ui.php');

	include_once(UI_ROOT . '/basicLeaguePage.php');


	$lge = getLeague();

	ob_start();
	if(isset($_GET["teamId"])){
	
		echo "<div class='section center'>";
		$teamId = $_GET["teamId"];
		$team = $lge->getTeam($teamId);
		
		$content = "<h3>{$team->getFullName()}</h3>";
		
		$content .= "<span><strong>Head Coach:</strong> " . $team->getStaffHC()->getName() . "<br/>" .
					"<strong>Offensive Co:</strong> " . $team->getStaffOC()->getName() . "<br/>" . 
					"<strong>Defensive Co:</strong> " . $team->getStaffDC()->getName() . "<br/>" . 
					"<strong>Lead Scout:</strong> " . $team->getStaffLS()->getName() . "</span>";
		
		echo createTeamPanelWithHelmet($teamId, $content);
		
		$content = "This will have the team roster";
		echo createTeamPanel($teamId, $content);
		
		echo "</div>";
	}
	else {
			
		$tms = $lge->getTeamIdsByCity();
		$cnt = 0;
	
		echo "<table class='uflTable center'>";
		foreach($tms as $teamID => $team)
		{
			$oTeam = $lge->getTeam($teamID);
			
			if($cnt%2 == 0){
				echo "<tr>";
			}
			
			echo "<td>
							<table class='dataTable center' style='cursor: hand;' onclick='document.location=\"team/index.php?teamId=" . $teamID . "\";'>
							<tr>
								<th colspan=2 class='lvl2'>" . $oTeam->getFullName() . "</th>
							</tr>
							<tr>";
			
			if($cnt%2 == 0){
				echo "<td><img src='" . $oTeam->getHelmetImg('right'). "' height='120px' width='160px'></td>";
				echo "<td><img src='" . $oTeam->getLogoImg(). "' height='120px' width='120px'></td>";
			}
			else
			{
				echo "<td><img src='" . $oTeam->getLogoImg(). "' height='120px' width='120px'></td>";
				echo "<td><img src='" . $oTeam->getHelmetImg('left'). "' height='120px' width='160px'></td>";
			}
			
			echo "</tr>
				</table>
				</td>";
			
			$cnt = $cnt + 1;
			if($cnt%2 ==  0){
				echo "</tr>";
			}
			
				
		}
		echo "</table>";
	}
		
	$page = new BasicLeaguePage();

	$page->pagePrint(ob_get_clean());

?>
