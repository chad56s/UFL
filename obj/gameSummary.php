<?php
  
	include_once(OBJECT_ROOT . '/Team.php');
	include_once(OBJECT_ROOT . '/Game.php');
	include_once(OBJECT_ROOT . '/Player.php');
	
	class GameReview {
		
		protected $size;
		
		function __construct($size="medium")
		{
			$this->size = $size;
		}
		
		function showReview($id)
		{
			$g = new Game($id);
			
			$home = Team::fromId($g->getProp("HomeTeam"));
			$away = Team::fromId($g->getProp("AwayTeam"));
			
			$topHomePasser = new Player($g->getProp("HomePassingLeaderPlayerID"));
			$topAwayPasser = new Player($g->getProp("AwayPassingLeaderPlayerID"));
			$topHomeRusher = new Player($g->getProp("HomeRushingLeaderPlayerID"));
			$topAwayRusher = new Player($g->getProp("AwayRushingLeaderPlayerID"));
			$topHomeReceiver = new Player($g->getProp("HomeReceivingLeaderPlayerID"));
			$topAwayReceiver = new Player($g->getProp("AwayReceivingLeaderPlayerID"));
			echo "<table class='center'>
							<tr>
								<td>
									<img src='".$away->getHelmetImg('right', $g->getProp("Year"))."' height='".$this->getImgHeight()."px' width='".$this->getImgWidth()."px'>
								</td>
								<td>
									<img src='".$home->getHelmetImg('left', $g->getProp("Year"))."' height='".$this->getImgHeight()."px' width='".$this->getImgWidth()."px'>
								</td>
							</tr>
							<tr>
								<td>Top Passer: ".$topAwayPasser->getProp("LastName")."<div style='font-size:.7em;'>("
										.$g->getProp("AwayPassCompletions")
										."/".$g->getProp("AwayPassAttempts")
										." ".$g->getProp("AwayPassYards")
										." yds)</div></td>
								<td>Top Passer: ".$topHomePasser->getProp("LastName")."<div style='font-size:.7em;'>("
										.$g->getProp("HomePassCompletions")
										."/".$g->getProp("HomePassAttempts")
										." ".$g->getProp("HomePassYards")
										." yds)</div></td>
							</tr>
							
							<tr>
								<td>Top Rusher: ".$topAwayRusher->getProp("LastName")."<div style='font-size:.7em;'>("
										.$g->getProp("AwayRushAttempts")
										."/".$g->getProp("AwayRushYards")
										." yds)</div></td>
								<td>Top Rusher: ".$topHomeRusher->getProp("LastName")."<div style='font-size:.7em;'>("
										.$g->getProp("HomeRushAttempts")
										."/".$g->getProp("HomeRushYards")
										." yds)</div></td>
							</tr>
							
							<tr>
								<td>Top Receiver: ".$topAwayReceiver->getProp("LastName")."<div style='font-size:.7em;'>("
										.$g->getProp("AwayReceptions")
										."/".$g->getProp("AwayReceivingYards")
										." yds)</div></td>
								<td>Top Receiver: ".$topHomeReceiver->getProp("LastName")."<div style='font-size:.7em;'>("
										.$g->getProp("HomeReceptions")
										."/".$g->getProp("HomeReceivingYards")
										." yds)</div></td>
							</tr>
						</table>
							
							
			";
						
		}
		
		
		private function getImgHeight(){
			$h = 120;
			if($this->size == "small")
				$h = 60;
			elseif($this->size == 'medium')
				$h = 120;
			elseif($this->size == 'large')
				$h = 240;
				
			return $h;
		}
		private function getImgWidth(){
			$w = 160;
			if($this->size == "small")
				$w = 80;
			elseif($this->size == 'medium')
				$w = 160;
			elseif($this->size == 'large')
				$w = 320;
				
			return $w;
		}
		
	} //end gameReview class


?>