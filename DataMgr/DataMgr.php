<?php

/*
 * DataMgr (data manager)
 * 
 * maintains a connection to a mySql database, performs queries and maintains the record set
 * NOW WITH MULTIPLE QUERY support!
 */

class DataMgr
{
	private $host;
	private $user;
	private $pass;
	private $db;
	
	private $sql;
	private $connection;
	private $results;
	
	/*
	 *	construct the datamanager object with a host, username, password and database name
	 */
	public function __construct($host, $user, $pass, $db)
	{
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->db = $db;
		
		$this->sql = NULL;
		$this->connection = NULL;
		$this->results = array();
		
		
		$this->connect();// or die ('cannot connect:');
	}
	
	
	public function connect()
	{
		//free results
		$this->freeResults();
		
		//close existing connection.  
		if($this->connection)
		{
			mysql_close($this->connection);
		}
		
		//open a new connection
		$this->connection = mysql_connect($this->host, $this->user, $this->pass) or die('ERROR: Unable to connect!');

		// select database
		mysql_select_db($this->db) or die('ERROR: Unable to select database!');
		
	}
	
	
	public function runQuery($q,$isUpdateOrInsert=false)
	{
			
		//store the query string	
		$this->sql = $q;
		//run the query string
		
		$newResults = array_push($this->results, mysql_query($this->sql)) or die ("Error in query:" . $this->sql. " " .mysql_error());
		
		return $this->results[$newResults-1];
	}
	
	
	public function freeResults()
	{
		//make sure the result is not a boolean from an update or insert operation
		foreach($this->results as $k => $rslt){
			if($rslt != NULL && gettype($rslt) != 'boolean'){
				mysql_free_result($rslt) or die(gettype($rslt));
			}
		}
			
		$this->results = array();
	}
	
	public function disconnect()
	{
		return;
		//TODO: why does closing the connection cause problems?
		// Create an object: $someDB = new Schedule;
		// Create the object again: $someDB = new Schedule; //again...
		// causes a problem.  mysql_close causes it...
		if($this->connection)
			mysql_close($this->connection);
			
		$this->connection = NULL;
		
	}
	
	
	public function __destruct()
	{
		$this->freeResults();
		$this->disconnect();
	}
}

?>