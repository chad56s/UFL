<?php
    
	include_once('config.php');

	include_once(UI_ROOT . '/basicLeaguePage.php');
	
	ob_start();

	//get the player attributes if playerId is defined
	if(isset($_GET['playerId']) AND $_GET['playerId'] > 0){
		
		include_once(OBJECT_ROOT . '/draft/playerProfile.php');
		
		$playerProf = new PlayerProfile($_GET['playerId']);
		
		$playerProf->printAttributes();	
	}
	
	else{
		
		include_once('header.php');
	
		include_once(OBJECT_ROOT . '/League.php');
		include_once(DBMGR_ROOT . '/DataMgr.php');
		
		$dmgr = createNewDataManager();
		$lge = new League();
		
		if($lge->getCurStageID() < 18) //league draft stage
			include_once('inc/draft/draftPreview.php');
		else
			include_once('inc/draft/draftReview.php');
		
		
		include_once('footer.php');

		$page = new BasicLeaguePage();
		$page->pagePrint(ob_get_clean());		
	} //end else

	
?>