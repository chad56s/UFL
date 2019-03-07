<?php
  

	include_once(APP_ROOT . '/func/leagueUtils.php');
	include_once(OBJECT_ROOT . '/League.php');
	include_once(DBMGR_ROOT . '/DataMgr.php');
	include_once(OBJECT_ROOT . '/gamePreview.php');
	include_once(OBJECT_ROOT . '/gameSummary.php');
	
	
	//information concerning ALL games of the week (there can be many)
	class GamesOfTheWeek{
		
		private $dataMgr;
		
		public function __construct()
		{	
			$this->dataMgr = createNewDataManager();
		}
		
		/*
		 * show game of the week info with rank = r for week w, year y
		 */
		public function showGameInfo($w,$y,$r=0,$size='medium')
		{
			$lge = new League();
			$q = $this->getGamesOfTheWeek($w,$y);
			
			while($row = mysql_fetch_assoc($q))
			{
				if($row["Rank"] == $r)
					$this->displayGame($row,$size);
			}
				
		}
		
		
		public function getNumGames($w,$y)
		{
			$q = $this->getGamesOfTheWeek($w,$y);
			return mysql_num_rows($q);
		}
		
		
		public function __destruct()
		{	}
		
		
		protected function getSql($w,$y)
		{
			$sql = "SELECT * 
								FROM fof_gotw 
							 WHERE week = " . $w . " 
							 	 AND year = ". $y . " 
					  ORDER BY rank";
			
			return $sql;
		}
		
		protected function getGamesOfTheWeek($w,$y,$tryManage=true)
		{
			
		
			$sql = $this->getSql($w,$y);
							 
			$qGame = $this->dataMgr->runQuery($sql);
			
			//if we got no rows back, we might need to have the games of the week created first. (we might
			//be in a new week)
			if(mysql_num_rows($qGame) == 0 && $tryManage)
			{
				$gotwMgr = new GOTWManager();
				$gotwMgr->doManagement();
				
				//try again if game mgmt was done
				if($gotwMgr->getNumGamesCreated() > 0)
					return $this->getGamesOfTheWeek($w,$y,false);
				
			}
			
			return $qGame;
			
		}
		
		/*
		 * After retrieving results of a game of teh week query, pass to this function
		 * an associative array of a row.  It will display the game
		 */
		protected function displayGame($row,$size)
		{
			$lge = new League();
			
			if($lge->weekIsPast($row["Week"],$row["Year"]) && is_numeric($row["GameResults_ID"]))
			{
				$info = new gameReview($size);
				$info->showReview($row["GameResults_ID"]);
			}
			elseif (is_numeric($row["TeamSchedule_ID"]))
			{
				$info = new gamePreview($size);
				$info->showPreview($row["TeamSchedule_ID"]);
			}
			else{
				echo "invalid game of the week<br/>";
			}
			
		}
		
	} //end class GamesOfTheWeek
	
	
	//information concerning the SINGLE game of the week
	class GameOfTheWeek extends GamesOfTheWeek{
		
		
		protected function getSql($w,$y)
		{
			$sql = "SELECT * 
								FROM fof_gotw 
							 WHERE week = " . $w . " 
							 	 AND year = ". $y . " 
					  		 AND rank = 0";
			return $sql;
		}
		
	}//end class GameOfTheWeek
	
	class GOTWManager
	{
		
		private $lge;
		private $numGamesUpdated;
		private $numGamesCreated;
		private $dataMgr;
		private $bDebug;
		
		public function __construct($bDoDebug=false)
		{
			$this->lge = new League();
			$this->numGamesUpdated = 0;
			$this->numGamesCreated = 0;
			
			$this->dataMgr = createNewDataManager();
			
			$this->bDebug = $bDoDebug;	
		}
		
		
		public function checkUpToDate()
		{
			$sql = "SELECT COUNT(1)
								FROM fof_gotw
							 WHERE week = " . $this->lge->getCurWeek() . "
							   AND year = " . $this->lge->getCurYear();
			
			$qgotw = $this->dataMgr->runQuery($sql);
			
			$count = mysql_fetch_row($qgotw);
			
			$bUpToDate = ($count[0] > 0);
			
			if($this->bDebug)
				echo ("gotw up to date? " . $bUpToDate . " (" . $count[0] . ")<br/>");
			
			return($bUpToDate);
			
		}
	
		
		public function getFeaturedPreseasonTeams($year)
		{
			$sql = "SELECT awayTeam, homeTeam
							  FROM fof_gameresults a
								JOIN fof_gotw b
								  ON a.ID = b.gameResults_ID
							 WHERE b.year = " . $year . "
							   AND b.week < " . WEEK_REGSEASON_START;
								 
			$qFeat = $this->dataMgr->runQuery($sql);
			
			$aTeams = array();
			while($a = mysql_fetch_array($qFeat))
			{
				foreach($a as $i => $teamId)
				{
					$aTeams[] = $teamId;
				}
			}
			return $aTeams;
							 
		}
	
		
		
		public function doManagement()
		{
			//update the past GOTW's so they point to the results
			$this->updatePastGOTW();
			//create the current GOTWs			
			$this->createCurrentGOTW();
			
		}
	
	
		public function getNumGamesCreated()
		{
			return $this->numGamesCreated;
		}
	
	
		public function getNumGamesUpdated()
		{
			return $this->numGamesUpdated;
		}
		
		/*
		 * updatePastGOTW
		 * 
		 * makes sure that games of the week occurring in the past have foreign keys
		 * set up to the game results table
		 */
		private function updatePastGOTW()
		{
			//update past games of the week
			$sql = "UPDATE fof_gotw a
								 SET gameResults_ID = 
								 		( SELECT b.ID 
												FROM fof_gameresults b
												JOIN fof_teamschedule c
													ON b.homeTeam = c.TeamId 
												 AND b.awayTeam = c.OpponentId
												 AND b.year = c.year
												 AND b.week = c.week
											 WHERE c.ID = a.teamSchedule_ID )
							 WHERE (
							 					(
													  	a.year = " . $this->lge->getCurYear() . "
							   					AND a.week < " . $this->lge->getCurWeek() . 
										 	 ")
								 				OR (a.year < " . $this->lge->getCurYear() . ")
										 )
								 AND a.gameResults_ID IS NULL"
							 ;
					
				$qResults = $this->dataMgr->runQuery($sql);
				$this->numGamesUpdated = mysql_affected_rows();
				
				if($this->bDebug)
					echo ($this->numGamesUpdated . " games updated!<br/>");
		}
		
		private function createCurrentGOTW()
		{
			//if we're up to date, just return
			if($this->checkUpToDate())
				return;
			
				
			if($this->lge->getCurWeek() < WEEK_REGSEASON_START)
			{
				//preseason games - get the pre season teams that have already been featured.  Eliminate
				//those teams from consideration.
				$aTeams = $this->getFeaturedPreseasonTeams($this->lge->getCurYear());

				//add -1 to array because it'll be empty the first week
				$aTeams[] = -1;
				
				//have to left join for new seasons. Don't care about last year's stats for preseason
				$sql = "SELECT a.ID, b.pct as pct1, c.pct as pct2,
												b.wins as wins1, c.wins as wins2,
												b.losses as losses1, c.losses as losses2,
												b.ties as ties1, c.ties as ties2,
												
												0 as lastYear_pct1, 0 as lastYear_pct2,
												0 as lastYear_wins1, 0 as lastYear_wins2,
												0 as lastYear_losses1, 0 as lastYear_losses2,
												0 as lastYear_ties1, 0 as lastYear_ties2 
									FROM fof_teamschedule a
									JOIN fof_gameinfo d
									  ON a.year = d.curYear
									 AND a.week = d.week
						 LEFT JOIN fof_standings_preseason b
									  ON a.teamId = b.teamId
									 AND b.year = d.curYear
						 LEFT	JOIN fof_standings_preseason c
									  ON a.opponentId = c.teamId
									 AND b.year = d.curYear
								 WHERE a.away = 0
									 AND a.teamID NOT IN (" . implode(",",$aTeams) . ")
									 AND a.opponentID NOT IN (" . implode(",",$aTeams) . ")
				";
				$numGamesOTW = 1;
				
			}
			else
			{
				//create regular season and post season GOTW
				//have to left join for week 1
				$sql = "SELECT a.ID, IFNULL(b.pct,0) as pct1, IFNULL(c.pct,0) as pct2,
												IFNULL(b.wins,0) as wins1, IFNULL(c.wins,0) as wins2,
												IFNULL(b.losses,0) as losses1, IFNULL(c.losses,0) as losses2,
												IFNULL(b.ties,0) as ties1, IFNULL(c.ties,0) as ties2,
												
												IFNULL(lyb.pct,0) as lastYear_pct1, IFNULL(lyc.pct,0) as lastYear_pct2,
												IFNULL(lyb.wins,0) as lastYear_wins1, IFNULL(lyc.wins,0) as lastYear_wins2,
												IFNULL(lyb.losses,0) as lastYear_losses1, IFNULL(lyc.losses,0) as lastYear_losses2,
												IFNULL(lyb.ties,0) as lastYear_ties1, IFNULL(lyc.ties,0) as lastYear_ties2,
												a.teamId, a.opponentId
									FROM fof_teamschedule a
									JOIN fof_gameinfo d
									  ON a.year = d.curYear
									 AND a.week = d.week
						 LEFT JOIN fof_standings b
									  ON a.teamId = b.teamId
									 AND d.curYear = b.year
						 LEFT JOIN fof_standings c
									  ON a.opponentId = c.teamId
									 AND d.curYear = c.year
						 LEFT JOIN fof_standings lyb
							      ON lyb.teamId = a.teamId
									 AND lyb.year = d.curYear - 1
						 LEFT JOIN fof_standings lyc
										ON lyc.teamId = a.opponentId
									 AND lyc.year = d.curYear - 1
						";
					
					if($this->lge->getCurWeek() == WEEK_ULTIMATE_BOWL)
					{				
						$numGamesOTW = 1;
					}
					else	
					{
						$sql = $sql . " WHERE a.away = 0";				
						$numGamesOTW = 4;
					}
				
			}
				
			$qGames = $this->dataMgr->runQuery($sql);
						
			//we've got the games.  Compute the game awesomeness for each one using winning pct
			$aGameRank = array();
			while($row = mysql_fetch_assoc($qGames))
			{
				//for week1 thru week8, use last year's numbers in the formula
				$thisYearWeight = 1;
				$lastYearWeight = 0;
				$weeksConsideredLastYear = 8;
				$finalWeekConsideredLastYear = WEEK_REGSEASON_START + $weeksConsideredLastYear;
				
				if($this->lge->getCurWeek() >= WEEK_REGSEASON_START && $this->lge->getCurWeek() <= $finalWeekConsideredLastYear){
					
					$thisYearWeight = ($this->lge->getCurWeek() - WEEK_REGSEASON_START) / $weeksConsideredLastYear;
					
					$lastYearWeight = 1 - $thisYearWeight;
				}
				
				$pct1 = $thisYearWeight * $row["pct1"] + $lastYearWeight * $row["lastYear_pct1"];
				$pct2 = $thisYearWeight * $row["pct2"] + $lastYearWeight * $row["lastYear_pct2"];
				$wins1 = $thisYearWeight * $row["wins1"] + $lastYearWeight * $row["lastYear_wins1"];
				$wins2 = $thisYearWeight * $row["wins2"] + $lastYearWeight * $row["lastYear_wins2"];
				$losses1 = $thisYearWeight * $row["losses1"] + $lastYearWeight * $row["lastYear_losses1"];
				$losses2 = $thisYearWeight * $row["losses2"] + $lastYearWeight * $row["lastYear_losses2"];
				$ties1 = $thisYearWeight * $row["ties1"] + $lastYearWeight * $row["lastYear_ties1"];
				$ties2 = $thisYearWeight * $row["ties2"] + $lastYearWeight * $row["lastYear_ties2"];
				
				$w1 = $wins1 + $losses1 + $ties1;
				$w2 = $wins2 + $losses2 + $ties2;
				
				$aGameRank[$row["ID"]] = advancedComputeGameAwesomeness($pct1,$w1,$pct2,$w2);
				
				//echo($row["teamId"] . "@" . $row["opponentId"]. "; Awesome: " . $aGameRank[$row["ID"]] . "<br/>");
			}
			
			if(count($aGameRank))
			{
				//sort it
				arsort($aGameRank);
				//get array of keys
				
				for($i=0; $i<$numGamesOTW && $i < count($aGameRank); $i++)
				{
					//send the first one (for preseason only) into game of the week
					$this->insertGOTW($this->lge->getCurWeek(), $this->lge->getCurYear(), $i, key($aGameRank));
					next($aGameRank);
				}
			}
			
			if($this->bDebug)
				echo $this->numGamesCreated . " games created!<br/>";
		}
		
		
		private function insertGOTW($w,$y,$r,$id)
		{
			$dm = createNewDataManager();
			$sql = "INSERT INTO fof_gotw (year, week, rank, teamSchedule_id, gameResults_id)
							 		 VALUES (" . $y . "," . $w . "," . $r . "," . $id . "," . "null)";
			
			if($dm->runQuery($sql))
				$this->numGamesCreated += 1;
		}
		
		public function __destruct()
		{}
		
	}
?>