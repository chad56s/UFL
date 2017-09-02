<?php

include_once(APP_ROOT . '/config.php');
include_once(DBMGR_ROOT . '/DataMgr.php');
include_once(APP_ROOT . '/func/leagueUtils.php');
include_once(OBJECT_ROOT . '/League.php');

class Staff {
	
	private $dataMgr;
	protected $props;
	
	protected $qTransactions;
	
	public function __construct(){
		$this->qTransactions = null;
	}
	
	public function getProp($prop){
		if($this->props)
			return $this->props[$prop];
	}
	
	public function getName(){
		return $this->getProp('Name');
	}
	
	
	public function getLink(){
		return $this->getName();
	}
	
	
	public function printTransactions(){
		$qTransaction = $this->getTransactions();
		$ufl = new League();

		echo "<table class='dataTable center'>";
		echo "<tr><th colspan=99 class='lvl1'>";
		
		echo $this->getName();
		
		echo " Transactions</th></tr>";
		
		echo "<tr>
						<th class='lvl3'>Year</th>
						<th class='lvl3'>Team</th>
						<th class='lvl3'>Position</th>
						<th class='lvl3'>Terms</th>
					</tr>";
		while($row = mysql_fetch_object($qTransaction)){				
			echo "<tr><td>";
			echo $row->Season;
			echo "</td><td>";
			echo $ufl->getTeamCity($row->TeamID);

			echo "</td><td>";
			echo mapGetTransactionType($row->Type); 
			echo "</td><td>";
			echo $row->Years . ' years $' . number_format($row->Price,0,'.',',');
			echo "</td></tr>";
		
		}
		echo "</table>";
	}
	
	public function __destruct(){
		
	}
	
	//helper function for the transactions page since terms are kept in a seperate table for staff
	public function getTermsByYear($y){
		$q = $this->getTransactions();
		
		mysql_data_seek($q,0);
		
		$terms = null;
		
		do{
			$terms = mysql_fetch_object($q);	
		} while($terms && $terms->Season != $y);
		
		return $terms;
	}
	
		
	//helper function for the transactions page since terms are kept in a seperate table for staff
	public function getPosAbbrevByYear($y){
		$q = $this->getTransactions();
		
		if(mysql_data_seek($q,0)){
			
			do{
				$pos = mysql_fetch_object($q);	
			} while($pos && $pos->Season != $y);

			//var_dump($pos);
			
			switch($pos->Type){
				case 11:
					$posString = "HC";
					break;
				case 12:
					$posString = "Ld. Sct.";
					break;
				case 18:
					$posString = "OC";
					break;
				case 19:
					$posString = "DC";
					break;
				default:
					$posString = $pos->Type;
					break;
			}
		}
		
		return $posString;
		
	}
	
	protected function getTransactions(){
		if($this->qTransactions == null)
			$this->qTransactions = $this->queryTransactions();
			
		return $this->qTransactions;
	}
	
} //end staff class

class Coach extends Staff {
	public function __construct($id){
		parent::__construct();
		
		$this->dataMgr = createNewDataManager();
		$sql = "SELECT * FROM fof_coaches WHERE ID = " . $id;
		$qStaff = $this->dataMgr->runQuery($sql);
		if(mysql_num_rows($qStaff)){
			$this->props = mysql_fetch_assoc($qStaff);
		}
	}
	
	public function getLink(){
		$lnk = sprintf("<a href='staff.php?coachID=%d'>%s</a>",$this->getProp('ID'),$this->getName());
		return $lnk;
	}
	
	protected function queryTransactions(){
		$sql = "SELECT b.Season, b.TeamID, b.Type, b.Years, b.Price
						  FROM fof_transactions_coaches b
						 WHERE b.CoachID = " . $this->getProp("ID");
	
		$qTransactions = $this->dataMgr->runQuery($sql);
		
		return $qTransactions;
	}
	
}

class Scout extends Staff {
	public function __construct($id){
		parent::__construct();
		
		$this->dataMgr = createNewDataManager();
		$sql = "SELECT * FROM fof_scouts WHERE ID = " . $id;
		$qStaff = $this->dataMgr->runQuery($sql);
		if(mysql_num_rows($qStaff)){
			$this->props = mysql_fetch_assoc($qStaff);
		}
	}


	
	public function getLink(){
		$lnk = sprintf("<a href='staff.php?scoutID=%d'>%s</a>",$this->getProp('ID'),$this->getName());
		return $lnk;
	}


	protected function queryTransactions(){
		$sql = "SELECT b.Season, b.TeamID, '12' as Type, b.Years, b.Price
							FROM fof_transactions_scouts b
						 WHERE b.ScoutID = " . $this->getProp("ID");
	
		$qTransactions = $this->dataMgr->runQuery($sql);
		
		return $qTransactions;
	}
		
}

//This function takes a transaction row (as an object (mysql_fetch_object))
//and gets the staff member involved
function getStaffByTransaction($transaction){
	$dataMgr = createNewDataManager();
	$staff = null;
	if($transaction->type == 11 || $transaction->type == 18 || $transaction->type ==19){
		$sql = "SELECT CoachID FROM fof_transactions_coaches
		         WHERE Season = " . $transaction->season . "
						   AND Type = " . $transaction->type . "
							 AND TeamID = " . $transaction->team1Id . "
							 AND Years > 0";
		$qStaff = $dataMgr->runQuery($sql);
		if(mysql_num_rows($qStaff) == 1){
			$staffID = mysql_fetch_array($qStaff);
			$staff = new Coach($staffID[0]);
		}
else
echo $sql;

	}
	elseif($transaction->type == 12){
		$sql = "SELECT ScoutID FROM fof_transactions_scouts
		         WHERE Season = " . $transaction->season . "
							 AND TeamID = " . $transaction->team1Id . "
							 AND Years > 0";
		$qStaff = $dataMgr->runQuery($sql);
		if(mysql_num_rows($qStaff) == 1){
			$staffID = mysql_fetch_array($qStaff);
			$staff = new Scout($staffID[0]);
		}
	}
	return $staff;
}
?>