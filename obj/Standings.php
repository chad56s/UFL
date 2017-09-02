<?php

include_once(DBMGR_ROOT . '/DataMgr.php');
include_once(APP_ROOT . '/func/leagueUtils.php');
include_once(OBJECT_ROOT . '/League.php');


/*
 * CLASS: Standings
 * 
 * Used to retrieve and display standings from the current year
 */
class Standings
{
	private $dataMgr;
	private $qStandings;
	private $confId;
	private $divId;
	private $teamId;
	private $league;
	private $year;
	private $week;
	

	const DISPLAY_SHORT = 0;
	const DISPLAY_LONG = 1;

	public function __construct(League &$league, $year=0, $week=0)
	{
		
		$this->league = $league;
		
		$this->dataMgr = createNewDataManager();
		
		$this->qStandings = NULL;
		
		//Can be set by an inheriting class for purposes of displaying the record
		//of one conference, division, or team.
		$this->divId = -1;
		$this->confId = -1;
		$this->teamId = -1;
		
		$this->year = $year;
		$this->week = $week;
		
		if($this->year == 0 || !$this->league->validYear($this->year))
			$this->year = $this->league->getCurYear();
		if($this->week == 0 || !$this->league->validWeek($this->week))
			$this->week = $this->league->getCurWeek();
	}
	
	/*
	 * TODO: change the view so that it also contains home record and away record
	 * also check the vNFL to see what else they've got (streak would be nice)
	 */
	private function getStandingsQueryCurrent()
	{
		if(weekIsPreseason($this->week))		
			$table = 'fof_standings_preseason';
		else
			$table = 'fof_standings';
			
		$sql = "
			SELECT b.cityName, b.conference, b.division,
						 a.ptsFor, a.ptsAgainst, 
						 a.wins, a.losses, a.ties, a.pct,
						 a.divWins, a.divLosses, a.divTies,
						 a.confWins, a.confLosses, a.confTies,
						 a.homeWins, a.homeLosses, a.homeTies, 
						 a.awayWins, a.awayLosses, a.awayTies
			  FROM fof_teams b
	 LEFT JOIN ".$table." a
			    ON a.teamID = b.ID
				 AND a.year = ".$this->year;
		
		//see if we're doing team reporting
		if($this->teamId != -1)
			$sql = $sql."
						 AND a.teamID = ".$this->teamId;
		
		//otherwise, see if conference or division reporting
		elseif($this->confId != -1)
		{
			$sql = $sql."
						 AND b.Conference = ".$this->confId; 
			//only allow division querying if conference is also supplied since division id
			//is not unique by itself.
			if($this->divId != -1)
				$sql = $sql." 
				     AND b.Division = ".$this->divId;
		}
		
		$sql = $sql." 
		 ORDER BY";
			
		//if retrieving standings only for one conference or division, don't order by division
		//so that all teams in the conference/division are ordered solely by wins.  
		//but if retrieving results for the entire league, order by conf, then division.
		if($this->confId == -1)
			$sql = $sql." b.conference, b.division,";
		
		$sql = $sql." pct desc, divWins desc, divLosses, divTies desc, confWins desc, confLosses, confTies desc, cityName";
		
	  return ($this->dataMgr->runQuery($sql));
	
	}
	
	
	private function getStandingsQueryPast(){
		
		$sql = "
			SELECT b.cityName, b.conference, b.division,
						 a.pointsFor as ptsFor, a.pointsAgainst as ptsAgainst,
						 a.wins, a.losses, a.ties, 
						 a.divWin as divWins, a.divLoss as divLosses, a.divTie as divTies,
						 a.confWins, a.confLoss as confLosses, a.confTies,
						 a.playoffs
			  FROM fof_franchise a
	 			JOIN fof_teams b 
	 					 ON a.teamIndex = b.id
			 WHERE a.year = ".$this->year."
		ORDER BY b.conference, b.division, a.wins desc, a.ties desc, a.playoffs desc 
		";
		
		return ($this->dataMgr->runQuery($sql));
		
	}
	
	
	private function getStandingsQuery(){
		if($this->year < $this->league->getCurYear())
			$this->qStandings = $this->getStandingsQueryCurrent();
		else	
			$this->qStandings = $this->getStandingsQueryCurrent();
		
	}
	
	public function getStandings()
	{
		$this->getStandingsQuery();
	}
	
	/*
	 * Default display is short
	 */
	private function printStandingsCurrent($format=0)
	{
				
		if($this->qStandings == NULL)
			$this->getStandings();
			
		$curConf = -1;
		$curDiv = -1;
		
			
		// see if any rows were returned 
		if (mysql_num_rows($this->qStandings) > 0) { 
	
	    // print them one after another 
	    echo "<table class='center'><tr>"; 
			
	    while($row  = mysql_fetch_object($this->qStandings)) 
			{ 
			
				if($row->conference != $curConf)
				{
					//if curConf is an actual conference, close its table
					if($curConf > 0)
					{
						echo "</table></td>";
					}
					
					echo "<td><table class='dataTable center'>";
					echo "<tr><th colspan=99 class='lvl1'>".strtoupper($this->league->getConferenceName($row->conference))." CONFERENCE</th>";
					$curConf = $row->conference;
				}
				
	
				if($row->division != $curDiv)
				{
					$curDiv = $row->division;
					echo "<tr><th colspan=99 class='lvl2'>".
						$this->league->getConferenceAbbrev($row->conference)." - ".
						$this->league->getDivisionName($row->conference,$row->division).
						" Division</th>";
					echo "<tr><th class='lvl3'>Team</th>
						<th class='lvl3'>W</th>
						<th class='lvl3'>L</th>
						<th class='lvl3'>T</th>
						<th class='lvl3'>PCT</th>
						<th class='lvl3'>PF</th>
						<th class='lvl3'>PA</th>
						<th class='lvl3'>Div</th>
						<th class='lvl3'>Conf</th>
						<th class='lvl3'>Home</th>
						<th class='lvl3'>Away</th></tr>";
				}
			
	    	echo "<tr>"; 
	      //city
				echo "<td>".$row->cityName." ".$row->nickname."</td>"; 
	      //wins
				echo "<td>".$row->wins."</td>"; 
	      //losses
				echo "<td>".$row->losses."</td>"; 
	      //ties
				echo "<td>".$row->ties."</td>"; 
				//pct
				if($row->wins + $row->losses + $row->ties == 0)
					echo "<td>.000</td>";
				else
	      	echo "<td>".number_format($row->pct,3)."</td>";
				
	      //points for
				echo "<td>".$row->ptsFor."</td>";
				//points against
				echo "<td>".$row->ptsAgainst."</td>";
				//div record
				echo "<td>".createRecordString($row->divWins,$row->divLosses,$row->divTies)."</td>";
				//conf record
				echo "<td>".createRecordString($row->confWins,$row->confLosses,$row->confTies)."</td>";
				//home record
				echo "<td>".createRecordString($row->homeWins,$row->homeLosses,$row->homeTies)."</td>";			
				//away record
				echo "<td>".createRecordString($row->awayWins,$row->awayLosses,$row->awayTies)."</td>";
				
				echo "</tr>"; 
				
	    } 
	    echo "</table></td></tr></table>"; 	
			
		}//end if row count
	}//end printStandings
	
	
	/*THIS FUNCTION NOT CURRENTLY USED.  It COULD be used, but all it has extra in it is
	 * whether the team made the playoffs or not. Might be able to build that into the view
	 */
	private function printStandingsPast($format=0){
		if($this->qStandings == NULL)
			$this->getStandings();
			
		$curConf = -1;
		$curDiv = -1;
		
			
		// see if any rows were returned 
		if (mysql_num_rows($this->qStandings) > 0) { 
	
	    // print them one after another 
	    echo "<table center><tr>"; 
			
	    while($row  = mysql_fetch_object($this->qStandings)) 
			{ 
			
				if($row->conference != $curConf)
				{
					//if curConf is an actual conference, close its table
					if($curConf > 0)
					{
						echo "</table></td>";
					}
					
					echo "<td><table class='dataTable center'>";
					echo "<tr><th colspan=99 class='lvl1'>".strtoupper($this->league->getConferenceName($row->conference))." CONFERENCE</th>";
					$curConf = $row->conference;
				}
				
	
				if($row->division != $curDiv)
				{
					$curDiv = $row->division;
					echo "<tr><th colspan=99 class='lvl2'>".
						$this->league->getConferenceAbbrev($row->conference)." - ".
						$this->league->getDivisionName($row->conference,$row->division).
						" Division</th>";
					echo "<tr><th class='lvl3'>Team</th>
						<th class='lvl3'>W</th>
						<th class='lvl3'>L</th>
						<th class='lvl3'>T</th>
						<th class='lvl3'>PCT</th>
						<th class='lvl3'>PF</th>
						<th class='lvl3'>PA</th>
						<th class='lvl3'>Div</th>
						<th class='lvl3'>Conf</th></tr>";
				}
			
				if($row->playoffs > 0)
					echo "<tr class='special'>";
				else
	    		echo "<tr>"; 
	      //city
				echo "<td>".$row->cityName." ".$row->nickname."</td>"; 
	      //wins
				echo "<td>".$row->wins."</td>"; 
	      //losses
				echo "<td>".$row->losses."</td>"; 
	      //ties
				echo "<td>".$row->ties."</td>"; 
				//pct
	      echo "<td>".number_format($row->pct,3)."</td>";
				
	      //points for
				echo "<td>".$row->ptsFor."</td>";
				//points against
				echo "<td>".$row->ptsAgainst."</td>";
				//div record
				echo "<td>".createRecordString($row->divWins,$row->divLosses,$row->divTies)."</td>";
				//conf record
				echo "<td>".createRecordString($row->confWins,$row->confLosses,$row->confTies)."</td>";
				
				echo "</tr>"; 
				
	    } 
	    echo "</table></td></tr></table>"; 	
			
		}//end if row count
	}
	
	public function printStandings($format=0){
		if($this->year < $this->league->getCurYear())
			$this->printStandingsCurrent($format);
		else
			$this->printStandingsCurrent($format);
		
	}
	
	public function __destruct()
	{
		
	}
} //end basic standings class

?>