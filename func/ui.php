<?php

/*
	$aColumns = array(array($aData.key, column title, column label))

*/
function createDataTable($title, $subTitle, $aColumns, $aData){

		$table = "";

		$table .= "<table class='dataTable center' style='width:100%;'>";
		
		if(strlen($title))
			$table .= "<tr><th colspan=99 class='lvl1'>$title</th></tr>";
		
		if(strlen($subTitle))
			$table .= "<tr><th colspan=99 class='lvl2'>$subTitle</th></tr>";
			
		$table .= "<tr>";
		foreach($aColumns as $column){
			if(count($column) >= 3)
				$label = "title='$column[2]'";
			else
				$label = '';
				
			$table .= "<th class='lvl3' $label>" . $column[1] . "</th>";
		}
		$table .= "</tr>";
		
		foreach($aData as $idx => $data){
			$table .= "<tr>";
			foreach($aColumns as $column){
				$table .= "<td>" . $data[$column[0]] . "</td>";
			}
			$table .= "</tr>";
		}
		
		$table .= "</table>";
		
		return $table;

}


function printTeamStyles($teamId){

	$teamStylesPrinted = array_fill(0,32,0);
	
	if(!$teamStylesPrinted[$teamId]){
	
		$lge = getLeague();
		
		if($lge->validTeam($teamId)){		
			$team = $lge->getTeam($teamId);
			$aColors = $team->getColors();
		}
		else
			$aColors = array("FFF","000","000");

		echo "<style>
					.team{$teamId}Panel {
						border-color: #$aColors[1];
						background-color: #$aColors[0];
						color: #$aColors[2];
					}
					
					.team{$teamId}Panel h1, 
					.team{$teamId}Panel h2, 
					.team{$teamId}Panel h3, 
					.team{$teamId}Panel h4, 
					.team{$teamId}Panel h5 {
						color: #$aColors[1];
					}
					
					.team{$teamId}Text2 {
						color: #$aColors[2];
					}
				</style>";
				
		$teamStylesPrinted[$teamId] = 1;
	
	}
	
}


function createTeamPanel($teamId, $content, $minHeight=0) {

	printTeamStyles($teamId);
	
	$panelId = uniqid("panel{$teamId}");
	
	$style = "";
	
	if($minHeight > 0){
		$style = "<style>
						#$panelId{
							min-height: {$minHeight}px;
							height:auto !important;
							height: {$minHeight}px;
						}
					</style>";
	}
	
	
	$section = "$style<div id='$panelId' class='panel team{$teamId}Panel'>$content</div>
						<div style='clear:both;'>&nbsp</div>";
	return $section;
}

	

function createTeamPanelWithHelmet($teamId, $content, $side='left'){

	$lge = getLeague();
	
	if($lge->validTeam($teamId)){
		$team = $lge->getTeam($teamId);
		
		$content = "<div class='inlineDivBackward'>
								<img src='{$team->getHelmetImg($side)}'>
							</div>
							<div class='inlineDiv'>
								$content
							</div>";
	}
	
	return createTeamPanel($teamId, $content, 170);

}


?>