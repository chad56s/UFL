<?php


include_once('ctl/selector.php');

class page {

	private $Navigation;

	
	public function __construct(){

		$this->Navigation = array();

	}

	public function pagePrint($content){
		echo $content;
	}
	


}
