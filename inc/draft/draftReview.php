<?php
/*
 * 
 * This File to be included purely by the draft.php page.  $dmgr and $lge are created there.
 * 
 * 
 */
	
		//initializations
		$pos = -1;
		if(isset($_GET['position']) && is_numeric($_GET['position']))
			$pos = $_GET['position'];
			
		$sortBy = "rank";
		if(isset($_GET['sortBy']))
			$sortBy = strtolower($_GET['sortBy']);
			
		$round = 1;
		if(isset($_GET['round']) && ($_GET['round'] > 0 || $_GET['round'] == -1))
			$round = $_GET['round'];
		
		$team = -1;
		if(isset($_GET['teamId']) && is_numeric($_GET['teamId']))
			$team = $_GET['teamId'];
		//end initializations
		
		$sortDirection = "asc";
		
			switch ($sortBy){
				case 'pre-draft rank':
					$sortCol = "b.player_id";
					break;
				case 'name':
					$sortCol = "a.lastName, a.firstName";
					break;
				case 'pos':
					$sortCol = "b.position_id";
					break;
				case 'school':
					$sortCol = "b.player_school";
					break;
				case 'pick':
					$sortCol = "a.draftRound, a.draftPick";
					break;
				case 'team':
					$sortCol = "a.draftedBy";
					break;
				default:
					$sortCol = "a.draftRound, a.draftPick";
			
		}
		
		if(isset($_GET['sortOrder'])){
			if(strtolower($_GET['sortOrder']) == 'down')
				$sortDirection = 'desc';
		}
			
		$sql = "SELECT a.draftRound, a.draftPick, a.draftedBy, a.lastName, a.firstName,
									 b.position_id, b.player_school, b.player_id 
					    FROM fof_playerhistorical a
						  JOIN draft_player b
						    ON concat(a.firstName,concat(' ',a.lastName)) = b.player_name
						 WHERE a.yearDraft = " . $lge->getCurYear();
						 
						if($pos > 0) $sql = $sql . " AND position_id = " . $pos;
						if($team > -1) $sql = $sql . " AND draftedBy = " . $team;
						if($round > -1) $sql = $sql . " AND a.draftRound = " . $round;
						
		$sql = $sql . "				
				  ORDER BY " . $sortCol . " " . $sortDirection . ", a.draftRound, a.draftPick"	;
			
		$qDraft = $dmgr->runQuery($sql);
		
		$numPages = 7;
		$pageSelector = new iteratorSelector(1,$numPages,1,$round);
		$pageSelector->setNavOnChange(true);
		$pageSelector->setVarName('round');
		$pageSelector->setAllOption('-1','All');
		echo " <strong>Round:</strong> ";
		$pageSelector->display();
		
		
		$teamSelector = new teamSelector($team);
		$teamSelector->setNavOnChange(true);
		echo "&nbsp;<strong>Team:</strong> ";
		$teamSelector->display();		
			
		$posSelector = new positionGroupSelector($pos);
		$posSelector->setNavOnChange(true);
		echo "&nbsp;<strong>Pos:</strong> ";
		$posSelector->display();
		
		
		if($round > $numPages)
			$round = 1;
		
		echo "<br/><br/><table class='dataTable center' id='tblDraftReview'>";
		echo "<tr><th colspan=99 class='lvl1'>";
			echo "Draft " . $lge->getCurYear();
		echo "</th></tr>";
	
		//column headers: if they change, don't forget to alter sort above!
		$aCols = array("Pick","Team","Name","Pos","School","Pre-Draft Rank");
		
		echo "<tr>";
		
		foreach($aCols as $val){
			echo "<th class='lvl3' nowrap>" . $val;
			if(strtolower($val) == strtolower($sortBy))
				echo "<img src='" . IMAGE_ROOT . "/sort_" . $sortDirection . ".gif'>";
			echo "</th>";
			
		}
			
		echo "</tr>";
	
		$qrows = 0;
		while(mysql_num_rows($qDraft) > 0 && $player = mysql_fetch_assoc($qDraft)){
			$qrows++;
			echo "<tr style='cursor: pointer;' onclick='showPlayerAttr(\"pid_" . $player["player_id"] . "\");' id='pid_" . $player["player_id"] . "'>
							<td>" . $player["draftRound"] . "." . $player["draftPick"] . "</td>
							<td>" . $lge->getTeamAbbrev($player["draftedBy"]) . "</td>
							<td>" . $player["lastName"] . ", " . $player["firstName"] . "</td>
							<td>" . mapGetPositionGroup($player["position_id"]) . "</td>
							<td>" . $player["player_school"] . "</td>
							<td>" . $player["player_id"] . "</td>
						</tr>";
		}
		
		echo "</table>"	;
		
		
		echo "<div id='playerAttrsContainer' style='padding: 10px; border: 1px solid red; width: 350px; position: absolute; display: none; background: black; color: white;'>
					<span style='cursor: pointer; float: left;' onclick='prevPlayer();'>Previous</span> 
					<span style='cursor: pointer; float: right;' onclick='nextPlayer();'>Next</span>
					<span style='cursor: pointer;' onclick='closeAttrs();'>Close</span>
					<br/><br/>
					<div id='playerAttrs'>
					</div>
					</div>";
		
		echo "
			<script>				
				$('#tblDraftReview th.lvl3').addClass('sortable');
			</script>
		
		";
		
		echo "
			<script>
				aPlayerAttr = new Object;
				lastPlayerId = 0;
				
				//retrieves attributes via xml
				function getPlayerAttr(playerId){
					apid = playerId.split('_');
					pid = apid[1];
					$.get('draft.php?ffdfe=true&playerId=' + pid,function(data){
						aPlayerAttr[playerId] = data;
						displayPlayerAttr(playerId);
					});
				}
				
				//call this function
				function showPlayerAttr(playerId){
					lastPlayerId = playerId;
			
					$('#tblDraftReview tr').removeClass('selected');
					$('#'+playerId).addClass('selected');
					
					if(!aPlayerAttr[playerId]){
						getPlayerAttr(playerId);	
					}
					else{
						//alert($('#playerAttrs').html());
						displayPlayerAttr(playerId);
					}
					
				}
				
				//actually display the attributes
				function displayPlayerAttr(playerId){
					//document.getElementById('playerAttrs').innerHTML = aPlayerAttr[playerId];
					$('#playerAttrs').html(aPlayerAttr[playerId]);
					$('#playerAttrsContainer').show().center();
				}
				
				//close the attribute window
				function closeAttrs(){
					$('#playerAttrsContainer').hide();
				}
				
				//show previous player
				function prevPlayer(){
					$('#'+lastPlayerId).prev().click();
				}
				
				//show next player
				function nextPlayer(){
					$('#'+lastPlayerId).next().click();
				}
			</script>
		
		";
?>
