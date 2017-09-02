<?php

include_once(DBMGR_ROOT . '/DataMgr.php');
include_once(APP_ROOT . '/func/leagueUtils.php');

class GameCenter
{
	private $dataMgr;
	private $gameId;
	private $lastPlayId;
	
	public function __construct($gameId,$lastPlayId){
		
		$this->dataMgr = createNewDataManager();
		$this->gameId = $gameId;
		$this->lastPlayId = $lastPlayId;
	}
	
	
	public function update() {
    $last_id = $this->lastPlayId;
    $game_id = $this->gameId;

    // Find the next play
    $statement = "select * from gamecenter where gamecenter_id > '$last_id' and game_id = '$game_id' and
play_text is not NULL
order by gamecenter_id";
		$qPlays = $this->dataMgr->runQuery($statement);
    $row = mysql_fetch_array($qPlays);
    if ($row['gamecenter_id']) {
      $thisplay = $row['gamecenter_id'];
      // We have a play to send.
      $statement = "select * from gamecenter where game_id = '$game_id' and drive = '".$row['drive']."' and
gamecenter_id <= '".$row['gamecenter_id']."'
order by gamecenter_id";
			$result = $this->dataMgr->runQuery($statement);
      $xml .= '
<update>';
      while ($row = mysql_fetch_array($result)) {
	$xml .= '
  <play>
    <id>'.$row['gamecenter_id'].'</id>
    <start>'.$row['start'].'</start>
    <end>'.$row['end'].'</end>
    <fgtext>'.$row['field_goal_text'].'</fgtext>
    <team>'.$row['team'].'</team>';
	if ($row['team'] != 2 && $row['start'] != $row['end'] && !$row['play_text'] || $row['gamecenter_id'] == $thisplay) {
	  $xml .= '
    <animate>1</animate>';
	}
	$xml .= '
  </play>';
	$last_row = $row;
      }
      $row = $last_row;
      // Down and distance
      $xml .= '
  <down>'.$row['down'].'</down>
  <togo>'.$row['togo'].'</togo>
  <qtr>'.$row['qtr'].'</qtr>
  <time>'.$row['time'].'</time>';
      if ($row['los'] != '') {
	$xml .= '
  <los>'.$row['los'].'</los>';
      }
      if ($row['first_down'] != '') {
	$xml .= '
  <firstdown>'.$row['first_down'].'</firstdown>';
      }
      $xml .= '
  <playtext>'.$row['play_text'].'</playtext>';
      // Formation info?
      if ($row['off_form'] != '') {
	$xml .= '
  <off_form>'.$row['off_form'].'</off_form>
  <def_form>'.$row['def_form'].'</def_form>';
      }
      // Lastly use the most recent score data
      // Team 0
      $xml .= '
  <team>
    <name>'.strtoupper($row['team_0_name']).'</name>
    <shortname>'.strtoupper($row['team_0_short']).'</shortname>
    <q1>'.$row['team_0_q1'].'</q1>
    <q2>'.$row['team_0_q2'].'</q2>
    <q3>'.$row['team_0_q3'].'</q3>
    <q4>'.$row['team_0_q4'].'</q4>
    <q5>'.$row['team_0_q5'].'</q5>
    <score>'.($row['team_0_q1']+
	      $row['team_0_q2']+
	      $row['team_0_q3']+
	      $row['team_0_q4']+
	      $row['team_0_q5']).'</score>
  </team>';
      // Team 1
      $xml .= '
  <team>
    <name>'.strtoupper($row['team_1_name']).'</name>
    <shortname>'.strtoupper($row['team_1_short']).'</shortname>
    <q1>'.$row['team_1_q1'].'</q1>
    <q2>'.$row['team_1_q2'].'</q2>
    <q3>'.$row['team_1_q3'].'</q3>
    <q4>'.$row['team_1_q4'].'</q4>
    <q5>'.$row['team_1_q5'].'</q5>
    <score>'.($row['team_1_q1']+
	      $row['team_1_q2']+
	      $row['team_1_q3']+
	      $row['team_1_q4']+
	      $row['team_1_q5']).'</score>
  </team>';
      // Other games
      $statement = "select game_id, gamecenter_id, team_0_short, team_0_q1 + if (team_0_q2 is NULL, 0, team_0_q2) + if(team_0_q3 is NULL, 0, team_0_q3) + if(team_0_q4 is NULL, 0, team_0_q4) + if(team_0_q5 is NULL, 0, team_0_q5) team_0_score, team_1_q1 + if (team_1_q2 is NULL, 0, team_1_q2) + if(team_1_q3 is NULL, 0, team_1_q3) + if(team_1_q4 is NULL, 0, team_1_q4) + if(team_1_q5 is NULL, 0, team_1_q5) team_1_score, team_1_short, time, qtr from gamecenter where gamecenter_playcount = '".$row['gamecenter_playcount']."' and gamecenter_year = '".$row['gamecenter_year']."' and gamecenter_week = '".$row['gamecenter_week']."' group by game_id order by game_id;";
      $result = $this->dataMgr->runQuery($statement);
      $xml .= '
  <othergames>';
      while($row = mysql_fetch_array($result)) {
	$xml .= '
    <game>
      <gamecenter_id>'.$row['gamecenter_id'].'</gamecenter_id>
      <game_id>'.$row['game_id'].'</game_id>
      <team_0>'.$row['team_0_short'].'</team_0>
      <team_0_score>'.$row['team_0_score'].'</team_0_score>
      <team_1>'.$row['team_1_short'].'</team_1>
      <team_1_score>'.$row['team_1_score'].'</team_1_score>';
	if ($row['qtr']) {
	  $time = $row['time'];
	  if ($row['qtr'] != 'OT') {
	    $time .= ' Q'.$row['qtr'];
	  } else {
	    $time .= ' OT';
	  }
	} else {
	  $time = "FINAL";
	}
	$xml .= '
      <time>'.$time.'</time>
    </game>';
      }
      $xml .= '
  </othergames>';
      $xml .= '
</update>';
    } else {
      $xml .= '
<update>
  <done>1</done>
</update>';
    }
    return $xml;    
  }
	
}

?>