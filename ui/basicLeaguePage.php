<?php

include_once(APP_ROOT . '/ctl/selector.php');
include_once(APP_ROOT . '/ui/page.php');

include_once(APP_ROOT . '/func/leagueUtils.php');


define('FLAG_INC_YEAR_SELECTOR', 0x01);
define('FLAG_INC_TEAM_SELECTOR', 0x02);
define('FLAG_INC_STAGE_SELECTOR', 0x04);


class BasicLeaguePage extends page{

	private $Navigation;

	public function __construct($flags=0x00){

		parent::__construct();

		$this->aNav = array('standings' => 'standings.php',
								'schedule' => 'schedule.php',
								'teams' => 'teams.php',
								'players' => 'player.php',
								'transactions' => 'transactions.php',
								'draft' => 'draft.php');

		$this->lge = getLeague();
		$this->aLge = $this->lge->getTeamIdsByDiv();

		$this->teamId = -1;
		$this->year = $this->lge->getCurYear();
		$this->stage = $this->lge->getCurStageID();
		$this->flags = $flags;
	}

	//turn on or off certain flags, ignoring others
	public function toggleFlags($flags, $bOptionOn=true){
		$this->flags = $bOptionOn ? ($this->flags | $flags) : ($this->flags & (~$flags));
	}

	//set flags equal to exactly what is passed in (or exactly opposite)
	public function setFlags($flags, $bOptionOn=true){
		$this->flags = $bOptionOn ? $flags : ~$flags;
	}
	
	public function setTeamId($teamId) {
		if($this->lge->validTeam($teamId))
			$this->teamId = $teamId;
	}

	public function setYear($year) {
		if($this->lge->validYear($year))
			$this->year = $year;
	}
	
	public function setStage($stage) {
		$this->stage = $stage;
	}
	
	public function pagePrint($content){

		$aSelectors = array();

		if($this->flags & FLAG_INC_TEAM_SELECTOR){
			$selector = new teamSelector($this->teamId);
			array_push($aSelectors,$selector);
		}
		if($this->flags & FLAG_INC_YEAR_SELECTOR){
			$selector = new yearSelector($this->year);
			array_push($aSelectors,$selector);
		}
		
		if($this->flags & FLAG_INC_STAGE_SELECTOR){
			$selector = new transStageSelector($this->stage);
			array_push($aSelectors,$selector);
		}
		
		

		$this->printHeader();
	
echo <<<END
		<!--body-->
			<tr>
				<td class="bodyContent">
					<div class="center">
				
					<!--subnav-->
END;

		if(count($aSelectors) > 0)
		{
			echo "
							<div align='right' style='border-style:solid;border:0px;border-color:#000000;float:right;'>";
									foreach($aSelectors as $selector){
										echo "<span style='margin-right:10px;'>";
										$selector->setNavOnChange(true);
										$selector->display();
										echo "</span>";
									}
							echo "</div>";
		}

		echo $content;

		$this->printFooter();
	}


	function printHeader(){

	$wwwRoot = WWW_ROOT;

	echo <<<END

			<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
			<html>
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
					<title>UFL - Ultimate Football League</title>
					
						<LINK href="$wwwRoot/style/default.css" rel="stylesheet" type="text/css">
						<script src="$wwwRoot/js/jquery-1.3.2.min.js"></script>
						<script src="$wwwRoot/js/utils.js"></script>
						<script src='js/commonJQ.js'></script>

END;
	
	if(checkAdminLevel(0)){	
		echo "<script src='js/AdminEdit.js'></script>";
	}
	else
	{
		echo "<script src='js/refreshNews.js'></script>";
	}

	echo <<<END
			</head>
				<body>

			<!--main table-->
			<table class="center" cellspacing="2" width="950">

			<!--banner and main nav-->
			<tr>
				<td class="hbf">
					<table cellspacing="2" cellpadding="2" width="100%">

					<!--banner-->
					<tr>
						<td class="inner">
							<table width="100%">
								<tr>
END;
							
								$confCount = 0;
								
								foreach($this->aLge as $cid => $conf){
									
									if($confCount == 0){
										$tableAlign = "left";
									}
									else {
										$tableAlign = "right";
									}
									
									
									echo "<td align='$tableAlign' width='50%'>";
									echo "<table>";
									
									$divCount = 0;
									
									foreach($conf as $did => $div){
									
										if(($divCount)%2 == 0){
											echo "<tr>";
										}
										
										$confClass = "teamNav" . $this->lge->getConferenceAbbrev($cid);
										$divClass = $this->lge->getConferenceAbbrev($cid) . $this->lge->getDivisionName($cid,$did);
										
										echo "<td>";
										echo "<table class='$confClass $divClass' cellpadding=0>";
										echo "<tr>";
										
											foreach($div as $tindex => $team){
												
												echo "<td><a href='teams.php?teamId=" . $team->getId() . "'><img class='teamNavImg' src='" . $team->getSmallLogoImg() . "' title='" . $team->getFullName() . "'></a></td>";
											}
										
										echo "</tr>";
										echo "</table>";
										echo "</td>";
										
										
										if(($divCount + 1)%2 == 0){
											echo "</tr>";
										}	
										
										$divCount = $divCount + 1;
										
									}
									
									
									echo "</table>";
									echo "</td>";
									
									if($confCount == 0){
										echo "<td align='center'>";
										echo "<a href='".WWW_ROOT."'>";
										echo "<img src='".IMAGE_ROOT."/uflLogo.png'>";
										echo "</a>";
										echo "</td>";
									}
									
									$confCount = $confCount + 1;
									
								}
								
							
echo <<<END
						</tr>
					</table>
				</td>
			</tr>
			<!--end banner-->
			<!--main nav-->
			<tr>
				<td colspan="2" bgcolor="#333333" class="navMain">
END;
						foreach ($this->aNav as $i => $page) {
						    echo "<span><a href='" . WWW_ROOT . "/" . $page . "'";
							if(basename($_SERVER['PHP_SELF']) == $page)
								echo " class='active'";
							echo ">" . $i . "</a></span>";
						}
echo <<<END
				</td>
			</tr>
				
			</table>
		<!--end main nav--> 
		</td>
	</tr>
	<!--end banner and main nav-->
END;

	}

	function printBody($content){


	}


	function printFooter(){
	
	$test = 1;

$solRoot = SOLECISMIC_ROOT;	
echo <<<END
					<br/><a href="$solRoot" target="new">Other League Files</a>
					</div>			
					</td>
				</tr>
				<!--end body-->
				
				<!--footer-->
				<tr>
					<td class="footerContent">Copyright&copy;2008. All rights reserved.<br/>Site designed by Chad Winter<br/>All teams designed by Clif Winter and Chad Winter</td>
					
				</tr>
				</table>
				<!--end main table-->
			</body>
		</html>
END;
	}

}



?>