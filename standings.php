<?php
  
	include_once('config.php');
	include_once(OBJECT_ROOT . '/League.php');
	include_once(OBJECT_ROOT . '/Standings.php');
	
	include_once (UI_ROOT . '/basicLeaguePage.php');
	
	$ufl = getLeague();
	$year = $ufl->getCurYear();
	$week = $ufl->getCurWeek();
	
	//check to see if another year was requested.  Make sure it's a numeric value and that it's a valid year
	if(isset($_GET['year']) AND $ufl->validYear($_GET['year']))
		$year = $_GET['year']; 
	if(isset($_GET['week']) AND $ufl->validWeek($_GET['week']))
		$week = $_GET['week']; 
	elseif($year != $ufl->getCurYear())
		$week = WEEK_REGSEASON_START;
		  
	
	ob_start();

	$header_inc_year_selector = true;
	
	echo "<div class='section center'><h3>UFL Standings - ".$year."</h3><hr class='sectionUnderline'/></div>";
					
	
	$uflStandings = new Standings($ufl,$year,$week);
	
	$uflStandings->getStandings();

	$uflStandings->printStandings();	

	$page = new BasicLeaguePage();
	$page->setFlags(FLAG_INC_YEAR_SELECTOR);
	$page->pagePrint(ob_get_clean());
?>