<?php

include_once(DBMGR_ROOT . '/DataMgr.php');
include_once(OBJECT_ROOT . '/Team.php');

// Play types
define (kPenalty, 2);
define (kKick, 3);
define (kFieldGoal, 4);
define (kPAT, 4);

// The formations are set up as bit-wise values so we can stuff it into an int
// Defensive formations
$defensive_formations = array('in a 43'=>0,
			    'in a 34'=>32,
			    'goal-line personnel'=>8,
			    'nickel personnel'=>16,
			    'dime personnel'=>24,
			    '1-deep, man-to-man coverage'=>0,
			    '1-deep, bump-and-run coverage'=>1,
			    '2-deep man-to-man coverage'=>2,
			    '2-deep bump-and-run coverage'=>3,
			    '3-deep zone coverage'=>4,
			    '4-deep zone coverage'=>5,
			    'strong-side man, otherwise cover-7 zone coverage'=>6,
			    'weak-side man, otherwise cover-7 zone coverage'=>7);

// Offensive formations
$offensive_formations = array('I formation, [^w]'=>0,
			      'I formation with two tight ends'=>8,
			      'I formation, with the second wideout in the slot'=>16,
			      'Pro formation, [^w]'=>1,
			      'Pro formation with two tight ends'=>9,
			      'Pro formation, with the second receiver in the slot'=>17,
			      'Weak formation,'=>2,
			      'Weak formation with two tight ends'=>10,
			      'Weak formation with three wide receivers'=>26,
			      'Strong formation,'=>3,
			      'Strong formation with two tight ends'=>11,
			      'Strong formation with three wideouts'=>27,
			      'Single-Back formation,'=>4,
			      'Single-Back formation with two tight ends'=>12,
			      'Single-Back formation with trips receivers'=>36,
			      'Single-Back formation with four receivers'=>44,
			      'Five-Receiver Spread formation'=>53,
			      'Goal-Line formation'=>56,
			      // Strength
			      'strength is left'=>0,
			      'strength is right'=>64);
						
$kREYardAbbrev = "/([a-zA-Z]{2,3})([0-9]{2})/";
						
class LogFileParser
{
	
	private $dataMgr;
	private $year;
	private $week;
	private $gameId;
	private $logFile; 
	private $awayTeam;
	private $homeTeam;
	
	
	public function __construct($year, $week, $awayId, $homeId){
		$this->year = $year;
		$this->week = $week;
		
		//get the log file location
		$this->logFile = APP_ROOT . '/' . getGameFileName('log',$year, $week, $homeId, $awayId);
		
		//get the game id from the log file name
		preg_match("/([0-9])+/",basename($this->logFile,".html"),$m);
		$this->gameId = $m[0];
		
		$this->dataMgr = createNewDataManager();
		
		//load the teams
		$this->awayTeam =& Team::fromId($awayId);
		$this->homeTeam =& Team::fromId($homeId);
		
	}
	
	public function alreadyParsed(){

		$sql = "SELECT count(1) FROM gamecenter WHERE game_id = " . $this->gameId;
		$qCnt = $this->dataMgr->runQuery($sql);
		$cnt = mysql_fetch_array($qCnt);
		return $cnt[0] > 0;
	}
	
	public function canParse(){
		return (file_exists($this->logFile) && !$this->alreadyParsed());
	}
	
	
	public function parse(){
		$bSuccess = false;
		if($this->canParse()){
			
			//set up some of the elements in $this->data that the database expects
			$this->data['game_id'] = $this->gameId;
			$this->data['team_1_name'] = $this->awayTeam->getCity(false,false);
			$this->data['team_0_name'] = $this->homeTeam->getCity(false,false);
			$this->data['team_1_short'] = $this->awayTeam->getProp('abbrev');
			$this->data['team_0_short'] = $this->homeTeam->getProp('abbrev');
			$this->data['gamecenter_year'] = $this->year;
			$this->data['gamecenter_week'] = $this->week;
			
			$source = file_get_contents($this->logFile);
			$this->runParse($source);
			$bSuccess = true;
		}
		return $bSuccess;
	}
	
	private function runParse(&$source) {
    global $defensive_formations;
    global $offensive_formations;
		global $kREYardAbbrev;
		
    $playcount = 1;
    $this->data['drive'] = 0;

    // Who won the toss?
    preg_match_all("/([A-Za-z(). ]+) won the toss and elected to ([a-z]+)/", $source, $toss);
    if ($toss[1][0] == $this->data['team_0_name'] && $toss[2][0] == 'receive') {
      $kicking_team = 1;
    } else {
      // Team 0 gets -1 because they are traveling in the negative direction
      $kicking_team = -1;
    }
		debugOut("TOSS:" . $this->data['team_0_name']);
		debugOut("TOSS:" . $toss[1][0]);
		debugOut("TOSS:" . $toss[2][0]);
		
		debugOut("kicking team: " . $kicking_team);
		
		
    // Pull out each play
    $plays = preg_split("/[\n\r]+/", $source);
    //preg_match_all("/>([^<]+)</", $source, $plays);
    //$plays = $plays[1];

    $started = false;
		
		//loop through all the plays and parse them
    foreach($plays as $key=>$play) {
    	
			debugOut("<BR/>=======================");
			
			//unset the common flags
      unset($penalty_processed);
      unset($fumble_parsed);
      unset($this->data['field_goal_text']);
		  
			//Get the next actual play...it's used sometimes for penalty processing
      $i = $key;
			do {
				$i++;
				$next_play = preg_replace("/<[^>]+>/", "", $plays[$i]);
		  } while ($plays[$i] && !$this->get_line($next_play));
			

			// Get the team bg color
      preg_match_all("/BGCOLOR=#([0-9A-F]+)/", $play, $bgcolor);
      preg_match_all("/FONT COLOR=#([0-9A-F]+)/", $play, $fgcolor);
      $bg_color = $bgcolor[1][0].$fgcolor[1][0];
      // Check to see if $team_color has been set
      if (is_array($team_color) && !$team_color[$bg_color]) {
				foreach($team_color as $value) {
	  		// Will only be one
	  			$team_color[$bg_color] = -$value;
				}
      }
			
			// Force a new drive?
      if ($new_drive) {
				$this->data['drive']++;
				$new_drive = false;
      }
			
      // Set the initial travel_direction and team values
      if ($team_color[$bg_color]) {
				// Don't incement if it's just a time out or the start of the 2nd or 4th quarters
				if (!preg_match("/time out|second quarter|fourth quarter/", $play)) {
	  			if ($travel_direction != $team_color[$bg_color]) {
	    			$this->data['drive'] ++;
	  			}
	  
					$travel_direction = $team_color[$bg_color];
		  		if ($travel_direction > 0) {
		    		$this->data['team'] = 1;
		  		} else {
		    		$this->data['team'] = 0;
		  		}
				}
      }
      
			
			// Strip html tags
      $play = preg_replace("/<[^>]+>/", "", $play);
      $parsed = false;
      if (!$this->data['qtr']) {
				// Get to the first quarter
				if ($play == "Start of first quarter.") {
	  		$this->data['team_0_q1'] = 0;
	  		$this->data['team_1_q1'] = 0;
	  		$started = true;
	  		$parsed = true;
				}
      }
      
			
			/*
			 * 
			 * 
			 * 
			 * Parse the play
			 * 
			 * 
			 * 
			 */

			debugOut("Parsing: " . $play);
			
			//Kickoff
      if (preg_match("/kicked off/", $play)) {
				$this->data['team'] = kKick;
				unset($this->data['down']);
				unset($this->data['togo']);
				unset($this->data['off_form']);
				unset($this->data['def_form']);
				// More than one animation, so unset "play" so it's NULL
				unset($this->data['play_text']);
				$travel_direction = $team_color[$bg_color];
				if (!$travel_direction) {
				  // First kickoff, set the initial team
				  $travel_direction = $kicking_team;
					debugOut("travel: " . $travel_direction);
				  $team_color[$bg_color] = $kicking_team;
				  // Start a new drive!
				  $this->data['drive'] ++;
				}
	
				// Get the starting line
				preg_match_all("/from the ([ A-Za-z0-9]+)/", $play, $start);
				
				debugOut("play: " . $play);
				debugOut("start: " . $start[1][0]);
				
				
				$this->data['start'] = $this->get_line($start[1][0]);
				preg_match_all("/([0-9]+) yard/", $play, $distance);
				$this->data['end'] = $this->data['start'] + ($distance[1][0] * $travel_direction);
				
				debugOut("start: " . $start[1][0]);
				debugOut("start: " . $this->data['start']);
				debugOut("distance: " . $distance[1][0]);
				debugOut("end: " . $this->data['end']);
				
				$this->save_play();
				// Now for the return
				$this->data['start'] = $this->data['end'];
				$travel_direction = -$travel_direction;
				if ($travel_direction > 0) {
				  $this->data['team'] = 1;
				} else {
				  $this->data['team'] = 0;
				}
				if (preg_match("/Touchback/", $play)) {
				  if ($travel_direction > 0) {
				    $this->data['end'] = 20;
				  } else {
				    $this->data['end'] = 80;
				  }
				} else {
					debugOut("play: " . $play);
				  preg_match_all("/to the ([ A-Za-z0-9]+)/", $play, $end);
					debugOut("count: " . count($end));
					debugOut("count1:" . count($end[0]));
					debugOut("count2:" . count($end[1]));
					debugOut("return: " . $end[1][0]);
				  $this->data['end'] = $this->get_line($end[1][0]);
				}
				
				debugOut("start: " . $this->data['start']);
				debugOut("end: " . $this->data['end']);
				
				// Check for fumble
				//cw commented out this fumble check because it's done later anyway
				/*
				if (preg_match("/fumble/", $play)) {
				  $fumble_parsed = true;
				  $this->save_play();
				  // Recovery
				  preg_match_all("/recovered by ([ a-zA-Z]+)/", $play, $recovery);
				  if ($recovery[1][0] == $this->data['team_0_short']) {
				    $travel_direction = -1;
				    $this->data['team'] = 0;
				  } else {
				    $travel_direction = 1;
				    $this->data['team'] = 1;
				  }
				  $this->data['start'] = $this->data['end'];
					
					//TODO: validity of following?  What about /([ ]?[A-Z]+[0-9]+)\./
				  preg_match_all("/([ A-Z0-9]+)\./", $play, $return);
				  $this->data['end'] = $this->get_line($return[1][0]);
				  $new_drive = true;
				  $parsed = true;
				}
				*/
				/*
				// Check for penalty
				if (preg_match("/PENALTY/", $play) && !preg_match("/declined/", $play)) {
				  $penalty_processed = true;
				  $this->save_play();
				  $this->data['start'] = $this->data['end'];
				  $this->data['end'] = $this->get_line($next_play);
				  $this->data['team'] = kPenalty;
				}
				*/
				$new_drive = true;
				$parsed = true;
    	}
			//end kickoff
			
      // Safety
      if (preg_match("/a safety/", $play)) {
				if ($travel_direction > 0) {
				  $this->data['end'] = -5;
				} else {
				  $this->data['end'] = 105;
				}
				$new_drive = true;
				$parsed = true;
      }
			
      // Punt?
      if (preg_match("/punted/", $play) && !$parsed) {
				unset($this->data['off_form']);
				unset($this->data['def_form']);
				// Blocked punt for safety needs to not call this
				$this->data['team'] = kKick;
				// More than one animation, so unset "play" so it's NULL
				unset($this->data['play_text']);
				// Get the starting line
				$this->data['start'] = $this->get_line($play);
				preg_match_all("/([0-9]+) yard/", $play, $distance);
				$this->data['end'] = $this->data['start'] + ($distance[1][0] * $travel_direction);
				$this->save_play();
				// Now for the return
				$this->data['start'] = $this->data['end'];
				$travel_direction = -$travel_direction;
				if ($travel_direction > 0) {
				  $this->data['team'] = 1;
				} else {
				  $this->data['team'] = 0;
				}
				if (preg_match("/Touchback/", $play)) {
				  if ($travel_direction > 0) {
				    $this->data['end'] = 20;
				  } else {
				    $this->data['end'] = 80;
				  }
				} else {
				  preg_match_all("/returned the kick ([-0-9]+) yard/", $play, $progress);
				  $this->data['end'] = $this->data['start'] + ($progress[1][0] * $travel_direction);
				}
				// Check for fumble
				//cw commented out this fumble check because it's done later anyway
				/*if (preg_match("/fumble/", $play)) {
				  $fumble_parsed = true;
				  $this->save_play();
				  // Recovery
				  preg_match_all("/recovered by ([ a-zA-Z]+)/", $play, $recovery);
				  if ($recovery[1][0] == $this->data['team_0_short']) {
				    $travel_direction = -1;
				    $this->data['team'] = 0;
				  } else {
				    $travel_direction = 1;
				    $this->data['team'] = 1;
				  }
				  $this->data['start'] = $this->data['end'];
				  preg_match_all("/([ A-Z0-9]+)\./", $play, $return);
				  $this->data['end'] = $this->get_line($return[1][0]);
				  $new_drive = true;
				  $parsed = true;
				}*/
				/*
				// Check for penalty
				if (preg_match("/PENALTY/", $play) && !preg_match("/declined/", $play)) {
				  $penalty_processed = true;
				  $this->save_play();
				  $this->data['start'] = $this->data['end'];
				  $this->data['end'] = $this->get_line($next_play);
				  $this->data['team'] = kPenalty;
				}
				*/
				$new_drive = true;
				$parsed = true;
      }//end punt
			
			
			
      // Forward progress?
      if (preg_match("/for [-0-9]+ yard/", $play)) {
				$this->data['start'] = $this->get_line($play);
				preg_match_all("/for ([-0-9]+) yard/", $play, $progress);
				$this->data['end'] = $this->data['start'] + ($progress[1][0] * $travel_direction);
				$parsed = true;
				$this->data['off_form'] = $this->offensive_formation($play);
				$this->data['def_form'] = $this->defensive_formation($play);
      }
      // Loss?
      if (preg_match("/loss of [0-9]+ yard/", $play)) {
				$this->data['start'] = $this->get_line($play);
				preg_match_all("/loss of ([0-9]+) yard/", $play, $progress);
				$this->data['end'] = $this->data['start'] + ($progress[1][0] * $travel_direction * -1);
				$parsed = true;
				$this->data['off_form'] = $this->offensive_formation($play);
				$this->data['def_form'] = $this->defensive_formation($play);
      }
			
      // Incomplete pass
      if (preg_match("/spiked the ball|incomplete|blocked at the line|pass was dropped/", $play)) {
				$this->data['start'] = $this->get_line($play);
				$this->data['end'] = $this->data['start'];
				$parsed = true;
				$this->data['off_form'] = $this->offensive_formation($play);
				$this->data['def_form'] = $this->defensive_formation($play);
      }
      
			// Interception
      if (preg_match("/intercepted/", $play)) {
				$this->data['start'] = $this->get_line($play);
				preg_match_all("/at the ([ A-Za-z0-9]+)/", $play, $turnover);
				$this->data['end'] = $this->get_line($turnover[1][0]);
				unset($this->data['play_text']);
				$this->save_play();
				$travel_direction = -$travel_direction;
				$this->data['start'] = $this->data['end'];
				if (preg_match("/TOUCHDOWN/", $play)) {
				  if ($travel_direction > 0) {
				    $this->data['end'] = 105;
				  } else {
				    $this->data['end'] = -5;
				  }
				} else {
				  preg_match_all("/returned ([-0-9]+) yard/", $play, $progress);
					$this->data['end'] = $this->data['start'] + ($progress[1][0] * $travel_direction);
				}
				
				if ($travel_direction > 0) {
				  $this->data['team'] = 1;
				} else {
				  $this->data['team'] = 0;
				}
				$new_drive = true;
				$parsed = true;
				$this->data['off_form'] = $this->offensive_formation($play);
				$this->data['def_form'] = $this->defensive_formation($play);
      }
			
      // Fumble
      if (preg_match("/fumble/", $play) && !$fumble_parsed) {
				/*parse the fumble only. Progress has already been parsed above
				//$this->data['start'] = $this->get_line($play);
				//preg_match_all("/for ([-0-9]+) yard/", $play, $progress);
				
				if(count($progress) > 0)
					$this->data['end'] = $this->data['start'] + ($progress[1][0] * $travel_direction);
				else	
					$this->data['end'] = $this->data['start'];
					
				*/
				//not sure why bother unsetting play_text...
				unset($this->data['play_text']);
				$this->save_play();
				// Recovery
				preg_match_all("/recovered by ( ?[a-zA-Z]+)/", $play, $recovery);
				if ($recovery[1][0] == $this->data['team_0_short']) {
				  $travel_direction = -1;
				  $this->data['team'] = 0;
				} else {
				  $travel_direction = 1;
				  $this->data['team'] = 1;
				}
				$plyReturn = strstr($play, "recovered by");
				$this->data['start'] = $this->data['end'];
				$this->data['end'] = $this->get_line($plyReturn);
				$new_drive = true;
				$parsed = true;
      }
			
      // Field goal
      if (preg_match("/field goal/", $play)) {
				$this->data['start'] = $this->get_line($play);
				$this->data['end'] = $this->get_line($play);
				unset($this->data['off_form']);
				unset($this->data['def_form']);
				if (preg_match("/succeeded/", $play)) {
				  $this->data['field_goal_text'] = '+3';
				} else {
				  $this->data['field_goal_text'] = 'NG';
				  $travel_direction = -$travel_direction;
				}
				$this->data['team'] = kFieldGoal;
				unset($this->data['down']);
				unset($this->data['togo']);
				$new_drive = true;
				$parsed = true;
      }
			
      // Extra Point
      if (preg_match("/Extra point/", $play)) {
				unset($this->data['off_form']);
				unset($this->data['def_form']);
				if ($travel_direction > 0) {
				  $this->data['start'] = 98;
				  $this->data['end'] = 98;
				} else {
				  $this->data['start'] = 2;
				  $this->data['end'] = 2;
				}
				$this->data['team'] = kPAT;
				if (preg_match("/was good/", $play)) {
				  $this->data['field_goal_text'] = '+1';
				} else {
				  $this->data['field_goal_text'] = 'NG';
				}
				unset($this->data['down']);
				unset($this->data['togo']);
				$new_drive = true;
				$parsed = true;
      }
			
      // 2-point conversion
      if (preg_match("/two-point conversion|conversion attempt/", $play)) {
				if ($travel_direction > 0) {
				  $this->data['start'] = 98;
				  $this->data['end'] = 98;
				} else {
				  $this->data['start'] = 2;
				  $this->data['end'] = 2;
				}
				$this->data['team'] = kPAT;
				if (preg_match("/successful/", $play)) {
				  $this->data['field_goal_text'] = '+2';
				} else {
				  $this->data['field_goal_text'] = 'NG';
				}
				unset($this->data['down']);
				unset($this->data['togo']);
				$new_drive = true;
				$parsed = true;
				$this->data['off_form'] = $this->offensive_formation($play);
				$this->data['def_form'] = $this->defensive_formation($play);
      }
			
      // New quarter
      if (preg_match("/Start of [a-z]+ quarter/", $play)) {
				unset($this->data['off_form']);
				unset($this->data['def_form']);
				$this->data['qtr']++;
				$this->data['time'] = "15:00";
				if (preg_match("/second/", $play)) {
				  $this->data['team_0_q2'] = 0;
				  $this->data['team_1_q2'] = 0;
				} elseif (preg_match("/third/", $play)) {
				  unset($this->data['down']);
				  unset($this->data['togo']);
				  unset($this->data['los']);
				  unset($this->data['first_down']);
				  $this->data['team_0_q3'] = 0;
				  $this->data['team_1_q3'] = 0;
				} elseif (preg_match("/fourth/", $play)) {
				  $this->data['team_0_q4'] = 0;
				  $this->data['team_1_q4'] = 0;
				}
				unset($this->data['start']);
				unset($this->data['end']);
				unset($this->data['team']);
				$parsed = true;
      }
			
      if (preg_match("/overtime/", $play)) {
				unset($this->data['off_form']);
				unset($this->data['def_form']);
				$this->data['qtr'] = 'OT';
				$this->data['time'] = "15:00";
				$this->data['team_0_q5'] = 0;
				$this->data['team_1_q5'] = 0;
				unset($this->data['start']);
				unset($this->data['end']);
				unset($this->data['team']);
				unset($this->data['down']);
				unset($this->data['togo']);
				unset($this->data['los']);
				unset($this->data['first_down']);
				$this->data['drive'] ++;
				$parsed = true;
      }
			
      // Final score
      if (preg_match("/Final Score/", $play)) {
				unset($this->data['off_form']);
				unset($this->data['def_form']);
				unset($this->data['start']);
				unset($this->data['end']);
				unset($this->data['down']);
				unset($this->data['togo']);
				$this->data['time'] = '0:00';
				unset($this->data['los']);
				unset($this->data['first_down']);
				$this->data['drive']++;
				unset($this->data['qtr']);
				$new_drive = true;
				$parsed = true;
      }
			
      // Touchdown?
      if (preg_match("/TOUCHDOWN/", $play)) {
				$this->data['end'] += 5*$travel_direction;
      }
			
      // Penalty?  Do this last, it will overwrite the play.
      if (preg_match("/PENALTY/", $play) && !preg_match("/declined/", $play) && !$penalty_processed) {
				if ($parsed) {
				  // We had a play, let's show it
				  $this->save_play();
				  unset($this->data['off_form']);
				  unset($this->data['def_form']);
				}
				if (preg_match("/Face Mask|Unnecessary Roughness|Unsportsmanlike Conduct|kick|punt/", $play)) {
				  // Penalty starts at the end of the play
				  $this->data['start'] = $this->data['end'];
				} else {
				  // Penalty starts at the previous play
				  $this->data['start'] = $this->get_line($play);
				}
				debugOut("<BR/>PENALTY NEXT PLAY: " . $next_play );
				$this->data['end'] = $this->get_line($next_play);
				$this->data['team'] = kPenalty;
				$parsed = true;
      }
      
			// Score update?
      $score_use = false;
			//commented out the original one because it didn't match well on st. louis	 
      //preg_match_all("/([a-zA-Z][a-zA-Z() \.]+) ([0-9]+), ([a-zA-Z() \.]+) ([0-9]+)/", $play, $score);
			debugOut('SCORING REG EXP');
			debugOut("/(" . $this->escapeTeamName($this->data['team_0_name']) . ") ([0-9]+), (" . $this->escapeTeamName($this->data['team_1_name']) . ") ([0-9]+)/", $play, $score);
			preg_match_all("/(" . $this->escapeTeamName($this->data['team_0_name']) . ") ([0-9]+), (" . $this->escapeTeamName($this->data['team_1_name']) . ") ([0-9]+)/", $play, $score);
			if(!count($score[0]))
				preg_match_all("/(" . $this->escapeTeamName($this->data['team_1_name']) . ") ([0-9]+), (" . $this->escapeTeamName($this->data['team_0_name']) . ") ([0-9]+)/", $play, $score);
			
      if (count($score[0])) {
				if ($score[1][0] == $this->data['team_0_name']) {
				  $score_use = true;
				  $team_0_score = $score[2][0];
				} elseif ($score[1][0] == $this->data['team_1_name']) {
				  $score_use = true;
				  $team_1_score = $score[2][0];
				}
				
				if ($score[3][0] == $this->data['team_0_name']) {
				  $score_use = true;
				  $team_0_score = $score[4][0];
				} elseif ($score[3][0] == $this->data['team_1_name']) {
				  $score_use = true;
				  $team_1_score = $score[4][0];
				}
				
				if ($score_use) {
				  switch($this->data['qtr']) {
				  case '1':
				    $this->data['team_1_q1'] = $team_1_score;
				    $this->data['team_0_q1'] = $team_0_score;
				    break;
				  case '2':
				    $this->data['team_1_q2'] = $team_1_score - $this->data['team_1_q1'];
				    $this->data['team_0_q2'] = $team_0_score - $this->data['team_0_q1'];
				    break;
				  case '3':
				    $this->data['team_1_q3'] = $team_1_score - $this->data['team_1_q1'] - $this->data['team_1_q2'];
				    $this->data['team_0_q3'] = $team_0_score - $this->data['team_0_q1'] - $this->data['team_0_q2'];
				    break;
				  case '4':
				    $this->data['team_1_q4'] = $team_1_score - $this->data['team_1_q1'] - $this->data['team_1_q2'] - $this->data['team_1_q3'];
				    $this->data['team_0_q4'] = $team_0_score - $this->data['team_0_q1'] - $this->data['team_0_q2'] - $this->data['team_0_q3'];
				    break;
				  case 'OT':
				    $this->data['team_1_q5'] = $team_1_score - $this->data['team_1_q1'] - $this->data['team_1_q2'] - $this->data['team_1_q3'] - $this->data['team_1_q4'];
				    $this->data['team_0_q5'] = $team_0_score - $this->data['team_0_q1'] - $this->data['team_0_q2'] - $this->data['team_0_q3'] - $this->data['team_0_q4'];
				    break;
				  }
				  unset($this->data['los']);
				  unset($this->data['first_down']);
				}
      }
      
			// Get the down detail
      preg_match_all("/([0-9]+)-([0-9]+)-([ A-Za-z]+)([0-9]+)/", $next_play, $down_detail);
      if ($down_detail[3][0] == $this->data['team_0_short']) {
				// Convert to 100-yard field
				$down_detail[4][0] = 100 - $down_detail[4][0];
      }
			
      if($down_detail[1]) {
				$this->data['down'] = $down_detail[1][0];
				$this->data['togo'] = $down_detail[2][0];
				$this->data['los'] = $down_detail[4][0];
				$this->data['first_down'] = $down_detail[4][0] + ($down_detail[2][0] * $travel_direction);
      } 
			else {
				//unset($this->data['down']);
				//unset($this->data['togo']);
      }
			
      preg_match_all("/[0-9]{1,2}:[0-9]{2}/", $next_play, $time);
      if ($time[0][0]) {
				$this->data['time'] = $time[0][0];
      }
			
      // Time out (must be after the down detail)
      if (preg_match("/time out/", $play)) {
				unset($this->data['off_form']);
				unset($this->data['def_form']);
				unset($this->data['start']);
				unset($this->data['end']);
				unset($this->data['team']);
				// Need to apply the down data to the previous play
				$statement = "select * from gamecenter where game_id = '".$this->data['game_id']."' order by
											gamecenter_id desc limit 1";
				
				$result = $this->dataMgr->runQuery($statement);
				
				while ($row = mysql_fetch_array($result)) {
				  $statement = "update gamecenter set down = '".$this->data['down']."', togo = '".$this->data['togo']."',
												time = '".$this->data['time']."', los = '".$this->data['los']."', first_down = '".$this->data['first_down']."'
												where gamecenter_id = '".$row['gamecenter_id']."'";
				  $this->dataMgr->runQuery($statement);
				}
				$parsed = true;
      }
      
			if ($started && !$parsed && $this->debug) {
				echo "<P>$play</p>";
      }
      if ($play && $started) {
				$this->data['play_text'] = addslashes($play);
				$this->data['gamecenter_playcount'] = $playcount;
				$playcount++;
				$this->save_play();
      }
    }//end parsing play
  }

  private function offensive_formation($play) {
    global $offensive_formations;
    $formation = 0;
    foreach($offensive_formations as $text=>$value) {
      if (preg_match("/".$text."/", $play)) {
	$formation += $value;
      }
    }
    return $formation;
  }

  private function defensive_formation($play) {
    global $defensive_formations;
    $formation = 0;
    foreach($defensive_formations as $text=>$value) {
      if (preg_match("/".$text."/", $play)) {
	$formation += $value;
      }
    }
    return $formation;
  }

  private function save_play() {
    $cols = array();
    $values = array();
    foreach($this->data as $key=>$value) {
      $cols[] = $key;
      $values[] = "'".$value."'";
    }
    $statement = "insert into gamecenter (".implode(",",$cols).") values (".implode(",",$values).")";
		
		debugOut($statement);
		
		$this->dataMgr->runQuery($statement);
		//echo $statement;
    //mysql_query($statement);
    //return mysql_insert_id();
  }

  private function get_line($line) {
  	$arg = $line;
    // Returns the line value for XXXYY in the play

		//CW: This is my regular expression
    preg_match_all("/( ?[a-zA-Z]{2,3})([0-9]{2})/", $line, $line);
		
		//CW: This is the original one.  I changed because this would match on all sorts of crap (one letter followed by 10 numbers, etc)
		//ALSO, the old one HAD to have a three letter abbreviation.  Since I have several abbreviations with two letters,
		//I had to add the ' ?' at the start too (because the two letter abbreviations are stored with a leading space character in 
		//the database.)
		//preg_match_all("/([a-zA-Z]+)([0-9]+)/", $line, $line);
		
		//debugOut("GetLine (". $arg . "):<strong>" . var_dump($line[1][0]) . "|</strong>" . var_dump($line[2][0]));

    if ($line[1][0] == $this->data['team_0_short']) {
      $return = 100 - $line[2][0];
    } else {
      $return = $line[2][0];
    }
    return $return;
  }

	private function escapeTeamName($teamName){
		$teamName = str_ireplace('(', '\(', $teamName);
		$teamName = str_ireplace(')', '\)', $teamName);
		return $teamName;
	}
	
	
	
	private function savePlay(){
		
	}
	
	
}

?>