<?php


include_once "../config.php";
include_once(APP_ROOT . '/func/leagueUtils.php');


$lge = getLeague();
$dbm = getDBConnection();

$year = $lge->getCurYear();

$sql = "DELETE 
			 FROM fof_playergamestats_by_year
			WHERE Year = $year";
$sql = "DELETE 
			 FROM fof_playergamestats_by_year";

			
$dbm->runQuery($sql);

$sql = "
INSERT INTO fof_playergamestats_by_year
(SELECT 0,
	playerID,
	YEAR,
	1,
	0,
	0,
	
	SUM(gamePlayed),
	SUM(gameStarted),
	SUM(passAttempts),
	SUM(passCompletions),
	SUM(passYards),
	MAX(longestPass),
	SUM(tdpasses),
	SUM(intthrown),
	SUM(timessacked),
	SUM(sackedyards),
	SUM(rushattempts),
	SUM(rushingyards),
	MAX(longestrun),
	SUM(rushtd),
	SUM(catches),
	SUM(receivingyards),
	MAX(longestreception),
	SUM(receivingtds),
	SUM(passtargets),
	SUM(yardsaftercatch),
	SUM(passdrops),
	SUM(puntreturns),
	SUM(puntreturnyards),
	SUM(puntreturntds),
	SUM(kickreturns),
	SUM(kickreturnyards),
	SUM(kickreturntds),
	SUM(fumbles),
	SUM(fumblerecoveries),
	SUM(forcedfumbles),
	SUM(misctd),
	SUM(keyrunblock),
	SUM(keyrunblockopportunites),
	SUM(sacksallowed),
	SUM(tackles),
	SUM(assists),
	SUM(sacks),
	SUM(ints),
	SUM(intreturnyards),
	SUM(intreturntds),
	SUM(passesdefended),
	SUM(passesblocked),
	SUM(qbhurries),
	SUM(passescaught),
	SUM(passplays),
	SUM(runplays),
	SUM(fgmade),
	SUM(fgattempted),
	MAX(fglong),
	SUM(PAT),
	SUM(PATAttempted),
	SUM(punts),
	SUM(puntyards),
	MAX(puntlong),
	SUM(puntin20),
	SUM(points),
	0,
	SUM(thirddownrushes),
	SUM(thirddownrushconversions),
	SUM(thirddownpassattempts),
	SUM(thirddownpasscompletions),
	SUM(thirddownpassconversions),
	SUM(thirddownreceivingtargets),
	SUM(thirddownreceivingcatches),
	SUM(thirddownreceivingconversions),
	SUM(firstdownrushes),
	SUM(firstdownpasses),
	SUM(firstdowncatches),
	SUM(fg40plusattempts),
	SUM(fg40plusmade),
	SUM(fg50plusattempts),
	SUM(fg50plusmade),
	SUM(puntnetyards),
	SUM(specialteamstackles),
	SUM(timesknockeddown),
	SUM(redzonerushes),
	SUM(redzonerushingyards),
	SUM(redzonepassattempts),
	SUM(redzonepasscompletions),
	SUM(redzonepassingyards),
	SUM(redzonereceivingtargets),
	SUM(redzonereceivingcatches),
	SUM(redzonereceivingyards),
	SUM(totaltds),
	SUM(twopointconversions),
	SUM(pancakeblocks),
	SUM(qbknockdowns),
	SUM(specialteamsplays),
	SUM(rushinggamesover100yards),
	SUM(receivinggamesover100yards),
	SUM(passinggamesover300yards),
	SUM(runsof10yardsplus),
	SUM(catchesof20yardsplus),
	SUM(throwsof20yardsplus),
	SUM(allpurposeyards),
	SUM(yardsfromscrimmage)
	FROM fof_playergamestats
	WHERE WEEK > 22
	AND Year = $year
	GROUP BY playerid, YEAR
	";

//$dbm->runQuery($sql);


?>