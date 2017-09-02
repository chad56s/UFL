<?php

include_once(DBMGR_ROOT . '/DataMgr.php');
include_once(APP_ROOT . '/func/leagueUtils.php');
include_once(OBJECT_ROOT . '/Team.php');

class League {
	
	private $dataMgr;
	private $qLeague;
	
	private $startYear;
	private $curYear;
	private $stage;  //not sure what this is used for
	private $week;
	private $FAStage;
	private $stageName;
	private $stageID; //a better stage to use than $this->stage.  Refers to fof_stagenames
	private $transactionStage;
	
	private $aTeams; //holds an array of teams indexed by team id;  each element is an assoc array containing:
										// city
										// nickname
										// abbre
										// wins
										// losses
										// ties
										// record
	public function __construct()
	{
		$this->dataMgr = createNewDataManager();
		
		$this->qLeague = NULL;
		$this->startYear = 0;
		$this->curYear = 0;
		$this->stage = 0;
		$this->week = 0;
		$this->FAStage = 0;
		$this->aTeams = array();
		
		$sql = "SELECT a.startYear, a.curYear, a.stage, a.week, a.FAStage,
									 b.StageName, b.StageIndex as stageID, b.transactionStage
						  FROM fof_gameinfo a
							JOIN fof_stagenames b
							  ON a.Stage = b.GameInfoStage
							 AND a.Week = b.GameInfoWeek
							 AND a.FAStage = b.GameInfoFAStage";
		$this->qLeague = $this->dataMgr->runQuery($sql);
		
		if(mysql_num_rows($this->qLeague))
		{
			$row = mysql_fetch_object($this->qLeague);
			
			$this->startYear = $row->startYear;
			$this->curYear = $row->curYear;
			$this->stage = $row->stage;
			$this->week = $row->week;
			$this->FAStage = $row->FAStage;
			$this->stageName = $row->StageName;
			$this->stageID = $row->stageID;
			$this->transactionStage = $row->transactionStage;
		}
		
	}
	
	
	public function __destruct()
	{
		
	}
	
	
	public function getCurYear()
	{return $this->curYear;}
	
	
	public function getStartYear()
	{return $this->startYear;}
	

	public function validYear($year)
	{return(is_numeric($year) AND $year >= $this->startYear AND $year <= $this->curYear);}
	

	public function getCurWeek()
	{return $this->week;}

  //keeping this function in this class instead of leagueUtils because validYear is here also
	public function validWeek($week)
	{
		return 
		(is_numeric($week) AND 
			(
				weekIsPreseason($week) OR 
		 		weekIsRegularSeason($week) OR 
		 		weekIsPostseason($week)
			)
		);
	}
	
	public function weekIsPast($week,$year=0){
		if($year==0)
			$year = $this->getCurYear();
			
		$past = ($year < $this->getCurYear() OR $week < $this->getCurWeek());
		
		return $past;
	}
	
	public function getCurStage(){
		return $this->stage;
	}
	
	public function getCurStageID(){
		return $this->stageID;
	}
	
	public function getCurFAStage(){
		return $this->FAStage;
	}
	
	public function getCurTransactionStage(){
		return $this->transactionStage;
	}
	
	public function validTeam($teamId){
		return is_numeric($teamId) AND $teamId < 32 AND $teamId >= 0;
	}
		
	public function getDivisionName($confId,$divId)
	{
		$divName = "";
		switch($confId)
		{
			case 1:
				switch($divId)
				{
					case 1: $divName = "Red"; break;
					case 2:	$divName = "Yellow"; break;
					case 3:	$divName = "Blue"; break;
					case 4:	$divName = "Green"; break;
					default: $divName = "ERROR - BAD DIV ID: ".$divId; break;
				}
				break;
			case 2:
				switch($divId)
				{
					case 1:	$divName = "Red"; break;
					case 2:	$divName = "Yellow"; break;
					case 3:	$divName = "Blue"; break;
					case 4:	$divName = "Green"; break;
					default: $divName = "ERROR - BAD DIV ID: ".$divId; break;
				}
				break;
			default: $divName = "ERROR - BAD CONF ID: ".$confId; break;
		}
		
		return $divName;
	}
	
	
	public function getConferenceName($confId)
	{
		$confName = "";
		
		switch($confId)
		{
			case 1: $confName = "Black"; break;
			case 2: $confName = "White"; break;
			default: $confName = "ERROR - BAD CONF ID: ".$confId; break;
		}
		return $confName;
	}
	
	
	public function getConferenceAbbrev($confId)
	{
		$confName = "";
		
		switch($confId)
		{
			case 1: $confName = "BFC"; break;
			case 2: $confName = "WFC"; break;
			default: $confName = "ERROR - BAD CONF ID: ".$confId; break;
		}
		return $confName;
	}
	
	public function getLogo(){
		return IMAGE_ROOT."/uflLogo.png";
	}
	
	public function getCenterfieldImg($ub=false, $year=0){
		$img = "";
		if(!$ub || $year == 0)
			$img = IMAGE_ROOT."/gc/uflLogo.png";
		else
			$img = IMAGE_ROOT."/gc/ubLogo$year.png";
			
		return $img;
	}
	
	public function getTeamCity($id,$bClarify=false)
	{
		$team = $this->getTeam($id);
		return ($team->getCity($bClarify));
		
	}
	
	
	public function getTeamNickName($id)
	{
		$team = $this->getTeam($id);
		
		return $team->getNickName();
	}
	
	public function getTeamAbbrev($id){
		$team = $this->getTeam($id);
		
		return $team->getAbbrev();
	}
	
	
	public function getTeamRecord($id,$year=0,$week=0)
	{
		$team = $this->getTeam($id);
		return $team->getRecord($year,$week);
		
	}
	
	
	public function getTeam($id)
	{
		if(!array_key_exists($id,$this->aTeams) && $this->validTeam($id))
			$this->loadAllTeams();
		
		if(!array_key_exists($id,$this->aTeams))
			echo "BAD TEAM ID IN league->getTeam(): " . $id;
			
		$team = $this->aTeams[$id];	
		return $team;
	}
	
	
	public function getTeamIdsByCity()
	{
		$aByCity = array();
		$aTms = $this->getTeams();
		
		foreach($aTms as $id => $team)
		{
			$aByCity[$id] = $team->getCity(true);
		}
		
		asort($aByCity);
		return $aByCity;
	}
	
	
	/* get array of teams in each conference */
	public function getTeamIdsByDiv(){
		$aByDiv = array();
		$aTms = $this->getTeams();
		
		foreach($aTms as $id => $team){
				
			$tc = $team->getProp('conference');
			$td = $team->getProp('division');
			
			if(!array_key_exists($tc,$aByDiv))
				$aByDiv[$tc] = array();
			
			if(!array_key_exists($td, $aByDiv[$tc]))
				$aByDiv[$tc][$td] = array();
			
			$aByDiv[$tc][$td][] = $team;
			
			
		}
		
		ksort($aByDiv);
		foreach($aByDiv as $confId => $conf){
			ksort($aByDiv[$confId]);
			foreach($conf as $divId => $div){
				usort($aByDiv[$confId][$divId], sortByCity);
			}
		}
		
		return $aByDiv;
	}
	
	
	private function getTeams()
	{
		if(!count($this->aTeams))
			$this->loadAllTeams();
			
		return $this->aTeams;
	}

	//LOAD ALL THE TEAMS WITH THIS CALL
	private function loadAllTeams()
	{
		$this->aTeams =& Team::loadAllTeams();
	}
	
}



?>