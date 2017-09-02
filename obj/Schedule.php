<?php

include_once(DBMGR_ROOT . '/DataMgr.php');
include_once(OBJECT_ROOT . '/gameSummary.php');
include_once(OBJECT_ROOT . '/gamePreview.php');

define("MAX_BYES_TO_DISPLAY","10");

class Schedule {
	
	protected $dataMgr;
	protected $qSchedule;

	protected $league;
	
	protected $week;
	protected $year;
	protected $teamId;
	
	
	public function __construct(League &$league, $year=0, $week=0, $teamId=0)
	{
		$this->dataMgr = createNewDataManager();
		
		$this->qSchedule = NULL;
		$this->league =& $league;
		
		$this->week = $week;
		
		$this->year = $year;
		$this->teamId = $teamId;
		
	}
	
	public function __destruct()
	{

	}
	
	public function getQuery(){
		return $this->qSchedule;
	}
	
}

/*
 * CLASS: ScheduleWeek
 * 
 * Displays weekly schedule
 */
class ScheduleWeek extends Schedule
{
	//used to get the byes for this week's schedule
	private $dataMgrByes;
	private $qByes;
	
	public function __construct(League &$league, $week=0, $year=0, $teamId=-1)
	{
		//validate the week
		if(!$league->validWeek($week))
			$week = $league->getWeek();
		
		//validate the year
		if(!$league->validYear($year))
			$year = $league->getCurYear();
		
		//construct parent
		parent::__construct($league, $year, $week, $teamId);
		
		$this->dataMgrByes = createNewDataManager();
		$this->qByes = NULL;
	}

	
	public function getSchedule($week=0,$year=0)
	{
		if($this->league->validWeek($week))
			$this->week = $week;
	
		if($this->league->validYear($year))
			$this->year = $year;
			
		//future
		if(!$this->league->weekIsPast($this->week,$this->year))
		{
			$sql = "
						SELECT a.cityName as home, a.Id as homeId, 
									 c.cityName as away, c.Id as awayId,
									 b.score as homeScore, b.oppScore as awayScore,
									 b.ID as ID
						  FROM fof_teams a
				 			JOIN fof_teamschedule b 
				  			ON a.Id = b.teamId
				 			JOIN fof_teams c
				 				ON b.opponentId = c.Id
						 WHERE b.week = ".(int)$this->week . "
						   AND b.year = ".(int)$this->year;

			//hunch: I think FOF is putting lower ID as away team in log file name; see also getGameFileName in leagueUtils.php						 
			if($this->week == WEEK_ULTIMATE_BOWL)
				$sql = $sql . " AND a.Id > c.Id"; 
			else	
				$sql = $sql . " AND b.away = 0";
							 
	
		}
		else //past
		{
			$sql = "
						SELECT b.cityName as home, b.Id as homeId,
									 c.cityName as away, c.Id as awayId,
									 a.HomeScore as homeScore, a.awayScore as awayScore,
									 a.ID as ID
						  FROM fof_gameresults a
							JOIN fof_teams b
								ON a.homeTeam = b.Id
						  JOIN fof_teams c
								ON a.awayTeam = c.Id
				 LEFT JOIN fof_gotw d
				 			  ON a.ID = d.gameResults_ID
						 WHERE a.week = ".(int)$this->week."
						   AND a.year = ".(int)$this->year."
				  ORDER BY d.rank is null, d.rank"; //sort by is null first, puts nulls at bottom
		}
		$this->qSchedule = $this->dataMgr->runQuery($sql);
		
	}//end getSchedule
	
		
	public function getByes($week=0,$year=0)
	{
		if($this->league->validWeek($week))
			$this->week = $week;
	
		if($this->league->validYear($year))
			$this->year = $year;
			
		//current year
		if($this->year == $this->league->getCurYear())
		{
			$sql = "
					SELECT a.cityName, a.Id
						FROM fof_teams a
					 WHERE a.id NOT IN (
					    	 	SELECT teamID 
									  FROM fof_teamschedule
									 WHERE week = ".(int)$this->week."
									   AND year = ".(int)$this->year."
					 )  
				ORDER BY a.cityName, a.nickname
			";
		}
		else //past season
		{
			$sql = "
					SELECT a.cityName, a.Id
						FROM fof_teams a
					 WHERE a.id NOT IN (
					    	 	SELECT awayTeam 
									  FROM fof_gameresults
									 WHERE week = ".(int)$this->week."
									   AND year = ".(int)$this->year."
					 )
					   AND a.id NOT in (
						 			SELECT homeTeam
									  FROM fof_gameresults
									 WHERE week = ".(int)$this->week."
									   AND year = ".(int)$this->year."
					 )  
				ORDER BY a.cityName
			";
		}
		
		$this->qByes = $this->dataMgrByes->runQuery($sql);
	}//end getByes
	
		
	public function printSchedule() 
	{
		//if the week has been played, show the score and winner
		$past = ($this->league->weekIsPast($this->week,$this->year));
		
		
		if($this->qSchedule == NULL)
			$this->getSchedule();
		
		if(mysql_num_rows($this->qSchedule) > 0)
		{
			
			mysql_data_seek($this->qSchedule,0);
			
			echo "<table class='dataTable center'>";
			echo "<tr><th colspan=99 class='lvl1'>UFL League Schedule - ".$this->year."</th></tr>";
			echo "<tr><th colspan=99 class='lvl2'>".getWeekString($this->week,true)."</th></tr>";
			echo "<tr>
							<th class='lvl3'>Visitor</th>
							<th class='lvl3'>Score</th>
							<th class='lvl3'>Home</th>
							<th class='lvl3'>Score</th>
							<th class='lvl3'>Box | Log | GC</th>
						</tr>";
						
			while($row = mysql_fetch_object($this->qSchedule))
			{
				$homeStyle = "";
				$visitStyle = "";
				if($past AND $row->awayScore > $row->homeScore)
					$visitStyle = 'special';
				else if($past AND $row->homeScore > $row->awayScore)
					$homeStyle = 'special';
				
				$awayTeam = $this->league->getTeamCity($row->awayId,true);
				$awayScore = $row->awayScore;
				if(!$past)
				{
					$awayTeam = $awayTeam." (".$this->league->getTeamRecord($row->awayId,$this->year,$this->week).")";
					$awayScore = "";
				}

				$homeTeam = $this->league->getTeamCity($row->homeId,true);
				$homeScore = $row->homeScore;
				if(!$past)
				{
					$homeTeam = $homeTeam." (".$this->league->getTeamRecord($row->homeId,$this->year,$this->week).")";
					$homeScore = "";
				}
				
				echo "<tr game='".$row->ID."'>";
				echo "<td class='".$visitStyle."'>".$awayTeam."</td>";
				echo "<td class='".$visitStyle."'>".$awayScore."</td>";
				echo "<td class='".$homeStyle."'>".$homeTeam."</td>";
				echo "<td class='".$homeStyle."'>".$homeScore."</td>";
				
				//print the links to log and box scores
				echo "<td class='center'>";
				echo createGameSummaryLinks($this->year,$this->week,$row->homeId,$row->awayId);
				echo "</td>";
				
				
				echo "</tr>";
			}//end while
			
			$byeString = $this->getByeTeamString();
			if(strlen($byeString) > 0)
				echo "<tr><th class='lvl3' colspan=99 style='text-align:left;'>" . $byeString . "</td></tr>";
				
			echo "</table>";
				
			if($this->week != WEEK_ULTIMATE_BOWL){	
				//print the jquery that will load the game summary or preview
				if($past)
					$widget = "gameSummary";
				else	
					$widget = "gamePreview";
					
				$this->addGameSummaryWidget($widget);
	
			}
			else{ //ultimate bowl
			
			   mysql_data_seek($this->qSchedule,0);
				$row = mysql_fetch_object($this->qSchedule);
				if($past){
					$summary = new gameReview("large");
					$summary->showReview($row->ID);
				}
				else{
					$summary = new gamePreview("large");
					$summary->showPreview($row->ID);
				}
			}
		}

	}


	public function getByeTeamString()
	{
		$byeString = "";
		
		if($this->qByes == NULL)
			$this->getByes();
		else
			mysql_data_seek($this->qByes,0);
		
		$numRows = mysql_num_rows($this->qByes);
		if($numRows > 0 AND $numRows < MAX_BYES_TO_DISPLAY)
		{
			$byeString = "Byes: ";
			$curRow = 1;
			while($row = mysql_fetch_object($this->qByes))
			{
				$byeString = $byeString . $this->league->getTeamCity($row->Id,true);
				if($curRow < $numRows)
				{
					$byeString = $byeString . ", ";
				}
				$curRow = $curRow + 1;
			}//end while\
		}//end if($numRows..)
		
		return $byeString;
	}
	
	public function addGameSummaryWidget($widgetClass)
	{			
			echo "
				<div id='gameSummary' class='center'>
				</div>
				
				<script>
					$('tr[game]').click(
						function(){	
							var i = this.getAttribute('game');
							$('tr[game]').removeClass('selected');
							$(this).addClass('selected');
							$('#gameSummary').load('widgets/".$widgetClass.".php?ajaxWidgetGameID='+i+'&rndm='+Math.random()*80000);
						}
					)
					
					$('tr[game]').css('cursor','pointer');
					
					
				</script>

			";
	}	

	public function __destruct()
	{
		
	}

} //end ScheduleWeekly
	
	

/*
 * CLASS: ScheduleTeam
 * 
 * Retrieves and displays a team's regular season schedule
 */	
class ScheduleTeam extends Schedule
{
	
	public function __construct(League &$league, $teamId, $year=0, $week=0)
	{
		//validate the year
		if(!$league->validYear($year))
			$year = $league->getCurYear();
		
		//validate the team
		if(!$league->validTeam($teamId))
			$teamId = 0;
			
		parent::__construct($league, $year, $week, $teamId);
	}
	
	public function getScheduleStartWeek()
	{
		return WEEK_REGSEASON_START;
	}
	
	public function getScheduleEndWeek()
	{
		return WEEK_REGSEASON_END;
	}
	
	public function getSchedule()
	{

		$sql = "
					SELECT b.opponentId, b.away, b.week, b.score, b.oppScore, b.ID, c.ID as resultID
					  FROM fof_teamschedule b
			 LEFT JOIN fof_gameresults c
			 				ON (b.teamId = c.homeTeam OR b.teamId = c.awayTeam)
								  AND b.week = c.week
									AND b.year = c.year
					 WHERE b.week >= ".$this->getScheduleStartWeek()."
					   AND b.week <= ".$this->getScheduleEndWeek()."
						 AND b.teamId = ".$this->teamId. "
						 AND b.year = ".(int)$this->year;
	
		$this->qSchedule = $this->dataMgr->runQuery($sql);
		
	}	


	public function printSchedule()
	{
		
			echo "<table class='dataTable center' width='440px'>";
			echo "<tr><th colspan=99 class='tm_th1_".$this->teamId."'>".
				$this->league->getTeamCity($this->teamId)." ".
				$this->league->getTeamNickname($this->teamId)." - ".
				$this->year."</th></tr>";
				
			echo "<tr>
							<th class='lvl3'>Week</th>
							<th class='lvl3'>Opponent</th>
							<th class='lvl3'>Score</th>
							<th class='lvl3'>W/L</th>
							<th class='lvl3' nowrap>Box | Log | GC</th>
						</tr>";
				
		$lastWeek = $this->getScheduleStartWeek();
		
		while($row = mysql_fetch_object($this->qSchedule))
		{
			//check for bye week
			if($row->week > $lastWeek + 1)
			{
				echo "<tr>";
				echo "<td>".getWeekString($lastWeek +1)."</td>";
				echo "<td colspan='99' style='text-align:center;'>BYE</td></tr>";
			}
				
			$past = $this->league->weekIsPast($row->week,$this->year);
		
			$cls = '';
			$week = getWeekString($row->week);
			$opponent = $this->league->getTeamCity($row->opponentId)." ".$this->league->getTeamNickname($row->opponentId);
			if($row->away == 1)
			{
				$opponent = "@ ".$opponent;
				$home = $row->opponentId;
				$away = $this->teamId;
			}
			else	
			{
				$opponent = strtoupper($opponent);
				$away = $row->opponentId;
				$home = $this->teamId;
			}
				
			if($past)
			{
				$score = $row->score."-".$row->oppScore;
				if($row->score > $row->oppScore)
				{
					$wl = 'W';
					$cls = 'special';
				}
				else if($row->oppScore > $row->score)
					$wl = 'L';
				else	
					$wl = 'T';
					
				$trID = $row->resultID;
				$trAttr = 'gameResult';
			}
			else
			{
				$score = "";
				$wl = "";
				$trID = $row->ID;
				$trAttr = 'game';
			}
				
			
			echo "<tr class='".$cls."' ".$trAttr."='".$trID."'>";
			echo "<td>".$week."</td>";
			echo "<td>".$opponent."</td>";
			echo "<td nowrap>".$score."</td>";
			echo "<td>".$wl."</td>";
			echo "<td nowrap style='text-align:center;'>".createGameSummaryLinks($this->year,$row->week,$home,$away)."</td></tr>";
			
			$lastWeek = $row->week;
			
		}
		
		echo "<tr><th colspan=99 class='tm_th1_".$this->teamId."' style='text-align:right'>".
				$this->league->getTeamRecord($this->teamId,$this->year,$this->getScheduleStartWeek()).
				"</th></tr>";
		echo "</table>";
		
		$this->addGameSummaryWidget();	
	}
	
	public function addGameSummaryWidget()
	{			
			echo "
				<div id='gameSummary' class='center' style='display:table;'>
				</div>
				
				<script>
					$('tr[game]').click(
						function(){	
							var i = this.getAttribute('game');
							$('tr[game]').removeClass('selected');
							$('tr[gameResult]').removeClass('selected');
							$(this).addClass('selected');
							$('#gameSummary').load('widgets/gamePreview.php?ajaxWidgetGameID='+i+'&rndm='+Math.random()*80000);
						}
					);
					
					$('tr[gameResult]').click(
						function(){	
							var i = this.getAttribute('gameResult');
							$('tr[game]').removeClass('selected');
							$('tr[gameResult]').removeClass('selected');
							$(this).addClass('selected');
							$('#gameSummary').load('widgets/gameSummary.php?ajaxWidgetGameID='+i+'&rndm='+Math.random()*80000);
						}
					);
					
					$('tr[gameResult]').css('cursor','pointer');
					$('tr[game]').css('cursor','pointer');
						
					
					
				</script>

			";
	}
	
	
	public function setTeam($teamId)
	{
		$this->teamId = $teamId;
	}

	
}
//END TEAM SCHEDULE

/*
 * CLASS: SchedulePreseasonTeam
 * 
 * Retrieves and displays a team's schedule
 */	
class SchedulePreseasonTeam extends ScheduleTeam
{
	
	public function __construct(League &$league, $teamId, $year=0, $week=0)
	{
		//validation is done in ScheduleTeam.
		//put custom validation here if necessary later			
		parent::__construct($league, $teamId, $year, $week);
	}
	
	
	public function getScheduleStartWeek()
	{
		return 1;
		return WEEK_PRESEASON_START;
	}
	
	public function getScheduleEndWeek()
	{
		return 5;
		return WEEK_PRESEASON_END;
	}
	
}
//END SCHEDULE PRESEASON TEAM



/*
 * CLASS: SchedulePreseasonTeam
 * 
 * Retrieves and displays a team's post-season schedule
 */	
class SchedulePostseasonTeam extends ScheduleTeam
{
	
	public function __construct(League &$league, $teamId, $year=0, $week=0)
	{
		//validation is done in ScheduleTeam.
		//put custom validation here if necessary later			
		parent::__construct($league, $teamId, $year, $week);
	}
	
	
	public function getScheduleStartWeek()
	{
		return WEEK_WILDCARD;
	}
	
	public function getScheduleEndWeek()
	{
		return WEEK_ULTIMATE_BOWL;
	}
	
}
//END SCHEDULE PRESEASON TEAM

?>