<?php


	include_once(OBJECT_ROOT . '/League.php');
	include_once(DBMGR_ROOT . '/DataMgr.php');
	include_once(APP_ROOT . '/func/leagueUtils.php');


class selector {
	protected static $static_bJsDisplayed = false;
	protected $startValue;  //holds the starting selected value
	protected $bNavOnChange; //if true, navigate to the containing page again with the url vars updated
	protected $ctlName; //name of the control on the page
	protected $allOptionDisplay; //if not empty, holds the display of the all option (e.g. League, or 'all transactions' or whatever)
	protected $allOptionValue; //if allOptionDisplay not empty, holds the value of the all option (e.g. League, or 'all transactions' or whatever)
	protected $varName; //the varname in the url (for autochange) or form
	
	protected $aVals; //the options for the select box. array key = option value; array value = option text
	
	public function __construct($startVal=null){
		$this->startValue = $startVal;
		
		$this->bNavOnChange = false;
		
		//defaults for all option
		$this->allOptionDisplay = "";
		$this->allOptionValue = "";
		
		$this->varName = "selectorValue";
		
		$this->aVals = array();
		
	}
	
	public function setOptions($aVals){
		$this->aVals = $aVals;
	}
	
	public function addOption($key,$val){
		$this->aVals[$key] = $val;
	}
	
	public function addOptions($aVals){
		$this->aVals = array_merge($this->aVals, $aVals);
	}
	
	public function setAllOption($val, $display){
		$this->allOptionValue = $val;
		$this->allOptionDisplay = $display;
	}
	
	public function setVarName($varName){
		$this->varName = $varName;
	}
	
	
	public function setNavOnChange($bNav){
		if(is_bool($bNav))
			$this->bNavOnChange = $bNav;
	}

	
	public function display(){
		
		$this->printJavascript();
		
		echo "<select name='" . $this->varName . "'";		
		if($this->bNavOnChange) echo " onchange=\"execSelect(this,'" . $this->varName . "');\"";
		echo ">";
		
		if(strlen($this->allOptionDisplay) > 0){
			echo "<option value='" . $this->allOptionValue . "'";
			if($this->startValue == $this->allOptionValue)
				echo " selected";
			echo">" . $this->allOptionDisplay . "</option>";		
			echo "<option value=''></option>";
		}
		
		$cnt = 0;
		while($row = $this->getNextVals())
		{
			$cnt = $cnt + 1;
			$val = $this->getOptionValue($row);
			$display = $this->getOptionDisplay($row);
			
			echo "<option value='" . $val . "'";
			if($this->startValue == $val)
				echo " selected";
			echo">" . $display . "</option>";
		}
		echo "</select>";
	
	}
	
	/* Leaving this function PUBLIC so maybe the control can be put on a page twice
	 * while leaving the printing of the javascript up to the calling code.
	 */
	public function printJavascript()
	{
		if(!self::$static_bJsDisplayed){
			self::$static_bJsDisplayed = true;
			echo "
				<script type=\"text/javascript\">
	
					function execSelect(selBox,varName)
					{
						var bFound = false;
						var base = '" . $_SERVER['PHP_SELF'] . "?';
						var query = window.location.search.substring(1);
						var aVars = query.split(\"&\");
						
						for (var i=0; i<aVars.length; i++){
							var pair = aVars[i].split('=');
							if(pair[0] == varName){
								aVars[i] = pair[0] + '=' + selBox.options[selBox.selectedIndex].value;
								bFound = true;
							}
						}
						
						if(!bFound)
							aVars.push(varName + '=' + selBox.options[selBox.selectedIndex].value);
							
						window.location.href= base + aVars.join('&');
					}
				</script>
			";
		}


	}
	
	protected function getOptionValue($record){
		if(!is_array($record))
			return "error";
		else
			return $record[0];
	}
	
	protected function getOptionDisplay($record){
		if(!is_array($record))
			return "error";
		else
			return $record[1];
	}
	
		
	protected function getNextVals(){
		return each($this->aVals);
	}
	
	
}//end selector class


class iteratorSelector extends selector {
	public function __construct($start,$end,$iterateBy,$selectedVal=null){
		parent::__construct($selectedVal);
		$error = false;
		
		//check for problems
		if($start > $end && $iterateBy > 0){
			$this->addOption("start should be less than end with positive iterator",0);
			$error = true;
		}
		elseif($start < $end && $iterateBy < 0){
			$this->addOption("start should be greater than end with negative iterator");
			$error = true;
		}
		if($iterateBy == 0){
			$this->addOption("iterator can't be 0");
			$error = true;
		}
		if(abs($start - $end) / abs($iterateBy) > 500){
			$this->addOption("iterator has greater than 500 options");
			$error = true;
		}
		
		//add the options
		if(!$error){
			$curVal = $start;
			while($curVal >= min($start,$end) && $curVal <= max($start,$end)){
				$this->addOption($curVal,$curVal);
				$curVal = $curVal + $iterateBy;
			}
		}
		
	}
}


class dbSelector extends selector {
	
	
	protected $dataMgr;
	protected $qData;
	protected $sql;
	
	public function __construct($startVal=null){
		
		parent::__construct($startVal);
		
		$this->dataMgr = createNewDataManager();
		$this->sql = "SELECT 'uninitialized','uninitialized' from dual";
		
	}
	
	public function display(){
		
		$this->executeQuery();
		mysql_data_seek($this->qData,0);
		parent::display();
		
	}

	
	protected function executeQuery(){
		$this->qData = $this->dataMgr->runQuery($this->sql);
	}
	
	protected function getNextVals(){
		return mysql_fetch_row($this->qData);
	}
		
}


class teamSelector extends dbSelector
{
	private $ufl;
	
	public function __construct($teamId='')
	{
		parent::__construct($teamId);
		$this->ufl = new League();
		
		
		$this->allOptionDisplay = "League";
		$this->allOptionValue = "";
		$this->varName = "teamId";
		
		$this->sql = "SELECT Id FROM fof_teams ORDER BY CityName,Nickname";
		
		
	}
	
	protected function getOptionDisplay($record){
		if(!is_array($record))
			return "error";
		else
			return $this->ufl->getTeamCity($record[0],true);
	}

}

class yearSelector extends selector
{
	private $startYear;
	private $endYear;
	private $curYear;
	
	public function __construct($year='')
	{
		parent::__construct($year);
		
		$this->allOptionDisplay = "";
		$this->allOptionValue = "";
		$this->varName = "year";
		
		$db = createNewDataManager();
		$sql = "SELECT startYear, curYear FROM fof_gameinfo";
		$qData = $db->runQuery($sql);

		$row = mysql_fetch_row($qData);
		$this->startYear = $row[0];
		$this->endYear = $row[1];
		$this->curYear = $this->startYear;		
		
	}

	protected function getNextVals(){
		if($this->curYear <= $this->endYear){
			echo $this->curYear;
			$returnVal = array($this->curYear,$this->curYear);
			$this->curYear = $this->curYear + 1;
		}
		else{
			$returnVal = null;
		}
		
		return $returnVal;
	}
	
}

class transStageSelector extends dbSelector
{
	
	public function __construct($stage='')
	{
		parent::__construct($stage);
		
		$this->allOptionDisplay = "All Stages";
		$this->allOptionValue = "-1";
		$this->varName = "stage";
		
		$this->sql = "SELECT 'current', 'Current Stage', -100 as stageIndex FROM dual
									UNION ALL
									SELECT distinct transactionStage, stageName, stageIndex FROM fof_stagenames WHERE transactionStage not in (0,400) ORDER BY stageIndex";		

	}
	
}

class positionGroupSelector extends dbSelector
{
	
	public function __construct($stage='')
	{
		parent::__construct($stage);
		
		$this->allOptionDisplay = "All Positions";
		$this->allOptionValue = "-1";
		$this->varName = "position";
		
		$this->sql = "SELECT id, positionGroup FROM fof_mappings WHERE id > 0 AND length(positionGroup) > 0 ORDER BY positionGroup";		
	}
	
}


?>