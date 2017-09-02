<?php

include_once(APP_ROOT . '/config.php');
include_once(DBMGR_ROOT . '/DataMgr.php');
include_once(APP_ROOT . '/func/leagueUtils.php');
include_once(OBJECT_ROOT . '/Transaction.php');

class CollegeIndex {
	
	static private $idx = array();
	
	static public function getById($id){
		
		if(!array_key_exists($id, self::$idx)){
			$db = getDBConnection();
			$sql = "SELECT Name FROM fof_colleges WHERE ID = $id";
			
			$qCollege = $db->runQuery($sql);
			if(mysql_num_rows($qCollege)){
				$row = mysql_fetch_array($qCollege);
				self::$idx[$id] = $row[0];
			}
			else
				self::$idx[$id] = "Invalid College ID: " . $id;
		}
		
		return self::$idx[$id];
		
	}
}

?>