<?php
class FriendsList
{
	private $link;
	private $login;
	private $list = array();
	
	public function __construct(MySQL $link, Login $login)
	{
		$this->link = $link;
		$this->login = $login;
		if(!$this->login->loggedin)
		{
			throw new Exception("You should be logged in before creating a FriendsList!");
		}
		$id = $this->login->id;
		$this->link->query("
			SELECT `friend` AS `key`, (
				SELECT CONCAT(`first`,' ',`last`)
				FROM `keys`
				WHERE `keys`.`key` = `friends`.`friend`
			) AS `name`
			FROM `friends`
			WHERE `user` = '{$id}'"
		);
		$this->list = array();
		while($row = $this->link->fetchrow())
		{
			$this->list[$row['key']] = $row['name'];
		}
	}
	
	public function getlist()
	{
		$ret = array();
		foreach($this->list as $key => $name)
		{
			$ret[] = array(
				'name' => $name,
				'key' => $key
			);
		}
		return $ret;
	}
	
	public function add($key)
	{
		$key = strtolower($key);
		if(strlen($key) != 36 || strlen(preg_replace('/[^a-f0-9]/i','',$key)) != 32)
		{
			throw new Exception("Invalid key!");
		}
		else
		{
			if(!isset($this->list[$key]))
			{
				$k = $this->link->escape($key);
				$this->link->query("
					SELECT CONCAT(`first`,' ',`last`) AS `name`
					FROM `keys`
					WHERE `key` = '{$k}'"
				);
				if($this->link->numrows() > 0)
				{
					$name = $this->link->result(0,'name');
					$this->list[$key] = $name;
					$this->link->query("
						INSERT INTO `friends` (`user`,`friend`)
						VALUES (
							'".$this->login->id."',
							'{$k}'
						)"
					);
				}
				else
				{
					throw new Exception("Unknown key.");
				}
			}
		}
	}
	
	public function remove($key)
	{
		$key = strtolower($key);
		if(isset($this->list[$key]))
		{
			unset($this->list[$key]);
			$this->link->query("
				DELETE FROM `friends`
				WHERE `user` = '".$this->login->id."' AND `friend` = '".$this->link->escape($key)."'"
			);
		}
	}
}
?>
