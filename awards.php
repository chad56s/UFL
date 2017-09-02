<?php
		include_once ('config.php');
		include_once('header.php');
		
		$dataMgr = createNewDataManager();
		
		$mode = 'champ';
		if(isset($_GET['mode']))
			$mode = $_GET['mode'];
		
		echo "<a href='" . $_SERVER['PHP_SELF'] . "?mode=champ'>Champion</a>&nbsp;|&nbsp;";
		echo "<a href='" . $_SERVER['PHP_SELF'] . "?mode=weekly'>Weekly and Season Awards</a>&nbsp;|&nbsp;";
		echo "<a href='" . $_SERVER['PHP_SELF'] . "?mode=allpro'>All-Pro Team</a>&nbsp;|&nbsp;";
		echo "<a href='" . $_SERVER['PHP_SELF'] . "?mode=allrookie'>All-Rookie Team</a>";
		
		switch ($mode){
			
			case 'champ':

				$sql = "SELECT * FROM fof_gameresults WHERE Week = " . WEEK_ULTIMATE_BOWL . " AND Year=" . $y;
				$qResults = $dataMgr->runQuery($sql);
				
				if(mysql_num_rows($qResults)){
					$row = mysql_fetch_object($qResults);
					
					if($row->AwayScore > $row->HomeScore)
						$champ = Team::fromId($row->AwayTeam);
					else
						$champ = Team::fromId($row->HomeTeam);
						
					echo "<h1>UFL " . $y . " Champions</h1>";
					echo "<img src='" . $champ->getHelmetImg() . "'>";
					
				}
				break;
			
			case 'allpro':
				include('stelmack/SeasonAllProTeam.html');
				break;

			case 'allrookie':
				include('stelmack/SeasonAllRookieTeam.html');
				break;
				
			case 'weekly':
				include('stelmack/SeasonAwards.html');
				break;

			default:
				echo 'Invalid Mode: ' . $mode;
		}

	include('footer.php');

?>