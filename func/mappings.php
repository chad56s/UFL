<?php
  $ufl_Mappings_Lookup = array("position" => array(),
														 "positionGroup" => array(),
														 "transactionType" => array(),
														 "precipitation" => array(),
														 "playerStatus" => array(),
														 "staffRole" => array(),
														 "playoffs" => array(),
														 "ability" => array(),
														 "stadiumType" => array(),
														 "constructionType" => array(),
														 "driveResult" => array(),
														 "injury" => array()
														 );
	
	/*
	 * Don't call this function directly.  Use the utility ones for the mapping you're 
	 * interested in below.
	 */
	function uflLoadMapping($mapType){
		global $ufl_Mappings_Lookup;
		if(count($ufl_Mappings_Lookup[$mapType]) == 0){
			
			if($mapType == 'injury')
				$sql = "SELECT ID, name FROM fof_injuries ORDER BY ID";
			else
				$sql = "SELECT ID, " . $mapType . " 
									FROM fof_mappings 
								 WHERE length(" . $mapType . ") > 0
						  ORDER BY ID";
			$dataMgr = createNewDataManager();
			
			$qMapping = $dataMgr->runQuery($sql);
			
			
			while($row = mysql_fetch_row($qMapping)){
				$ufl_Mappings_Lookup[$mapType][$row[0]] = $row[1];
			}	
		}
	}
	
	//Don't call this function either.  Again, use the utility ones below
	function uflGetMapping($mapType,$x){
		global $ufl_Mappings_Lookup;
		
		uflLoadMapping($mapType);
		
		if(array_key_exists($x,$ufl_Mappings_Lookup[$mapType]))
			return $ufl_Mappings_Lookup[$mapType][$x];
		else
			return "Non-existent mapping: " . $mapType . "(" . $x . ")";
	}
	
	
	function uflGetMapKey($mapType,$x){
		global $ufl_Mappings_Lookup; 
		
		uflLoadMapping($mapType);
		$map = $ufl_Mappings_Lookup[$mapType];
		return array_search($x,$map);
	}
	
													
	//UTILITY FUNCTIONS													 
	function mapGetPosition($x){
		return uflGetMapping("position",$x);
	}	
	
	function mapGetPositionGroup($x=''){
		if(is_numeric($x))
			return uflGetMapping("positionGroup",$x);
		else
			return $ufl_Mappings_Lookup["positionGroup"];
	}	
	
	function positionToPositionGroup($x){
		if(is_numeric($x))
			$x = mapGetPosition($x);
		
		switch($x){
		
			case 'FL':
			case 'SE':
				$group = 'WR';
				break;
			case 'LT':
			case 'RT':
				$group = 'T';
				break;
			case 'LG':
			case 'RG':
				$group = 'G';
				break;
			case 'LDE':
			case 'RDE':
				$group = 'DE';
				break;
			case 'LDT':
			case 'RDT':
			case 'NT':
				$group = 'DT';
				break;
			case 'SLB':
			case 'WLB':
				$group = 'OLB';
				break;
			case 'MLB':
			case 'SILB':
			case 'WILB':
				$group = 'ILB';
				break;
			case 'LCB':
			case 'RCB':
				$group = 'CB';
				break;
			case 'SS':
			case 'FS':
				$group = 'S';
				break;
			default:
				$group = $x;
		}
		
		return $group;
	}
	
	function positionGroupToMetaGroup($x){
	
		if(is_numeric($x))
			$x = mapGetPositionGroup($x);
		
		switch($x){
			case 'RB':
			case 'FB':
				$group = 'HB';
				break;
			case 'C':
			case 'G':
			case 'T':
				$group = 'OL';
				break;
			case 'DE':
			case 'DT':
				$group = 'DL';
				break;
			case 'ILB':
			case 'OLB':
				$group = 'LB';
				break;
			case 'CB':
			case 'S':
				$group = 'DB';
				break;
			default:
				$group = $x;
		}
		
		return $group;
	
	}
	
	function mapGetTransactionType($x){
		return uflGetMapping("transactionType",$x);
	}	
	
	function mapGetPrecipitation($x){
		return uflGetMapping("precipitation",$x);
	}	
	function mapGetPlayerStatus($x){
		return uflGetMapping("playerStatus",$x);
	}	
	function mapGetStaffRole($x){
		return uflGetMapping("staffRole",$x);
	}	
	function mapGetPlayoffs($x){
		return uflGetMapping("playoffs",$x);
	}	
	function mapGetAbility($x){
		return uflGetMapping("ability",$x);
	}	
	function mapGetStadiumType($x){
		return uflGetMapping("stadiumType",$x);
	}	
	function mapGetConstructionType($x){
		return uflGetMapping("constructionType",$x);
	}	
	function mapGetDriveResult($x){
		return uflGetMapping("driveResult",$x);
	}	
	function mapGetInjury($x){
		return uflGetMapping("injury",$x);
	}
	function unmapAbility($x){
		return uflGetMapKey("ability", $x);
	}
	function unmapStaffPositionToTransactionType($x){
		$val = 0;
		switch(strtolower($x)){
			case "head coach":
			case "hc":
			case "coach":
				$val = 11;
				break;
			case "offensive coordinator":
			case "oc":
				$val = 18;
				break;
			case "defensive coordinator":
			case "dc":
				$val = 19;
				break;
			case "scout":
			case "lead scout":
			case "ls":
				$val = 12;
				break;
		}
		return $val;
	}
	
?>