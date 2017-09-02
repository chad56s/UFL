<?php
  
	include_once('header.php');
	include_once('obj\League.php');
	include_once('obj/Schedule.php');
			
	$ufl = new League();
	
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
	
	$uflSchedule = new ScheduleTeam($ufl,0,$year);
	echo "<table><tr>";
	for($i=0;$i<32;$i++)
	{
		if(($i+3)%3 == 0)
			echo "</tr><tr>";
		echo "<td>";
		$uflSchedule->setTeam($i);
		$uflSchedule->getSchedule();
		$uflSchedule->printSchedule();
		echo "</td>";
		
	}
	echo "</tr></table>";
	
	include('footer.php');
?>