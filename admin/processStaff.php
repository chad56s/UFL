<?php
    include_once('../config.php');
		include_once(DBMGR_ROOT . '/DataMgr.php');
		include_once(OBJECT_ROOT . '/League.php');
		
		$dataMgr = createNewDataManager();
		
		$lge = new League();
		$season = $lge->getCurYear();
		
		//process all the scouts
		$sql = "SELECT * FROM fof_scouts_raw";
		$qRawScouts = $dataMgr->runQuery($sql);
		
		//for each scout in the raw table, look them up in the
		//scouts table.  If they're not found, insert them.  If they
		//are found, update them.
		//Then delete any transactions in the fof_transactions_scouts
		//table for this scout for this year.  Then insert this transaction
		while($scoutRaw = mysql_fetch_object($qRawScouts)){
			//all we have to go by is name...
			$sql = "SELECT ID
							  FROM fof_scouts
							 WHERE name = '" . addslashes($scoutRaw->Name) . "'";

			$qScoutID = $dataMgr->runQuery($sql);
			
			if(mysql_num_rows($qScoutID)){
				$r = mysql_fetch_array($qScoutID);
				$scoutID = $r[0];
				$sql = sprintf("
						UPDATE fof_scouts
						   SET Age = %d,
							 		 Playoffs = %d, BowlWins = %d,
									 QB = %d, RB = %d, WR = %d, OL = %d,
									 K = %d, DL = %d, LB = %d, DB = %d,
									 Youth = %d
						 WHERE ID = %d
				", $scoutRaw->Age, $scoutRaw->Playoffs, $scoutRaw->BowlWins, 
				   unmapAbility($scoutRaw->QB), unmapAbility($scoutRaw->RB), unmapAbility($scoutRaw->WR), 
					 unmapAbility($scoutRaw->OL), unmapAbility($scoutRaw->K), unmapAbility($scoutRaw->DL), 
					 unmapAbility($scoutRaw->LB), unmapAbility($scoutRaw->DB), unmapAbility($scoutRaw->Youth),
					 $scoutID);
					 
				$dataMgr->runQuery($sql);
				echo "Updated scout $scoutID<br/>";
			}
			else
			{
				$sql = sprintf("
					INSERT INTO fof_scouts (
							Name, Age, Playoffs, BowlWins,
							QB, RB, WR, OL,
							K, DL, LB, DB, Youth )
					VALUES ('%s', %d, %d, %d,
							%d, %d, %d, %d,
							%d, %d, %d, %d, %d)",
					
					addslashes($scoutRaw->Name), $scoutRaw->Age, $scoutRaw->Playoffs, $scoutRaw->BowlWins,
					unmapAbility($scoutRaw->QB), unmapAbility($scoutRaw->RB), unmapAbility($scoutRaw->WR),
					unmapAbility($scoutRaw->OL), unmapAbility($scoutRaw->K), unmapAbility($scoutRaw->DL),
					unmapAbility($scoutRaw->LB), unmapAbility($scoutRaw->DB), unmapAbility($scoutRaw->Youth)
				);
				$dataMgr->runQuery($sql);
				$scoutID = mysql_insert_id();	
				echo "Inserted scout $scoutID<br/>";
			}
			
			//get team id
			$sql = "SELECT ID FROM fof_teams WHERE concat(cityName,concat(' ',nickname)) = '" . $scoutRaw->Team . "'";

			$qTeam = $dataMgr->runQuery($sql);
			
			if(mysql_num_rows($qTeam)){
				$r = mysql_fetch_array($qTeam);
				$teamID = $r[0];
			}
			else
				$teamID = -1;
			
			if($teamID != -1){
				//get rid of any previous "transactions" involving this scout for this year
				$sql = sprintf("DELETE FROM fof_transactions_scouts WHERE ScoutID = %d AND Season = %d",$scoutID,$season);
				$dataMgr->runQuery($sql);
				
				//insert the newest transaction involving this scout for this year
				$sql = sprintf("INSERT INTO fof_transactions_scouts (ScoutID, Season, TeamID, Price, Years)
												VALUES(%d,%d,%d,%d,%d)", $scoutID, $season, $teamID, $scoutRaw->Price, $scoutRaw->Years);
				$dataMgr->runQuery($sql);
				echo "created transaction for scout $scoutID for {$scoutRaw->Years} years<br/>";
			}
			
		}//end processing scouts_raw
		
		//delete the scouts_raw table
		$sql = "DELETE FROM fof_scouts_raw";
		$dataMgr->runQuery($sql);
		
	
		//process all the coaches
		$sql = "SELECT * FROM fof_coaches_raw";
		$qRawCoaches = $dataMgr->runQuery($sql);
		
		//for each coach in the raw table, look them up in the
		//coaches table.  If they're not found, insert them.  If they
		//are found, update them.
		//Then delete any transactions in the fof_transactions_coaches
		//table for this coach for this year.  Then insert this transaction
		while($coachRaw = mysql_fetch_object($qRawCoaches)){
			//all we have to go by is name...can't go by age because it increments each year.
			$sql = "SELECT ID
							  FROM fof_coaches
							 WHERE name = '" . addslashes($coachRaw->Name) . "'";

			$qCoachID = $dataMgr->runQuery($sql);
			
			if(mysql_num_rows($qCoachID)){
				$r = mysql_fetch_array($qCoachID);
				$coachID = $r[0];
				$sql = sprintf("
						UPDATE fof_coaches
						   SET Age = %d, Exp = %d,
							 		 Playoffs = %d, BowlWins = %d,
									 QB = %d, RB = %d, WR = %d, OL = %d,
									 K = %d, DL = %d, LB = %d, DB = %d,
									 Youth = %d, Motiv = %d, Disc = %d,
									 Off = %d, Def = %d, Inj = %d
						 WHERE ID = %d
				", $coachRaw->Age, $coachRaw->Exp, $coachRaw->Playoffs, $coachRaw->BowlWins, 
				   unmapAbility($coachRaw->QB), unmapAbility($coachRaw->RB), unmapAbility($coachRaw->WR), 
					 unmapAbility($coachRaw->OL), unmapAbility($coachRaw->K), unmapAbility($coachRaw->DL), 
					 unmapAbility($coachRaw->LB), unmapAbility($coachRaw->DB), unmapAbility($coachRaw->Youth),
					 unmapAbility($coachRaw->Motiv), unmapAbility($coachRaw->Disc), unmapAbility($coachRaw->Off),
					 unmapAbility($coachRaw->Def), unmapAbility($coachRaw->Inj), $coachID);
	 
				$dataMgr->runQuery($sql);
				echo "updated coach $coachID<br/>";
			}
			else
			{
				$sql = sprintf("
					INSERT INTO fof_coaches (
							Name, Age, Exp, Playoffs, BowlWins,
							QB, RB, WR, OL,
							K, DL, LB, DB, Youth,
							Motiv, Disc, Off, Def, Inj )
					VALUES ('%s', %d, %d, %d, %d,
							%d, %d, %d, %d,
							%d, %d, %d, %d, %d,
							%d, %d, %d, %d, %d)",
					
					addslashes($coachRaw->Name), $coachRaw->Age, $coachRaw->Exp, $coachRaw->Playoffs, $coachRaw->BowlWins,
					unmapAbility($coachRaw->QB), unmapAbility($coachRaw->RB), unmapAbility($coachRaw->WR),
					unmapAbility($coachRaw->OL), unmapAbility($coachRaw->K), unmapAbility($coachRaw->DL),
					unmapAbility($coachRaw->LB), unmapAbility($coachRaw->DB), unmapAbility($coachRaw->Youth),
					unmapAbility($coachRaw->Motiv), unmapAbility($coachRaw->Disc), unmapAbility($coachRaw->Off),
					unmapAbility($coachRaw->Def), unmapAbility($coachRaw->Inj)
				);
				$dataMgr->runQuery($sql);
				$coachID = mysql_insert_id();		
				echo "inserted coach $coachID<br/>";
			}
			
			//get team id
			$sql = "SELECT ID FROM fof_teams WHERE concat(cityName,concat(' ',nickname)) = '" . $coachRaw->Team . "'";

			$qTeam = $dataMgr->runQuery($sql);
			
			if(mysql_num_rows($qTeam)){
				$r = mysql_fetch_array($qTeam);
				$teamID = $r[0];
			}
			else
				$teamID = -1;
			
			//-1 will usually indicate that the coach is available but unhired
			if($teamID != -1){
				//get rid of any previous "transactions" involving this coach for this year
				$sql = sprintf("DELETE FROM fof_transactions_coaches WHERE CoachID = %d AND Season = %d",$coachID,$season);
				$dataMgr->runQuery($sql);
				
				//insert the newest transaction involving this coach for this year
				$sql = sprintf("INSERT INTO fof_transactions_coaches (CoachID, Season, TeamID, Type, Price, Years)
												VALUES(%d,%d,%d,%d,%d,%d)", $coachID, $season, $teamID, unmapStaffPositionToTransactionType($coachRaw->Position),
												$coachRaw->Price, $coachRaw->Years);
				$dataMgr->runQuery($sql);
				echo "created transaction for coach $coachID for {$coachRaw->Years} years<br/>";
			}
			
		}//end processing coaches_raw
		
		//delete the coaches_raw table
		$sql = "DELETE FROM fof_coaches_raw";
		$dataMgr->runQuery($sql);
		
?>