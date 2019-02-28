<?php
	
	include_once ('config.php');
	
	include_once (OBJECT_ROOT . '/League.php');
	include_once (APP_ROOT . '/func/leagueUtils.php');

	include_once (OBJECT_ROOT . '/GOTW.php');
	include_once (UI_ROOT . '/basicLeaguePage.php');
	
	
	ob_start();
		
	$mod = 0;
	
	//show the news from last week on monday and tuesday
	if(date('N') < 3 && $lge->validWeek($w-1))
	{
		//we don't actually know if the database has been updated.  We can 
		//sort of tell by checking whether the current week's games of the week have
		//been computed.  Get a new game of the week manager and check to see if it's
		//up to date.
		$gotwMgr = new GOTWManager();
		
		//the game of the week manager is not up to date.  Show last week's GOTWS
		//instead of creating new ones.  New ones will be created Wednesday
		if(!$gotwMgr->checkUpToDate())
		{
			$w = $w - 1;
			//set modifier to show last week's dates
			$mod = -7;
		}
	}
	
	if(weekIsPreseason($w))
	{
		echo "<h1>GAME OF THE WEEK</h1>";
		$gotw = new GameOfTheWeek;
		$gotw->showGameInfo($w,$y);
	}
	elseif(weekIsRegularSeason($w) || (weekIsPostseason($w) && $w <= WEEK_ULTIMATE_BOWL))	
	{
		$gotw = new GamesOfTheWeek;
		$today = new DateTime();
		$todayDay = date('N');
		
		function getNextDay($ts, $day,$mod)
		{
			/*$today = new DateTime();
			$interval = 'P'.($day - date('N')).'D';
			$today = $today->add($interval);
			return $today->format('D, M jS');*/
			$intvl = '+'.abs($day - date('N',$ts) + $mod).' days';
			return date('D, M jS', strtotime($intvl,$ts));
		}
		
		function getLastDay($ts, $day,$mod)
		{
			/*$today = new DateTime();
			$interval = 'P'.(date('N') - $day).'D';
			$today = $today->add($interval);
			return $today->format('D, M jS');*/
			$intvl = '-'.abs($day - date('N',$ts) + $mod).' days';
			return date('D, M jS', strtotime($intvl,$ts));
			
		}
		function getDay($day,$mod)
		{
			$ts = strtotime('+2 days');
			
			if(date('N',$ts) < 4)
			{
				if(date('N',$ts) > $day)
					$mod = $mod + 7;
					
				return getNextDay($ts, $day,$mod);
			}
			elseif ($day < date('N',$ts))
				return getNextDay($ts, $day, $mod);
			else	
				return getLastDay($ts, $day,$mod);
		}
		
		
		$thurs = getDay(4,$mod);
		$sat = getDay(6,$mod);
		$sun = getDay(7,$mod);
		$mon = getDay(1,$mod);
		
		
		$numGames = $gotw->getNumGames($w,$y);
		
			echo "<table class='uflTable center' style='width:95%;'>";
			echo "<tr><th colspan=99 class='lvl1'>UFL Games of the Week</th></tr>";
			echo "<tr><th colspan=99 class='lvl2'>".getWeekString($w,true)." " . $y . "</th></tr>";

		if($numGames == 4)
		{
		echo 	"
						<tr><th>Thursday</th><th>Saturday</th></tr>
						<tr>
							<td class='uflTable' style='width:50%;'>";
							$gotw->showGameInfo($w,$y);
							echo "</td>
							<td class='uflTable' style='width:50%;'>";
							$gotw->showGameInfo($w,$y);
							echo "</td>
						</tr>";
						
			echo "<tr><th colspan=99 class='lvl1'>&nbsp;</th></tr>";
		}
		
			
			$size = 'medium';  //default to medium size previews
			$g1 = 0;					 //game rank for left side game
			$g2 = 1;					 //game rank for right side game

			if($numGames == 4)
				echo "<tr><th>Sunday</th><th>Monday</th></tr>";
			elseif($numGames == 2){
				echo "<tr><th>Saturday</th><th>Sunday</th></tr>";	
				//reverse order of game ranks for champ week (best on Sunday)
				$g1 = 1;
				$g2 = 0;
			}	
			elseif($numGames == 1){
				echo "<tr><th>Sunday</th></tr>";
				$size = 'large';  //superbowl!  show large preview
			}
			
			echo "<tr>			
							<td class='uflTable' style='width:50%;'>";	
								$gotw->showGameInfo($w,$y,$size);
				echo "</td>";
				
			if($numGames > 1){
				echo "<td class='uflTable' style='width:50%;'>";
								$gotw->showGameInfo($w,$y,$size);
				echo "</td>";
			}
			
			echo "</tr>
			</table>";
	}


	
	//Other league news.  For now, I don't care about saving this from week to week, so I'm just using
	//2008, week 1, etc., etc.  Who cares.
	//TODO: change getNewsItem to allow another form of news.  One that doesn't require year, week, etc.
	//One that can just be identified by an ID or string
	echo "<br/>";
	echo "<table class='uflTable center' style='width:50%'>";
	echo "<tr><td>";
	echo getNewsItem(2010,1,'fun',0);
	echo "</td></tr></table>";


	$gotw_page = new BasicLeaguePage();
	$gotw_page->pagePrint(ob_get_clean());

	include('footer.php');

?>