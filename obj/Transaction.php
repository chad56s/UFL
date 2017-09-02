<?php


include_once(DBMGR_ROOT . '/DataMgr.php');
include_once(OBJECT_ROOT . '/Player.php');
include_once(OBJECT_ROOT . '/Staff.php');


class Transaction
{
	
	protected $year;
	protected $transStage;
	protected $transType;
	protected $teamId;
	protected $playerId; 
	
	protected $player;
	
	protected $dataMgr;
	
	protected $qTransaction;
	
	/*
	 * construct takes year, transaction stage (fof_stagenames.transactionStage), and transaction type (optional)
	 * it DOES NOT take weeks!!!!
	 */
	public function __construct(){
		
		$this->dataMgr = createNewDataManager();
		$this->player = null;
	}
	
	
	//An easier function to call for player transactions
	public function getPlayerTransactions($player){
		$this->player = $player;
		$this->getTransactions(-1,-1,-1,-1,$player->getPlayerID());
	}
	
	
	public function getTransactions($year=-1,$transStage=-1,$teamId=-1,$transType=-1,$playerId=-1){
		
		$this->year = intval($year);
		$this->transStage = intval($transStage);
		$this->teamId = intval($teamId);
		$this->transType = intval($transType);
		$this->playerId = intval($playerId);
		
		$sql = "
			SELECT a.type, a.team1Id, a.team2Id, a.position, a.years, a.salary,
						 a.stage, a.season, a.playerId, b.cityName, c.stageName, d.abbrev
			  FROM fof_transactions a
		LEFT JOIN fof_teams b
				 ON a.team1Id = b.ID
			  JOIN (SELECT DISTINCT stageIndex, stageName, transactionStage FROM fof_stagenames) c
				 ON a.stage = c.transactionStage
		LEFT JOIN fof_teams d
	 			 ON a.team2Id = d.ID
			 WHERE 1=1";
			 
		if($this->year >= 0)
			$sql = $sql . " AND a.season = " . $this->year;
			 
		if($this->transStage >= 0)
			$sql = $sql .  " AND a.stage = " . $this->transStage;
			
		if($this->transType >= 0)
			$sql = $sql . " AND a.type = " . $this->transType;
		else
			$sql = $sql . " AND a.type != 28"; //don't get injuries unless specificially requested
		
		if($this->teamId >= 0){
			$sql = $sql . " AND (a.team1Id = " . $this->teamId . "
										  		 OR (a.team2Id = " . $this->teamId . "
											     		AND (a.type = 4 OR a.type = 14 OR a.type = 15)
												 	 )
											)";
		}
		if($this->playerId >= 0) {
			$sql = $sql . " AND a.playerId = " . $this->playerId;
			$sql = $sql . " AND a.type != 23";
		}
			
		$sql = $sql . $this->getOrderByClause();
		$this->qTransaction = $this->dataMgr->runQuery($sql);

	}
	
	
	public function printTransactions(){

		echo "<table class='dataTable center' style='width:100%;'>";
		echo "<tr><th colspan=99 class='lvl1'>";
		
		if($this->teamId >= 0){
			$ufl = getLeague();
			echo $ufl->getTeamCity($this->teamId);
		}
		elseif(is_object($this->player))
			echo $this->player->getPlayerName();
		else	
			echo "UFL League";
			
		echo " " . $this->getTableTitleString() . "</th></tr>";
		
		echo "<tr>
						<th class='lvl3'>Year</th>
						<th class='lvl3'>Stage</th>
						<th class='lvl3'>Team</th>";
						if($this->playerId < 0)
							echo "<th class='lvl3'>Player</th>";	
		echo		"<th class='lvl3'>Pos</th>
						<th class='lvl3'>" . $this->getTransactionColumnTitle() . "</th>
						<th class='lvl3'>" . $this->getTermsColumnTitle() . "</th>
					</tr>";
		
		if(mysql_num_rows($this->qTransaction) > 0){			
			while($row = mysql_fetch_object($this->qTransaction)){
				
				if($row->playerId != 0)
					$person = new Player($row->playerId);
				else if($row->type < 29)//renovation, construction, city move (yikes!)
					$person = getStaffByTransaction($row);
				else
					$person = null;
				
				echo "<tr><td>";
				echo $row->season;
				echo "</td><td>";
				echo $row->stageName;
				echo "</td><td>";
				echo $row->cityName;
				
				if($this->playerId < 0){
					echo "</td><td>";
					
					if(is_object($person))
						echo $person->getLink(); 
				}
				
				echo "</td><td>";
				if(get_class($person) == 'Player')
					echo mapGetPositionGroup($row->position);
				elseif(is_object($person))
					echo (get_class($person));
					
				echo "</td><td>";
				$transType = $this->translateType($row);
				echo $transType;
				echo "</td><td>";
				
				$transTerms = $this->translateTerms($row, $person);
				echo $transTerms;
				echo "</td></tr>";
			
			}
		}
		else{
			echo "<tr><td colspan=99 style='text-align:center;'><em>No " . $this->getTableTitleString() . "</em></td></tr>";
		}
		
		echo "</table>";
	}
		
	public function __destruct(){
		
	}
	
	/*
	 * This function takes a row from the query and determines what should be displayed
	 * as the transaction type based on type id, etc.  Returns an english representation
	 * of the pertinent details of the transaction
	 */
	protected function translateType($r){
		$trans = mapGetTransactionType($r->type);
		
		//see if we need to append team2's abbreviation
		if(	$r->type == 4 || 
				$r->type == 5 || 
				$r->type == 15)
				$trans = $trans . " " . $r->abbrev;
				
		//see if we need to append position information
		if($r->type == 26)
			$trans = $trans . " " . mapGetPosition($r->team2Id) . " from " . mapGetPosition($r->years);
			
		return $trans;
	}
	
		/*
	 * This function takes a row from the query and returns the terms of the transaction
	 */
	protected function translateTerms($r,$person){
		$terms = "";
		
		//for now, just assume salary means there's a contract involved
		if($r->type > 28){ //construction, renovation, move
			if($r->team2Id)
				$terms = "succeeded";
			else
				$terms = "failed";
		}
		if($r->salary > 0){
			if($r->salary >= 100)
				$terms = "$". $r->salary / 100 . " mil. ";
			else	
				$terms = "$" . number_format($r->salary * 10000);
				
				
			$terms = $terms . " " . $r->years . " yr";
			if($r->years > 1)
				$terms = $terms . "s";
				
		}
		if($r->playerId == 0 && $row->type < 29 && is_object($person)){//staff of some kind
			$staffTerms = $person->getTermsByYear($this->year);
			$terms = "$" . number_format($staffTerms->Price) . " " . $staffTerms->Years . " yr";
			if($staffTerms->years > 1)
				$terms = $terms . "s";
			
		}
		
		return $terms;
	}
	
	
	protected function getTableTitleString(){ return "Transactions"; }
	protected function getTransactionColumnTitle(){ return "Transaction"; }
	protected function getTermsColumnTitle(){ return "Terms"; }
	protected function getOrderByClause(){ return " ORDER BY a.season, c.stageIndex, a.id, b.cityName, type, position"; }
	
}


class Injury extends Transaction {
	
	public function __construct(){
		parent::__construct();
	}
	
	
	public function getPlayerInjuries($player){
		$this->player = $player;
		$this->getInjuries(-1,-1,-1,$player->getPlayerID());
	}
	
	public function getInjuries($year=-1,$transStage=-1,$teamId=-1,$playerId=-1){
		$this->getTransactions($year,$transStage,$teamId,28,$playerId);
	}
	
	public function printInjuries(){
		$this->printTransactions();
	}
	
	public function __destruct(){
		parent::__destruct();
	}

	/*
	 * This function takes a row from the query and determines what should be displayed
	 * as the transaction type based on type id, etc.  Returns an english representation
	 * of the pertinent details of the transaction
	 */
	protected function translateType($r){
		$trans = mapGetInjury($r->years);
		
		return $trans;
	}
	
		/*
	 * This function takes a row from the query and returns the terms of the transaction
	 */
	protected function translateTerms($r){
		$terms = "";
		
		$terms = $this->getInjurySeverity($r->salary % 1000);
		
		return $terms;
	}
	
	
	
	protected function getTableTitleString(){ return "Injuries"; }
	protected function getTransactionColumnTitle(){ return "Injury"; }
	protected function getTermsColumnTitle(){ return "Severity"; }
	protected function getOrderByClause(){ return " ORDER BY a.season, c.stageIndex, b.cityName, a.id, type, position"; }
	
	private function getInjurySeverity($x){
		$sev = "";
		switch ($x){
			case 1:
				$sev = "moderate";
				break;
			case 2:
				$sev = "mild";
				break;
			case 3:
				$sev = "serious";
				break;
			case 4:
				$sev = "very serious";
				break;
			case 5:
				$sev = "career threatening";
				break;
			case 6:
				$sev = "reaggravation";
				break;
			default: $sev = $x;
		}
		return $sev;
	}
}

function getLastTransactionStage(){
	$dataMgr = createNewDataManager();
	
	$sql = "SELECT stage
					  FROM fof_transactions
					 WHERE ID = (SELECT MAX(ID) FROM fof_transactions)";
	
	$q = $dataMgr->runQuery($sql);
	$row = mysql_fetch_row($q);
	return($row[0]);
	
}

?>