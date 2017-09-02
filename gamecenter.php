<?php
	include_once("config.php");
	include_once(OBJECT_ROOT . '/LogFileParser.php');
	include_once(OBJECT_ROOT . '/Schedule.php');
	include_once(OBJECT_ROOT . '/League.php');
	
	include_once(UI_ROOT . '/basicLeaguePage.php');

	
	//Initializations
	$dbMgr = getDBConnection();
	$lge = getLeague();
	$year = $lge->getCurYear();
	$week = $lge->getCurWeek();
	$away = -1;
	$home = -1;
	$gameId = 0;
	$lastId = 0;
	
	//if gamecenter is provided (i.e. from clicking 'other' games in gamecenter)
	if(isset($_GET['game_id']) && is_numeric($_GET['game_id']) && strlen($_GET['game_id']) == 10){
		$gameId = $_GET['game_id']; 
		$year = intval(substr($gameId,0,4));
		$week = intval(substr($gameId,4,2))+1;
		$away = intval(substr($gameId,6,2));
		$home = intval(substr($gameId,8,2));
		if(isset($_GET['last_id']) && is_numeric($_GET['last_id']))
			$lastId = $_GET['last_id']; 
	}
	else{
		if(isset($_GET['week']) && $lge->validWeek($_GET['week']))
			$week = $_GET['week']; 
		if(isset($_GET['year']) && $lge->validYear($_GET['year']))
			$year = $_GET['year']; 
		if(isset($_GET['home']) && $lge->validTeam($_GET['home']))
			$home = $_GET['home']; 
		if(isset($_GET['away']) && $lge->validTeam($_GET['away']))
			$away = $_GET['away']; 
	}
	//check to make sure the requested game exists and has been played
	$sql = "
		SELECT id, temperature, precip, wind
		  FROM fof_gameresults
		 WHERE awayTeam = " . $away . "
		   AND homeTeam = " . $home . "
		   AND year = " . $year . "
		   AND week = " . $week . "
	";
	$qGame = $dbMgr->runQuery($sql);
	if(mysql_num_rows($qGame) == 0){
		//game doesn't exist.  Look for the highest ranking gotw for this week
		$sql = "
			SELECT a.homeTeam, a.awayTeam, temperature, precip, wind
			  FROM fof_gameresults a
			  JOIN fof_gotw b
				  ON a.id = b.gameResults_id
			 WHERE b.week = " . $week . "
			   AND b.year = " . $year . "
	  ORDER BY b.rank
		";
		$qGame = $dbMgr->runQuery($sql);
		if(mysql_num_rows($qGame) > 0){
			$game = mysql_fetch_object($qGame);
			$home = $game->homeTeam;
			$away = $game->awayTeam;
			$temp = $game->temperature;
			$wind = $game->wind;
			$precip = $game->precip;
		}
	}
	else{
			$game = mysql_fetch_object($qGame);
			$temp = $game->temperature;
			$wind = $game->wind;
			$precip = $game->precip;
	}
	if($away != -1 && $home != -1){
		//now we have a valid year, week and teams
		//get the entire schedule for the week
		$sched = new ScheduleWeek($lge,$week,$year);
		$sched->getSchedule();
		$qSched = $sched->getQuery();
		//set to the beginning of the query just in case
		mysql_data_seek($qSched,0);
		//try to parse each game if it hasn't been already
		while($game = mysql_fetch_object($qSched)){
			$parser = new LogFileParser($year,$week,$game->awayId,$game->homeId);
			if($parser->canParse()){
				if(!$parser->parse()){
					die("Error Can't parse the game for " . $year.$week.$away.$home);
				}
			}
		}
		$gameId = sprintf("%d%02d%02d%02d",$year,$week-1,$away,$home);
		$awayTeam =& Team::fromId($away);
		$homeTeam =& Team::fromId($home);
		//We might need to parse the weather if the database hasn't been updated yet (i.e. the week is still in progress)		
		if(!isset($temp)){
			$logFile = APP_ROOT . '/' . getGameFileName('log',$year, $week, $home, $away);
			$source = file_get_contents($logFile);
			preg_match_all("/weather: ([0-9]+) degrees, ([A-Za-z ]+), ([0-9]*) ?(mph|calm)/", $source, $conditions);
			$temp = $conditions[1][0];
			if(is_numeric($conditions[3][0]))
				$wind = $conditions[3][0];
			else
				$wind = 0;
			switch($conditions[2][0]){
				case 'fair':
					$precip = 0;
					break;
				case 'rain':
					$precip = 1;
					break;
				case 'stormy':
					$precip = 2;
					break;
				case 'snow':
					$precip = 3;
					break;
				default:
					$precip = 0;
			}
		}
		
		if($week > WEEK_REGSEASON_END)
			$centerFieldImg = $lge->getCenterfieldImg(true, $year);
		else
			$centerFieldImg = $homeTeam->getCenterfieldImg();

ob_start();
			
		echo '
			<script type="text/javascript" src="js/swfobject.js"></script>
			<div class="gamecenter">
			  <div id="gamecenter">&nbsp;</div>
			  <script type="text/javascript">
			    var so = new SWFObject("flash/fof_gamecenter.swf", "single", "690", "690", "9", "");
			    so.addVariable("endzone_0", "' . $homeTeam->getEndzoneImg() .'");
			    so.addVariable("endzone_1", "' . $awayTeam->getEndzoneImg() . '");
			    so.addVariable("centerfield", "' . $centerFieldImg . '");
			    so.addVariable("team_0_color_1", "0x' . $homeTeam->getProp("color2") . '");
			    so.addVariable("team_0_color_2", "0x' . $homeTeam->getProp("color1"). '");
			    so.addVariable("team_1_color_1", "0x' . $awayTeam->getProp("color2"). '");
			    so.addVariable("team_1_color_2", "0x' . $awayTeam->getProp("color1"). '");
			    so.addVariable("game_id", "' . $gameId . '");
			    so.addVariable("temp", "' . $temp . '");
			    so.addVariable("wind", "' . $wind . '");
			    so.addVariable("weather", "' . $precip . '");
			    so.addVariable("last_id", "' . $lastId . '");
			    so.addVariable("year", "' . $year . '");
			    so.addVariable("week", "' . $week . '");
			    so.addVariable("game", "' . $gameId . '");
			    so.write("gamecenter");
			  </script>
			</div>
		';
	}
	else{
		echo "<p>Error!  Probably these games haven't been played yet.</p>";
	}

	$page = new BasicLeaguePage();
	$page->pagePrint(ob_get_clean());

?>