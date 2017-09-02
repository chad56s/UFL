<?php
/*
 * 
 * This File to be included purely by the draft.php page.  $dmgr and $lge are created there.
 * 
 * 
 */
	
		//initializations
		$pos = -1;
		if(isset($_GET['position']) AND is_numeric($_GET['position']))
			$pos = $_GET['position'];
			
		$sortBy = "rank";
		if(isset($_GET['sortBy']))
			$sortBy = strtolower($_GET['sortBy']);
			
		$page = 1;
		if(isset($_GET['page']) AND $_GET['page'] > 0)
			$page = $_GET['page'];
			
		
		$rows = 32;
		if(isset($_GET['rows']) AND $_GET['rows'] > 0)
			$rows = $_GET['rows'];
		//end initializations
		
		$sortDirection = "asc";
		
			switch ($sortBy){
				case 'rank':
					$sortCol = "player_id";
					break;
				case 'name':
					$sortCol = "player_name";
					break;
				case 'pos':
					$sortCol = "position_id";
					break;
				case 'school':
					$sortCol = "player_school";
					break;
				case 'height':
					$sortCol = "player_height";
					break;
				case 'weight':
					$sortCol = "player_weight";
					break;
				case 'score':
					$sortCol = "player_score";
					break;
				case 'adj score':
					$sortCol = "player_adj_score";
					break;
				case '40 time':
					$sortCol = "player_40";
					break;
				case 'bench':
					$sortCol = "player_bench";
					break;
				case 'agility':
					$sortCol = "player_agil";
					break;
				case 'broad jump':
					$sortCol = "player_broad";
					break;
				case 'sol test':
					$sortCol = "player_solec";
					break;
				case 'position drill':
					$sortCol = "player_pos_drill";
					break;
				case '% developed':
					$sortCol = "player_developed";
					break;
				default:
					$sortCol = "player_id";
			
		}
		
		if(isset($_GET['sortOrder'])){
			if(strtolower($_GET['sortOrder']) == 'down')
				$sortDirection = 'desc';
		}
			
		$sql = "SELECT *, " . $sortCol . " IS NULL AS isNull 
						  FROM draft_player
						 WHERE 1 = 1";
		if($pos > 0) $sql = $sql . " AND position_id = " . $pos;
							
		$sql = $sql . " ORDER BY isNull, " . $sortCol . " " . $sortDirection;
			
		$qDraft = $dmgr->runQuery($sql);		
			
		$posSelector = new positionGroupSelector($pos);
		$posSelector->setNavOnChange(true);
		echo "<strong>Pos:</strong> ";
		$posSelector->display();
		
		$numPages = ceil(mysql_num_rows($qDraft)/$rows);
		if($page > $numPages)
			$page = 1;
			
		$pageSelector = new iteratorSelector(1,$numPages,1,$page);
		$pageSelector->setNavOnChange(true);
		$pageSelector->setVarName('page');
		echo " <strong>Page:</strong> ";
		$pageSelector->display();
		mysql_data_seek($qDraft, ($page-1) * $rows);
		
		$rowsSelector = new selector($rows);
		$rowsSelector->setNavOnChange(true);
		$rowsSelector->setVarName('rows');
		$aPgSelOpts = array(10=>10,32=>32,50=>50,100=>100,200=>200,500=>500,1000=>1000);
		$rowsSelector->setOptions($aPgSelOpts);
		echo " <strong>Rows:</strong> ";
		$rowsSelector->display();
		
		
		
		echo "<br/><br/><table class='dataTable center' id='tblDraftPreview'>";
		echo "<tr><th colspan=99 class='lvl1'>";
			echo "Draft Class " . $lge->getCurYear();
		echo "</th></tr>";
	
		//column headers: if they change, don't forget to alter sort above!
		$aCols = array("Rank","Name","Pos","Score","Adj Score", "School",
											"Height","Weight","40 Time",
											"Bench","Agility","Broad Jump","Sol Test",
											"Position Drill","% Developed");
		
		echo "<tr>";
		
		foreach($aCols as $val){
			echo "<th class='lvl3' nowrap>" . $val;
			if(strtolower($val) == strtolower($sortBy))
				echo "<img src='" . IMAGE_ROOT . "/sort_" . $sortDirection . ".gif'>";
			echo "</th>";
			
		}
			
		echo "</tr>";
	
		$qrows = 0;
		while(mysql_num_rows($qDraft) > 0 && $qrows < $rows && $player = mysql_fetch_assoc($qDraft)){
			$qrows++;
			echo "<tr style='cursor: pointer;' onclick='showPlayerAttr(\"pid_" . $player["player_id"] . "\");' id='pid_" . $player["player_id"] . "'>
							<td>" . $player["player_id"] . "</td>
							<td>" . $player["player_name"] . "</td>
							<td>" . mapGetPositionGroup($player["position_id"]) . "</td>
							<td>" . $player["player_score"] . "</td>
							<td>" . $player["player_adj_score"] . "</td>
							<td>" . $player["player_school"] . "</td>
							<td>" . $player["player_height"] . "in.</td>
							<td>" . $player["player_weight"] . "lbs.</td>
							<td>" . $player["player_40"] . "</td>
							<td>" . $player["player_bench"] . "</td>
							<td>" . $player["player_agil"] . "</td>
							<td>" . $player["player_broad"] . "</td>
							<td>" . $player["player_solec"] . "</td>
							<td>" . $player["player_pos_drill"] . "</td>
							<td>" . $player["player_developed"] . "%</td>
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
				$('#tblDraftPreview th.lvl3').addClass('sortable');
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
			
					$('#tblDraftPreview tr').removeClass('selected');
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

