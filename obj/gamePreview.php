<?php
  
	include_once(OBJECT_ROOT . '/Game.php');
	include_once(OBJECT_ROOT . '/Player.php');
	include_once(OBJECT_ROOT . '/Team.php');
	
	
	class GamePreview {
		protected $size;
		
		public function __construct($size="medium")
		{
			$this->size = $size;
		}
		
		public function showPreview($id)
		{
		
			$g = new GameFuture($id);	
				
			if($g->getProp("Away") == 1)
			{
				$away = Team::fromId($g->getProp("TeamID"));
				$home = Team::fromId($g->getProp("OpponentID"));
			}
			else
			{
				$home = Team::fromId($g->getProp("TeamID"));
				$away = Team::fromId($g->getProp("OpponentID"));
			}
			
			echo "<table class='center'>
							<tr>
								<td class='center' nowrap>
									<img src='" . $away->getHelmetImg('right', $g->getProp("Year")) . "' height='".$this->getImgHeight()."px' width='".$this->getImgWidth()."px'>
								</td>
								<td>&nbsp;</td>
								<td>
									<img src='" . $home->getHelmetImg('left', $g->getProp("Year")) . "' height='".$this->getImgHeight()."px' width='".$this->getImgWidth()."px'>						
								</td>
							</tr>
							<tr>
								<td class='headlineSmall'>
								" . $away->getFullName() . "<br/>(" . $away->getRecord(0,($g->getProp("Week") >= WEEK_REGSEASON_START?WEEK_REGSEASON_START:WEEK_PRESEASON_START)) . ")
								</td>
								<td class='headlineSmall'>";
								
								if($g->getProp('Week') != WEEK_ULTIMATE_BOWL)
									echo "@";
								else
									echo "&nbsp;";
									
								echo "
								</td>
								<td class='headlineSmall'>
								" . $home->getFullName() . "<br/>(" . $home->getRecord(0,($g->getProp("Week") >= WEEK_REGSEASON_START?WEEK_REGSEASON_START:WEEK_PRESEASON_START)) . ")
								</td>
							</tr>
						</table>
						<table class='center' style='width:95%;'>
							<tr>
								<td>" . $g->getNewsFeed() . "</td>
							</tr>
							
						</table>
						
			";
			
			echo $g->getBoxAndLogLinks();
			
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
		
	} //end gamePreview class

	
?>