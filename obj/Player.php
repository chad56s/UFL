<?php

include_once(APP_ROOT . '/config.php');
include_once(DBMGR_ROOT . '/DataMgr.php');
include_once(APP_ROOT . '/func/leagueUtils.php');
include_once(APP_ROOT . '/func/ui.php');
include_once(OBJECT_ROOT . '/Transaction.php');
include_once(OBJECT_ROOT . '/CollegeIndex.php');

class Player {
	
	private $dataMgr;
	public $props;
	
	public function __construct($id)
	{
		$this->dataMgr = getDBConnection();
		
		$qPlayer = NULL;
		
		$sql = "SELECT *
					 FROM fof_playerhistorical a
					WHERE a.id = ".$id;
						 
		$qPlayer = $this->dataMgr->runQuery($sql);
		
		if(mysql_num_rows($qPlayer))
		{
			$this->props = mysql_fetch_assoc($qPlayer);
		}
		
		$this->props["CollegeName"] = CollegeIndex::getById($this->getProp("College"));
		$this->props["CurrentTeam"] = $this->getCurrentTeam();
		
	}
	
	
	public function getProp($prop)
	{
		if($this->props && array_key_exists($prop,$this->props))
			return $this->props[$prop];
		else
			return null;
	}
	
	
	public function getPlayerID(){
		return $this->getProp('ID');
	}
	
	public function getPlayerName($bLastFirst = 0){
		if($bLastFirst)
			return $this->getProp('LastName') . ', ' . $this->getProp('FirstName');
		else
			return $this->getProp('FirstName') . ' ' . $this->getProp('LastName');
	}
	
	public function getPlayerPosition($bGroup=0){
		return $bGroup ? positionToPositionGroup($this->getProp('Position')) : mapGetPosition($this->getProp('Position'));
	}
	
	//this function is so the transaction page can simply
	//call getName for players, scouts, and coaches
	
	public function getLink(){
		$lnk = sprintf("<a href='player.php?playerID=%d'>%s</a>",$this->getProp('ID'),$this->getPlayerName());
		return $lnk;
	}
	
	public function getCurrentTeam() {
		$curTeam = -1;
		$sql = "SELECT Team FROM fof_playeractive WHERE id = " . $this->getProp("ID");
		$qCurTeam = $this->dataMgr->runQuery($sql);
		if(mysql_num_rows($qCurTeam)){
			$row = mysql_fetch_array($qCurTeam);
			$curTeam = $row[0];
		}
		return $curTeam;
	}
	
	
	public function getYearlyStats($playoffs = 0) {
		$sql = "SELECT * 
					 FROM fof_playergamestats_by_year
					WHERE PlayerID = " . $this->getPlayerID() . "
					  AND playoffs = $playoffs
				ORDER BY Year";
		$qStats = $this->dataMgr->runQuery($sql);
		//echo $sql;
		$aStats = array();
		
		while($row = mysql_fetch_assoc($qStats)){
			$aStats[$row["Year"]] = $row;
			
			//adjust sacks since they are stored as integer
			$aStats[$row["Year"]]["Sacks"] /= 10;
			
		}
		
		return $aStats;
	
	}
	
		
	public function printTransactions(){
		$trans = new Transaction();
		$trans->getPlayerTransactions($this);
		$trans->printTransactions();
	}


	public function printInjuries(){
		$trans = new Injury();
		$trans->getPlayerInjuries($this);
		$trans->printInjuries();
	}
	
	public function printYearlyStats() {
	
		$statMap = $this->getStatMap();
		$regSeason = $this->getYearlyStats(0);
		$playoffs = $this->getYearlyStats(1);
		
		switch (positionGroupToMetaGroup($this->getPlayerPosition(1))){
		
			case 'QB':
				$relevantStats = array (0,1,2,3,4,5,6,7,8,9,10,28);
				break;
			case 'HB':
				$relevantStats = array(0,1,2,11,12,14,13,28);
				break;
			case 'TE':
				$relevantStats = array(0,1,2,15,16,18,17,28);
				break;
			case 'WR':
				$relevantStats = array(0,1,2,15,16,18,17,28);
				break;
			case 'OL':
				$relevantStats = array(0,1,2,32,33,85,34,45,46);
				break;
			case 'DL':
				$relevantStats = array(0,1,2,37,35,36,42,43,45,46,30,29);
				break;
			case 'LB':
				$relevantStats = array(0,1,2,37,35,36,42,43,45,46,30,29,38,39,40,41,44);
				break;
			case 'DB':
				$relevantStats = array(0,1,2,38,39,41,44,40,37,35,36,42,43,45,46,30,29);
				break;
			case 'P':
				$relevantStats = array(0,1,2,52,53,54,55,72);
				break;
			case 'K':
				$relevantStats = array(0,1,2,47,48,49,50,51,56,68,69,70,71);
				break;
			default:
				$relevantStats = array(0,1,2);
				break;
		
		}
		
		
		$aColumns = array();
		foreach($relevantStats as $idx)
			$aColumns[] = $statMap[$idx];
			
		if(count($regSeason)){
			echo createDataTable($this->getPlayerName() . " Stats", "Regular Season", $aColumns, $regSeason);
			
			if(count($playoffs))
				echo createDataTable("", "Playoffs", $aColumns, $playoffs);
				
		}
		
	}

	
	public function __destruct()
	{
		
	}
	
	
	private function getStatMap(){
	
		$statMap = array(
		0 => array("Year", "Year", "Year"),
		1 => array("GamePlayed", "GP", "Games Played"),
		2 => array("GameStarted", "GS", "Games Started"),
		3 => array("PassAttempts", "PA", "Pass Attempts"),
		4 => array("PassCompletions", "PC", "Pass Completions"),
		5 => array("PassYards", "PaYds", "Pass Yards"),
		6 => array("LongestPass", "PaLng", "Longest Pass"),
		7 => array("TDPasses", "PaTD", "TD Passes"),
		8 => array("INTThrown", "Ints", "Interceptions"),
		9 => array("TimesSacked", "Sack", "Sacked"),
		10 => array("SackedYards", "SYds", "Sacks - Yds Lost"),
		11 => array("RushAttempts", "RuAtt", "Rush Attempts"),
		12 => array("RushingYards", "RuYds", "Rushing Yards"),
		13 => array("LongestRun", "RuLng", "Longest Run"),
		14 => array("RushTD", "RuTD", "Rushing TD"),
		15 => array("Catches", "Ctch", "Catches"),
		16 => array("ReceivingYards", "ReYds", "Receiving Yards"),
		17 => array("LongestReception", "ReLng", "Longest Reception"),
		18 => array("ReceivingTDs", "ReTD", "Receiving TD"),
		19 => array("PassTargets", "Targ", "Targeted"),
		20 => array("YardsAfterCatch", "YAC", "Yards After Catch"),
		21 => array("PassDrops", "Drops", "Passes Drops"),
		22 => array("PuntReturns", "PRet", "Punt Returns"),
		23 => array("PuntReturnYards", "PRetYds", "Punt Return Yards"),
		24 => array("PuntReturnTDs", "PRetTD", "Punt Return TDs"),
		25 => array("KickReturns", "KRet", "Kick Returns"),
		26 => array("KickReturnYards", "KRetYds", "Kick Return Yards"),
		27 => array("KickReturnTDs", "KRetTD", "Kick Return TDs"),
		28 => array("Fumbles", "F", "Fumbles"),
		29 => array("FumbleRecoveries", "FRec", "Fumble Recoveries"),
		30 => array("ForcedFumbles", "FF", "Forced Fumbles"),
		31 => array("MiscTD", "MiscTD", "Miscellaneous TDs"),
		32 => array("KeyRunBlock", "KRB", "Key Run Blocks"),
		33 => array("KeyRunBlockOpportunities", "KRBOp", "Key Run Block Opportunities"),
		34 => array("SacksAllowed", "SA", "Sacks Allowed"),
		35 => array("Tackles", "Tac", "Tackles"),
		36 => array("Assists", "Asst", "Assists"),
		37 => array("Sacks", "Sck", "Sacks"),
		38 => array("INTs", "Int", "Interceptions"),
		39 => array("INTReturnYards", "IntRetYds", "Interception Return Yards"),
		40 => array("INTReturnTDs", "IntRetTD", "Interception Return TDs"),
		41 => array("PassesDefended", "PaDef", "Passes Defensed"),
		42 => array("PassesBlocked", "PaBlk", "Passes Blocked"),
		43 => array("QBHurries", "QBHurr", "QB Hurries"),
		44 => array("PassesCaught", "CmpAlw", "Completions Allowed"),
		45 => array("PassPlays", "PaPly", "Pass Plays"),
		46 => array("RunPlays", "RuPly", "Run Plays"),
		47 => array("FGMade", "FG", "Field Goals"),
		48 => array("FGAttempted", "FGAtt", "Field Goals Attempted"),
		49 => array("FGLong", "FGLng", "Longest Field Goal"),
		50 => array("PAT", "PAT", "Point After TD"),
		51 => array("PATAttempted", "PATAtt", "PAT Attempts"),
		52 => array("Punts", "P", "Punts"),
		53 => array("PuntYards", "PYds", "Punt Yards"),
		54 => array("PuntLong", "PLng", "Longest Punt"),
		55 => array("PuntIn20", "PI20", "Punts Inside 20"),
		56 => array("Points", "Pts", "Points Scored"),
		57 => array("ThirdDownRushes", "3rdRu", "Third Down Rushes"),
		58 => array("ThirdDownRushConversions", "RuConv", "Third Down Rush Conversions"),
		59 => array("ThirdDownPassAttempts", "3rdPaAtt", "Third Down Pass Attempts"),
		60 => array("ThirdDownPassCompletions", "3rdPaC", "Third Down Pass Completions"),
		61 => array("ThirdDownPassConversions", "PaConv", "Third Down Pass Conversions"),
		62 => array("ThirdDownReceivingTargets", "3rdReTarg", "Third Down Receiving Targets"),
		63 => array("ThirdDownReceivingCatches", "3rdRe", "Third Down Receptions"),
		64 => array("ThirdDownReceivingConversions", "3rdReConv", "Third Down Reception Conversions"),
		65 => array("FirstDownRushes", "1stRu", "1st Down Rushes"),
		66 => array("FirstDownPasses", "1stPa", "1st Down Passes"),
		67 => array("FirstDownCatches", "1stRe", "1st Down Receptions"),
		68 => array("FG40PlusAttempts", "40+Att", "Attempts from 40+ Yds"),
		69 => array("FG40PlusMade", "40+", "FG made from 40+"),
		70 => array("FG50PlusAttempts", "50+Att", "Attempts from 50+ Yds"),
		71 => array("FG50PlusMade", "50+", "FG made from 50+"),
		72 => array("PuntNetYards", "Net", "Punt Net Yards"),
		73 => array("SpecialTeamsTackles", "STTack", "Special Teams Tackles"),
		74 => array("TimesKnockedDown", "KD", "Knocked Down"),
		75 => array("RedZoneRushes", "RZRu", "Red Zone Rushes"),
		76 => array("RedZoneRushingYards", "RZRuYds", "Red Zone Rushing Yards"),
		77 => array("RedZonePassAttempts", "RZPaAtt", "Red Zone Pass Attempts"),
		78 => array("RedZonePassCompletions", "RZPaComp", "Red Zone Pass Completions"),
		79 => array("RedZonePassingYards", "RZPaYds", "Red Zone Passing Yards"),
		80 => array("RedZoneReceivingTargets", "RZReTarg", "Red Zone Receiving Targets"),
		81 => array("RedZoneReceivingCatches", "RZRec", "Red Zone Receptions"),
		82 => array("RedZoneReceivingYards", "RZReYds", "Red Zone Receiving Yards"),
		83 => array("TotalTDs", "TDs", "Total TDs"),
		84 => array("TwoPointConversions", "2PtConv", "Two-Point Conversions"),
		85 => array("PancakeBlocks", "PanBlk", "Pancake Blocks"),
		86 => array("QBKnockdowns", "QBKD", "QB Knockdowns"),
		87 => array("SpecialTeamsPlays", "STPly", "Special Teams Plays"),
		88 => array("RushingGamesOver100Yards", "100+RuG", "Rushing Games over 100 Yards"),
		89 => array("ReceivingGamesOver100Yards", "100+ReG", "Receiving Games over 100 Yards"),
		90 => array("PassingGamesOver300Yards", "300+PaG", "Passing Games Over 300 Yards"),
		91 => array("RunsOf10YardsPlus", "10+Ru", "Rushes of 10 Yards or Greater"),
		92 => array("CatchesOf20YardsPlus", "20+Re", "Receptions of 20 Yards of Greater"),
		93 => array("ThrowsOf20YardsPlus", "20+Pa", "Passes of 20 Yards or Greater"),
		94 => array("AllPurposeYards", "APYds", "All-Purpose Yards"),
		95 => array("YardsFromScrimmage", "YdsFS", "Yards From Scrimmage")
		
		);
	
		return $statMap;
	}
	
} //end player class
?>