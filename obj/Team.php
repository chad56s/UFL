<?php

include_once(DBMGR_ROOT . '/DataMgr.php');
include_once(OBJECT_ROOT . '/League.php');
include_once(OBJECT_ROOT . '/Staff.php');
include_once(OBJECT_ROOT . '/Roster.php');
include_once(APP_ROOT . '/func/leagueUtils.php');

/*
 * CLASS TEAM:
 * 
 * to use: do not call constructor, it is private.  Call the static methods loadAllTeams or fromId
 */
class Team{
	
	private static $curYear;
	private static $curWeek;
	
	private $props;
	private $staff;
	private $roster;
	
	
	//loads all teams with one database call.  Use this function to return an array of team objects
	public static function loadAllTeams()
	{
		$tmpDataMgr = createNewDataManager();
		$sql = self::getQueryTextAndCurYearWeek();
		$qTeam = $tmpDataMgr->runQuery($sql);
		$aTeams = array();
		
		while($row = mysql_fetch_assoc($qTeam))
		{		
			$aTeams[$row["id"]] = new Team($row);
		}
		
		return $aTeams;
		
	}
	
	//load one single team.  Call this function to load only one team
	public static function fromId($id)
	{
		$tmpDataMgr = createNewDataManager();
		$sql = self::getQueryTextAndCurYearWeek($id);
		
		$qTeam = $tmpDataMgr->runQuery($sql);
		
		$team = new Team(mysql_fetch_assoc($qTeam));
		
		return $team;
		
	}
	
	
	public function getCity($bClarify=true,$bFormat=true)
	{
		
		$city = $this->getProp('cityName');
		
		//have to leave unformatted for certain things like parsing log files
		if($bFormat)
			$city = cityFormat($city);
			
		if(strtoupper($city) == 'NEW YORK' && $bClarify)
		{
			$city = $city." ".substr($this->getNickName($id),0,1).".";
		}
		
		return $city;
		
	}
	
	public function getId(){
		return $this->getProp('id');
	}
	
	public function getColors() {
		return array($this->getProp("color1"), $this->getProp("color2"), $this->getProp("color3"));
	}

	public function getNickName()
	{
		return $this->getProp('nickname');
	}
	
	
	public function getFullName()
	{
		return $this->getCity(false) . " " . $this->getNickName();
	}
	
	
	public function getAbbrev(){
		return $this->getProp('abbrev');
	}
	
	public function getRecord($year, $week)
	{
		$record = "0-0";
		
		if($year == 0)
			$year = self::$curYear;
		
		if($year != self::$curYear){
			$dmgr = createNewDataManager();
			if(weekIsPreseason($week))
				$tbl = 'fof_standings_preseason';
			elseif(weekIsPostseason($week))
				$tbl = 'fof_standings_postseason';
			else
				$tbl = 'fof_standings';
				
			$sql = "SELECT wins, losses, ties 
							  FROM " . $tbl . " 
							 WHERE teamId = " . $this->getProp("id") . "
						     AND year = " . $year;
			$qResults = $dmgr->runQuery($sql);
			
			while($row = mysql_fetch_object($qResults)){
				$record = createRecordString($row->wins,$row->losses,$row->ties);
			}
		}
		else{
			if(weekIsPreseason($week))
				$record = $this->getProp('prerecord');
			elseif(weekIsPostseason($week))
				$record = $this->getProp('postrecord');
			else
				$record = $this->getProp('record');
			
		}
		
		return $record;
	}
	
	public function getHelmetImg($side='right', $year=0)
	{
	
		$imgFile = sprintf("%02d_%s.png", $this->getProp("id"), $side);

		//look for a specific year
		if($year != 0 && $year != self::$curYear){
		
			$regex = sprintf("/%02d_%s_([0-9]{4})/", $this->getProp("id"), $side);
			$aFiles = preg_grep($regex, scandir(APP_ROOT . "/images/teams/helmets"));
			
			$helmetYear = 9999;
			$regExMatch = null;
			foreach($aFiles as $file){
				preg_match("/[0-9]{4}/", $file, $regExMatch);
				$fileYear = intval($regExMatch[0]);
				if($fileYear >= $year)
					$helmetYear = min($helmetYear, $fileYear);
			}
			
			if($helmetYear != 9999)
				$imgFile = sprintf("%02d_%s_%d.png", $this->getProp("id"), $side, $helmetYear);
		}
	
		return IMAGE_ROOT . "/teams/helmets/" . $imgFile;
		
	}
	
	
	public function getSmallLogoImg()
	{
		$imgFile = sprintf("%02dsmall.gif",$this->getProp("id"));
		return IMAGE_ROOT . "/teams/logo/" . $imgFile;
		
	}
	
	public function getLogoImg()
	{
		$imgFile = sprintf("logo%02d.jpg",$this->getProp("id"));
		return SOLECISMIC_ROOT . "/" . $imgFile;
		
	}
	
	public function getEndzoneImg()
	{
		$imgFile = sprintf("%02dendzone.png",$this->getProp("id"));
		return IMAGE_ROOT . "/teams/endzones/" . $imgFile;
		
	}
	
	public function getCenterfieldImg()
	{
		$lge = getLeague();
		return $lge->getCenterfieldImg();
		//TODO: make individual centerfield logos
		//$imgFile = sprintf("endzone%02d.png",$this->getProp("id"));
	}
	
	public function getStyle(){
	
		return "tm_th1_" . $this->getId();
	
	}
	
	//get other properties without an explicit get function	
	public function getProp($prop){
		if($this->props)
			return $this->props[$prop];
	}
	
	public function getRoster(){
		$db = getDBConnection();
		
		$sql = "SELECT * FROM fof_playeractive where Team = " . $this->getId();
	}
	
	public function getStaffHC($year=0){
		$staff = null;
		if($year == 0 && count($this->staff) > 0)
			$staff = $this->staff[0];
		else{
			$staff = $this->doStaffQuery(unmapStaffPositionToTransactionType("hc"), $year);
			if($year == 0)
				$this->staff[0] = $staff;
		}
		
		return $staff;
	}
	
	public function getStaffOC($year=0){
		$staff = null;
		if($year == 0 && count($this->staff) > 1)
			$staff = $this->staff[1];
		else{
			$staff = $this->doStaffQuery(unmapStaffPositionToTransactionType("oc"), $year);
			if($year == 0)
				$this->staff[1] = $staff;
		}
		
		return $staff;
	}
	
	public function getStaffDC($year=0){
		$staff = null;
		if($year == 0 && count($this->staff) > 2)
			$staff = $this->staff[2];
		else{
			$staff = $this->doStaffQuery(unmapStaffPositionToTransactionType("dc"), $year);
			if($year == 0)
				$this->staff[2] = $staff;
		}
		
		return $staff;
	}
	
	public function getStaffLS($year=0){
		$staff = null;
		if($year == 0 && count($this->staff) > 3)
			$staff = $this->staff[3];
		else{
			$staff = $this->doStaffQuery(unmapStaffPositionToTransactionType("ls"), $year);
			if($year == 0)
				$this->staff[3] = $staff;
		}
		
		return $staff;
	}
	
	public function getAllStaff($year=0){
	
		return array(	$this->getStaffHC($year), 
							$this->getStaffOC($year), 
							$this->getStaffDC($year), 
							$this->getStaffLS($year));
	
	}
	
	public function __destruct()
	{
		
	}
	
	
	//private construct function which can only be called from the public methods fromId and loadAllTeams
	private function __construct($rec)
	{
		
		$this->setProps($rec);
		$this->staff = null;
		$this->roster = null;
		
		$this->loadStaff();
		
	}
	
	//the query for loading the team data. -1 (or any invalid teamId) will return 
	//query text that will get all teams.  Will also set the cur year and week
	private static function getQueryTextAndCurYearWeek($id=-1)
	{
		$lge = getLeague();
		
		self::$curYear = $lge->getCurYear();
		self::$curWeek = $lge->getCurWeek();
		
		$sql = "SELECT a.id, a.cityName, a.nickname, a.abbrev, a.conference, a.division,
									 b.wins, b.losses, b.ties,
									 c.wins as pwins, c.losses as plosses, c.ties as pties,
									 d.wins as powins, d.losses as polosses, d.ties as poties,
									 e.color1, e.color2, e.color3
						  FROM fof_teams a
				 LEFT JOIN fof_standings b
						  	ON a.id = b.teamId
							 AND b.year = " . $lge->getCurYear() . "
				 LEFT JOIN fof_standings_preseason c
				 				ON a.id = c.teamId
							 AND c.year = " . $lge->getCurYear() . "
				 LEFT JOIN fof_standings_postseason d
				 				ON a.id = d.teamId
							 AND d.year = " . $lge->getCurYear() . "
							JOIN fof_team_colors e
							  ON a.id = e.teamId
							 ";
						 	
		if($lge->validTeam($id))
			$sql = $sql . " WHERE a.id = " . mysql_real_escape_string($id);
		return $sql;
	}
	
	//this function is private because the class expects exactly those rows (plus record and prerecord) 
	//which are returned from the query.  Properties should not attempt to be set outside the class
	private function setProps($rec)
	{
		
		$this->props = $rec;
		$this->props["record"] = createRecordString($this->props["wins"],$this->props["losses"],$this->props["ties"]);
		$this->props["prerecord"] = createRecordString($this->props["pwins"],$this->props["plosses"],$this->props["pties"]);
		//for the current year, I want the post-season record to be added to regular season record
		$this->props["postrecord"] = createRecordString($this->props["powins"] + $this->props["wins"],
																										$this->props["polosses"] + $this->props["losses"],
																										$this->props["poties"] + $this->props["ties"]);
	}
	
	private function loadStaff(){
		if(is_null($this->staff)){
			$this->staff = $this->getAllStaff();
		}
	}
	
	private function doStaffQuery($staffTransactionType, $year=0){
	
		$lge = getLeague();
	
		if($year == 0 || !$lge->validYear($year)){
			$year = $lge->getCurYear();
		}
		
		$isCoach = $staffTransactionType != 12;  //scouts
		
		$table = "fof_transactions_coaches";
		$idCol = "CoachID";
		if(!$isCoach){
			$table = "fof_transactions_scouts";
			$idCol = "ScoutID";
		}
			
		$sql = "SELECT $idCol, season
					 FROM $table
					WHERE TeamID = {$this->getId()}
					  AND Season <= $year";
		
		if($isCoach)
			$sql .= " AND Type = $staffTransactionType";
			
		$sql .= " ORDER BY season desc";
		
		$db = getDBConnection();
		
		$qStaff = $db->runQuery($sql);
		
		$staff = null;
		if(mysql_num_rows($qStaff) > 0){
			$staffResults = mysql_fetch_assoc($qStaff);
			if($isCoach)
				$staff = new Coach($staffResults[$idCol]);
			else
				$staff = new Scout($staffResults[$idCol]);
		}
		
		return $staff;
	
	}
	
}  //end team class


?>
