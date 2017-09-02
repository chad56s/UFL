<?php

	include_once(DBMGR_ROOT . '/DataMgr.php');
	include_once(APP_ROOT . '/func/leagueUtils.php');
	
	class PlayerProfile {
	
		private $dataMgr;
		private $props;
		private $attrs;
		
		public function __construct($id)
		{
			
			$this->dataMgr = createNewDataManager();
			$this->props = array();
			$this->attrs = array();
			
			$qPlayer = NULL;
			
			$sql = "SELECT *
							  FROM draft_player a
							 WHERE a.player_id = ".$id;
							 
							 
			$qPlayer = $this->dataMgr->runQuery($sql);
			
			if(mysql_num_rows($qPlayer))
			{
				$this->props = mysql_fetch_assoc($qPlayer);
			}
			
			$sql = "SELECT b.attribute_name, b.attribute_id,
										a.player_to_attribute_low as low, a.player_to_attribute_high as high
							  FROM draft_player_to_attribute a
							  JOIN draft_attribute b
								  ON a.attribute_id = b.attribute_id
							 WHERE player_id = ".$id."
							 ORDER BY  b.attribute_id"; 
						
			$qAttributes = $this->dataMgr->runQuery($sql);

			while($attr = mysql_fetch_object($qAttributes)){
				$this->attrs[$attr->attribute_name] = array('low'=>$attr->low, 'high'=>$attr->high, 'id'=>$attr->attribute_id);
			}

		}
		
		public function getProp($prop)
		{
			if($this->props && array_key_exists($prop,$this->props))
				return $this->props[$prop];
			else
				return null;
		}
		
		public function getAttributes(){
			return $this->attrs;
		}
		
		public function printAttributes(){
			echo "<div class='rangeContainer'>";
			echo "<span style='font-family:arial black; color:silver; margin-left:30px; text-align:left;'>" . $this->getProp('player_name') ." - " .mapGetPositionGroup($this->getProp("position_id")) . "</span><br/>";
			echo "<span style='font-family:arial black; font-size: 0.7em; color:silver; margin-left:30px; text-align: left;'>" . $this->getProp('player_school') . "</span>";
			foreach($this->attrs as $attr => $range){
				$low = $range['low'];
				$high = $range['high'];
				$attrId = $range['id'];
				$lowWidth = $low;
				
				$avg = ($low + $high)/2;
				$width = $high - $low;
				
				if($attrId == 1){
					//formations for QBs
					$divisor = 2;
					$lowWidth = $lowWidth * 6;
				}
				else
					$divisor = 25;
				
				
				echo "<div class='range'>";
				echo "<div class='rangeTitle' style='text-align:right; width: 125px;'>" . $attr . ":</div> ";
				
				echo "<div class='rangeBar'>
								<div class='rangeFill rangeFill" . floor($avg/$divisor) ."' style='width:" . $lowWidth . "px;'> </div>
								<div class='rangeMarker rangeMarker" . floor($avg/$divisor) . "' style='left:" . $lowWidth . "px; width: " . $width . "%;'>" . $low . "-" . $high . "</div>
							</div>";
				echo "<div class='clear'></div></div>";
			}
			echo "</div>";
		}

	}
	
	
?>