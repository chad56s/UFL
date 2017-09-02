<?php

include_once ('mappings.php');

include_once(DBMGR_ROOT . '/DataMgr.php');
include_once(OBJECT_ROOT . '/League.php');

//WEEKS defined as after preseason has started
define("WEEK_PRESEASON_START", 1);
define("WEEK_PRESEASON_END", 5);
define("WEEK_REGSEASON_START", 6);
define("WEEK_REGSEASON_END", 22);
define("WEEK_WILDCARD", 23);
define("WEEK_DIVISIONAL", 24);
define("WEEK_CONFERENCE", 25);
define("WEEK_ULTIMATE_BOWL", 26);

//STAGE defined as StageIndex in fof_stagenames
define("STAGE_OFFSEASON", 0);
define("STAGE_PRESEASON_START", 26);
define("STAGE_PRESEASON_END", 30);
define("STAGE_REGSEASON_START", 31);
define("STAGE_REGSEASON_END", 47);
define("STAGE_WILDCARD", 48);
define("STAGE_DIVISIONAL", 49);
define("STAGE_CONFERENCE", 50);
define("STAGE_ULTIMATE_BOWL", 51);
define("STAGE_SEASON_END", 52);
//data utilities

function getLeague(){
	static $lge = NULL;
	
	if(!is_object($lge))
		$lge = new League();
	
	return $lge;
}

function getDBConnection(){
	static $db = NULL;
	
	if(!is_object($db))
		$db = createNewDataManager();
	
	return $db;
}


function createNewDataManager() {
	return new DataMgr($GLOBALS['_host'], $GLOBALS['_user'], $GLOBALS['_pass'], $GLOBALS['_db']);
}

function computeGameAwesomeness($pcta, $pctb) {
	//for now
	//game awesomeness is = (a.pct + b.pct - ABS(1.95 * stdDev(a.pct,b.pct))
	$avg = ($pcta + $pctb) / 2;
	$awesome = $pcta + $pctb - abs(1.85 * ($avg - $pcta));
	$awesome = number_format($awesome, 3);
	$awesome = $awesome + mt_rand(0, 1000) / 10000000;
	return $awesome;

}

//game awesomeness has improved!  It is now:
// lowest winning pct - abs(stdDev - .8/week)
//
// analysis: I admittedly don't understand this very well, but I'll give it a shot.
// Think of standard deviation between two winning percentages on a three dimensional graph.
// Z = 0 where x=y and then the graph slopes up linearly to 0.5 as (0,1) and (1,0).
// Okay, now if you take that graph and subtract some number from it (this is the .8/week part)
// and then take the absolute value of that, you get a graph that has some small, positive, constant value
// where x=y, z dips down to zero on either side of that line and then goes back up.
// Now, take the graph of min(pcta,pctb).  It's basically a straight line from 0,0 to 12,12 (in week 12)
// that slants down to zero along the x and y axes.
// Take that graph, subtract the first one, and you end up with what I was going for.  Winning percentages are good
// and standard deviations are important, and it's the mixture of the two that counts.

//TO CALL: $pcta = team a's win pct
//					$wa = number of games team a has played
//					$pctb = team b's win pct
//					$wb = number of games team b has played

function advancedComputeGameAwesomeness($pcta, $wa, $pctb, $wb) {

	$avg = ($pcta + $pctb) / 2;
	$min = min($pcta, $pctb);
	$stdDev = $avg - $min;
	//std dev = average - lower of the two (gives us positive value)
	$w = ($wa + $wb) / 2;
	$awesome = 0;

	//check for w = 0 so we don't get division by zero error.
	if ($w != 0) {
		$awesome = $min - abs($stdDev - 0.8 / $w);

		$awesome = round($awesome, 3);

		//To add some variety in, try dividing by 100000 or, dare I say it, even 10000
		//Check the spreadsheet to see how this will be affected.  10000 actually isn't bad.
		$awesome = $awesome + mt_rand(0, 1000) / 10000;
	}

	return $awesome;
}

//display utilities
function createRecordString($w, $l, $t) {
	if (!is_numeric($w))
		$w = 0;
	if (!is_numeric($l))
		$l = 0;

	$rec = $w . "-" . $l;

	if ($t != 0)
		$rec = $rec . "-" . $t;

	return $rec;

}

function getWeekString($w, $bFull = false) {
	$retVal = "";

	if ($bFull) {
		if ($w == WEEK_PRESEASON_START) {
			$retVal = "Hall of Fame Game";
			return $retVal;
		} else if (weekIsPreseason($w))//preseason
			$retVal = "Preseason - Week ";
		else if (weekIsRegularSeason($w))
			$retVal = "Week ";
		else if (weekIsPostseason && $w != WEEK_ULTIMATE_BOWL)
			$retVal = "Playoffs - ";
	}

	if ($w == WEEK_PRESEASON_START)
		$retVal = 'HOF';
	else if (weekIsPreseason($w))//preseason
		$retVal = $retVal . ($w - 1);
	else if (weekIsRegularSeason($w))
		$retVal = $retVal . ($w - WEEK_PRESEASON_END);
	else if ($w == WEEK_WILDCARD)
		$retVal = $retVal . "Wildcard";
	else if ($w == WEEK_DIVISIONAL)
		$retVal = $retVal . "Divisional";
	else if ($w == WEEK_CONFERENCE)
		$retVal = $retVal . "Conference Championship";
	else if ($w == WEEK_ULTIMATE_BOWL)
		$retVal = "Ultimate Bowl";
	else
		$retVal = "invalid week: " . $w;

	return $retVal;
}

//city filter.  Currently only removes the (OR) from Portland.  The responsibility
//for adding the nickname initial belongs to the team object.
function cityFormat($city) {
	//Get rid of the (OR) from Portland
	if (strtoupper($city) == 'PORTLAND (OR)')
		$city = 'Portland';
	else if (stristr($city, 'NEW YORK'))
		$city = 'New York';

	return $city;
}

//html utilities
function getGameFileName($type, $year, $week, $home, $away) {
	if ($week == WEEK_ULTIMATE_BOWL && $away > $home) {
		//this is just a theory, but since there's no official "away" team in the bowl,
		//I think the file is named with the lower id followed by the higher id...
		$temp = $away;
		$away = $home;
		$home = $temp;
	}

	$week = sprintf("%02d", $week - 1);
	$home = sprintf("%02d", $home);
	$away = sprintf("%02d", $away);

	//file will reside in solecismic root.  But we only want the filepath, not the weblink.
	$fileName = str_replace(WWW_ROOT, "", SOLECISMIC_ROOT) . "/" . $type . $year . $week . $away . $home . ".html";

	return $fileName;
}

function getGameFileLink($type, $year, $week, $home, $away) {
	$fileName = getGameFileName($type, $year, $week, $home, $away);
	if (file_exists(APP_ROOT . '/' . $fileName))
		return "<a href='" . WWW_ROOT . $fileName . "' target='new'>" . $type . "</a>";
	else
		return "";
}

function createBoxLink($year, $week, $home, $away) {
	return getGameFileLink('box', $year, $week, $home, $away);

}

function createLogLink($year, $week, $home, $away) {
	return getGameFileLink('log', $year, $week, $home, $away);
}

function createBoxAndLogLinks($year, $week, $home, $away) {
	$boxLink = createBoxLink($year, $week, $home, $away);
	$logLink = createLogLink($year, $week, $home, $away);

	if (strlen($boxLink) && strlen($logLink))
		return $boxLink . " | " . $logLink;
	else
		return "";
}

function createGameSummaryLinks($year, $week, $home, $away) {
	$bl = createBoxAndLogLinks($year, $week, $home, $away);
	$logFile = getGamefileName('log', $year, $week, $home, $away);
	$gc = "";
	if (file_exists(APP_ROOT . '/' . $logFile)) {
		preg_match("/([0-9])+/", basename($logFile, ".html"), $gameId);
		$gc = "<a href='" . WWW_ROOT . "/gamecenter.php?game_id=" . $gameId[0] . "'><img  style='vertical-align:text-bottom;' src='" . IMAGE_ROOT . "/gc/gameCenter.gif'></a>";
	}

	$retVal = $bl;
	if (strlen($gc))
		$retVal = $retVal . " | " . $gc;
	return $retVal;
}

//function: getNewsItem
// gets the news text and then wraps it in a news div for possible editing (if logged in)
function getNewsItem($year, $week, $type, $id) {
	$news = getNews($year, $week, $type, $id);

	if (strlen($news) || isset($_SESSION['username'])) {
		$divId = sprintf("%s_%d_%d_%d", $type, $year, $week, $id);
		$news = "<div class='news' id='" . $divId . "'>" . $news . "</div>";
	}
	return $news;
}

//function: getNews
// reads the news item and returns the text
function getNews($year, $week, $type, $id) {
	$news = "";
	$fileName = sprintf("%s/news/%d/%d/%s%d", APP_ROOT, $year, $week, $type, $id);

	//LEAVE IN FOR DEBUGGING
	//echo $fileName;

	if (file_exists($fileName . ".xml")) {
		//TODO: XML news parser

	} elseif (file_exists($fileName . ".txt")) {
		$fileName = $fileName . ".txt";

		if (filesize($fileName) > 0) {
			$fh = fopen($fileName, 'r');
			$news = fread($fh, filesize($fileName));
			fclose($fh);
		}

	}

	return $news;
}

function sortByCity($t1, $t2) {
	return $t1 -> getCity() > $t2 -> getCity();
}

function writeNews($year, $week, $type, $id, $news) {
	$fileName = sprintf("%s/news/%d/%d/%s%d", APP_ROOT, $year, $week, $type, $id);
	$fileName = $fileName . ".txt";

	$dirName = dirname($fileName);

	if (!is_dir($dirName))
		mkdir($dirName);

	$fh = fopen($fileName, 'w');
	fwrite($fh, $news, strlen($news));
	fclose($fh);
}

//time of season utilities
function weekIsPreseason($week) {
	return $week >= WEEK_PRESEASON_START && $week <= WEEK_PRESEASON_END;
}

function weekIsRegularSeason($week) {
	return $week >= WEEK_REGSEASON_START && $week <= WEEK_REGSEASON_END;
}

function weekIsPostseason($week) {
	return $week >= WEEK_WILDCARD;
	//the week after the ultimate bowl should also be counted as post-season.
	//the next line down is what this function used to do.
	return $week >= WEEK_WILDCARD && $week <= WEEK_ULTIMATE_BOWL;
}

//debug and admin

function debugOut($output) {
	if (false)
		echo $output . "<br/>";
}

//admin access
function checkAdminLevel($lvl) {
	//returns true if admin level is equal to or lower than $lvl (lower admin lvl is better)
	return (isset($_SESSION['adminLevel']) && $_SESSION['adminLevel'] <= $lvl);
}
?>