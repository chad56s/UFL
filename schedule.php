<?php
  
	include_once ('config.php');
	include_once(OBJECT_ROOT . '/League.php');
	include_once(OBJECT_ROOT . '/Schedule.php');
		
	include_once (UI_ROOT . '/basicLeaguePage.php');

	$ufl = getLeague();
	
	$curYear = $ufl->getCurYear();
	$curWeek = $ufl->getCurWeek();
	
	$year = $curYear;
	$week = $curWeek;
	$teamId = -1;
	
	//check to see if another year was requested.  Make sure it's a numeric value and that it's a valid year
	if(isset($_GET['year']) AND $ufl->validYear($_GET['year']))
		$year = $_GET['year']; 

	//check to see if another week was requested.  Make sure it's a numeric value and that it's a valid week
	if(isset($_GET['week']) AND $ufl->validWeek($_GET['week']))
		$week = $_GET['week']; 
		
	if(isset($_GET['teamId']) AND $ufl->validTeam($_GET['teamId']))
		$teamId = $_GET['teamId']; 
	
	//don't show schedules past the ultimate bowl week
	$week = min($week,WEEK_ULTIMATE_BOWL);
	
	ob_start();

	$uflSchedule = NULL;
	if($teamId != -1)	
	{
		echo "<div class='section center'><h3>Schedule -".$ufl->getTeamCity($teamId)." ".$ufl->getTeamNickname($teamId)." ".$year."</h3><hr class='sectionUnderline'/></div>";
		echo "<div class='sectionNav center'>";
		
		if(!weekIsPreseason($week))
			echo "<a href='schedule.php?week=".WEEK_PRESEASON_START."&year=".$year."&teamId=".$teamId."'>";
		echo "Preseason";
		if(!weekIsPreseason($week))
			echo "</a>";
			
		echo " | ";
		
		if(!weekIsRegularSeason($week))	
			echo "<a href='schedule.php?week=".WEEK_REGSEASON_START."&year=".$year."&teamId=".$teamId."'>";
		echo "Regular Season";
		if(!weekIsRegularSeason($week))
			echo "</a>";
			
		if($year != $curYear OR weekIsPostseason($curWeek))
		{
			echo " | ";
			if(!weekIsPostseason($week))
				echo "<a href='schedule.php?week=".WEEK_WILDCARD."&year=".$year."&teamId=".$teamId."'>";
			echo "Postseason";
			if(!weekIsPostseason($week))
				echo "</a>";
			
		}
			
		echo "</div><br/>";
		
		if(weekIsPreseason($week))
			$uflSchedule = new SchedulePreseasonTeam($ufl,$teamId,$year);	
		elseif(weekIsPostseason($week))
			$uflSchedule = new SchedulePostseasonTeam($ufl,$teamId,$year);
		else
			$uflSchedule = new ScheduleTeam($ufl,$teamId,$year);
	}
	else
	{
		$uflSchedule = new ScheduleWeek($ufl,$week,$year);
		echo "<div class='section center'><h3>Schedule - ".getWeekString($week,true)." ".$year."</h3><hr class='sectionUnderline'/></div>";
		echo "<div class='sectionNav center'>";
		
		$maxWeek2Show = WEEK_ULTIMATE_BOWL;
		if($year == $curYear && !weekIsPostseason($curWeek) && $curWeek <= WEEK_ULTIMATE_BOWL)//had to add the ultimate bowl check here for the week after the bowl game is played
			$maxWeek2Show = max($curWeek,WEEK_REGSEASON_END); //don't show playoffs for current year, if we haven't made it there yet.
		
		echo "<table>";
		for($i=1; $i<=$maxWeek2Show; $i++)
		{
			if($i==1)
				echo "<tr><td align='right'>Preseason: </td><td>";
			else if($i==WEEK_REGSEASON_START)
				echo "</td></tr><tr><td align='right'>Week: </td><td>";
			else if($i==WEEK_WILDCARD)
				echo "</td></tr><tr><td align='right'>Postseason: </td><td>";
			
			if($i == $curWeek)
				$cls = "bold";
			else	
				$cls = "";
				
			if($i != $week)
				echo "<a class='".$cls."' href='schedule.php?week=".$i."&year=".$year."'>";
			echo getWeekString($i);
			if($i != $week)
				echo "</a>";
			echo "&nbsp;";
			
		}
		echo "</td></tr></table>";
		
		echo "</div><br/>";
		
	}

	if(!$uflSchedule)
		echo "can't create schedule";
	else
	{
		$uflSchedule->getSchedule();
		$uflSchedule->printSchedule();
		
	}

	$page = new BasicLeaguePage();
	$page->setFlags(FLAG_INC_TEAM_SELECTOR | FLAG_INC_YEAR_SELECTOR);
	$page->setYear($year);
	$page->setTeamId($teamId);

	$page->pagePrint(ob_get_clean());

?>

</div>