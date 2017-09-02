<?php

include_once(APP_ROOT . '/config.php');
include_once(DBMGR_ROOT . '/DataMgr.php');
include_once(APP_ROOT . '/func/leagueUtils.php');

class Game {
	
	public $props;
	
	public function __construct($id)
	{
		
		$dataMgr = createNewDataManager();
		
		$sql = $this->getSql($id);
						 
		$qGame = $dataMgr->runQuery($sql);
			
		if(mysql_num_rows($qGame))
			$this->props = mysql_fetch_assoc($qGame);
		else
			$this->props = NULL;
			
		
	}

	
	public function getProp($prop)
	{
		if($this->props)
			return $this->props[$prop];
	}
	
	
	public function __destruct()
	{
		
	}
	
	
	public function getNewsFeed()
	{
		//TODO: get some cool stuff about the played game?  A summary or story
		return "";
	}
	
	
	public function getBoxAndLogLinks()
	{
		$links = createGameSummaryLinks($this->getProp('Year'),$this->getProp('Week'),$this->getProp('HomeTeam'),$this->getProp('AwayTeam'));
			
		return $links;
	}
	
	
	protected function getSql($id)
	{
		$sql = "SELECT a.*
						  FROM fof_gameresults a
						 WHERE a.id = ".$id;
						 
		return $sql;
	}	
	
} //end game class


class GameFuture extends Game {
	
	protected function getSql($id)
	{
		$sql = "SELECT a.*
						  FROM fof_teamschedule a
						 WHERE a.id = ".$id;
		
		return $sql;
		
	}	
	
	public function getNewsFeed()
	{
		return getNewsItem($this->getProp('Year'),$this->getProp('Week'),'gamePreview',$this->getProp('ID'));
	}
	
	
	public function getBoxAndLogLinks()
	{
		if($this->getProp('Away') == 0)
			$links = createGameSummaryLinks($this->getProp('Year'),$this->getProp('Week'),$this->getProp('TeamID'),$this->getProp('OpponentID'));
		else
			$links = createGameSummaryLinks($this->getProp('Year'),$this->getProp('Week'),$this->getProp('OpponentID'),$this->getProp('TeamID'));

		return $links;
	}
	
}

?>