<?php
class MySQL
{
	const DATABASE_ADDRESS = 'localhost';
	const USER_NAME = 'tslprofiles';
	const USER_PASSWORD = '[redacted]';
	const DATABASE_NAME = 'tslprofiles';
	private $link = NULL;
	private $queries = 0;
	private $server;
	private $user;
	private $pass;
	private $db;
	private $results;
	private $numrows = 0;
	private $cloned = false;
	
	public function __construct($server = self::DATABASE_ADDRESS, $user = self::USER_NAME, $pass = self::USER_PASSWORD, $db = self::DATABASE_NAME)
	{
		try
		{
			$this->connect($server, $user, $pass);
			$this->setdb($db);
		}
		catch(Exception $e)
		{
			throw new Exception("Caught exception in ".__CLASS__." constructor: ".$e->getMessage());
		}
		return true;
	}

	public function __destruct()
	{
		$this->disconnect();
	}
	
	public function __clone()
	{
		$this->cloned = true;
	}
		
	public function connect($server, $user, $pass)
	{
		$link = @mysql_connect($server, $user, $pass, true);
		if(!is_resource($link))
		{
			throw new Exception("Could not connect to database at '{$link}'");
			return false;
		}
		$this->link = $link;
	}

	public function escape($string)
	{
		return mysql_real_escape_string($string, $this->link);
	}
	
	public function numrows()
	{
		return $this->numrows;
	}

	public function setdb($db)
	{
		if(!@mysql_select_db($db, $this->link))
		{
			throw new Exception("Could not set database to {$db}");
			return false;
		}
		return true;
	}

	public function query($query, $return = false)
	{
		$results = @mysql_query($query, $this->link);
		if(mysql_errno($this->link) != 0)
		{
			trigger_error("Error #".mysql_errno($this->link)." running MySQL query: ".htmlentities(mysql_error($this->link))."\n<br />Query: ".htmlentities($query), E_USER_WARNING);
			return false;
		}
		++$this->queries;
		$query = trim($query);
		$command = strtoupper(substr($query,0,strpos($query,' ')));
		if($command == 'SELECT')
		{
			if($return)
			{
				return $results;
			}
			else
			{
				$this->results = $results;
				$this->numrows = mysql_num_rows($results);
			}
		}
		else
		{
			if(!$return)
			{
				$this->numrows = mysql_affected_rows($this->link);
			}
			return true;
		}
	}

	public function fetchrow()
	{
		$return = @mysql_fetch_array($this->results);
		return $return;
	}

	public function result($row, $column)
	{
		return @mysql_result($this->results,$row,$column); // or throw new Exception("Error fetching result {$row},{$column}.");
	}

	public function id()
	{
		return mysql_insert_id($this->link);
	}

	public function disconnect()
	{
		if($this->cloned) return;
		mysql_close($this->link);
	}
}
?>
