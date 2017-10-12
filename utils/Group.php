<?php
class Group
{
	private $link;
	private $_id;
	private $_name;
	private $_description;
	private $_founder;
	private $_open;
	private $_image;
	
	public function __construct(MySQL $link, $id)
	{
		$this->link = $link;
		if(is_numeric($id) && strpos($id,'.') === false)
		{
			$this->_id = $id;
			$results = $this->link->query("
				SELECT `name`,`description`,`founder`,`open`,`image`
				FROM `groups`
				WHERE `id` = '{$id}'",
				true
			);
			if(mysql_num_rows($results) > 0)
			{
				list(
					$name,
					$description,
					$founder,
					$open,
					$image
				) = mysql_fetch_row($results);
				$this->_name = $name;
				$this->_description = $description;
				$this->_founder = $founder;
				$this->_open = (bool)$open;
				$this->_image = $image;
			}
			else
			{
				throw new Exception("Unknown group ID!");
			}
		}
		else if(is_string($id))
		{
			$this->link->query("
				SELECT `id`
				FROM `groups`
				WHERE `slug` = '".self::Slug($id)."'"
			);
			if($link->numrows() > 0)
			{
				$this->__construct($link, $link->result(0,'id'));
			}
			else
			{
				throw new Exception("Unknown group slug!");
			}
		}
		else
		{
			throw new Exception("That's not an ID.");
		}
	}
	
	public function add($id, $admin = false, $addedby = false)
	{
		if($this->ismember($id))
		{
			throw new Exception("You can't add someone to a group they are already in.");
			return;
		}
		$permit = $this->open;
		$id = (int)$id;
		$admin = (int)$admin;
		if(!$permit && $addedby !== false)
		{
			$this->link->query("
				SELECT `admin`
				FROM `groupmembers`
				WHERE `id` = '".(int)$addedby."'
				LIMIT 1"
			);
			if($this->link->numrows() > 0 && $this->link->result(0,0))
			{
				$permit = true;
			}
			else
			{
				throw new Exception("User #{$addedby} is not authorised to add members to this group!");
			}
		}
		if($permit)
		{
			//$profile = new Profile($this->link, $id);
			$this->link->query("
				INSERT INTO `groupmembers` (`user`,`group`,`time`,`admin`)
				VALUES (
					'{$id}',
					'{$this->_id}',
					NOW(),
					'{$admin}'
				)"
			);
			return true;
		}
		else
		{
			throw new Exception("Cannot join group; access is restricted to invited members only.");
		}
	}
	
	public function remove($id, $remover)
	{
		$id = (int)$id;
		if(!$this->ismember($id))
		{
			throw new Exception("You can't remove someone from a group unless they were actually in the group!");
			return;
		}
		$remover = (int)$removed;
		$permitted = ($id == $remover);
		if(!$permitted)
		{
			$this->link->query("
				SELECT `admin`
				FROM `groupmembers`
				WHERE `user` = '{$remover}' AND `group` = '{$this->_id}'"
			);
			if($this->link->numrows() > 0 && $this->link->result(0,0) == 1)
			{
				$permitted = true;
			}
			else
			{
				throw new Exception("User {$remover} has no right to remove users from this group!");
			}
		}
		if($permitted)
		{
			$this->link->query("
				DELETE FROM `groupmembers`
				WHERE `user` = '{$id}' AND `group` = '{$this->_id}'"
			);
			$this->link->query("
				SELECT COUNT(`id`) FROM `groupmembers`
				WHERE `group` = '{$this->id}'"
			);
			if($this->link->result(0,0) == 0)
			{
				$this->delete();
			}
		}
	}
	
	public function members()
	{
		$this->link->query("
			SELECT `id`,`admin` FROM `groupmembers`
			WHERE `group` = '{$this->_id}'"
		);
		$members = array();
		while($row = $this->link->fetchrow())
		{
			$members[] = array(
				'id' => (int)$row['id'],
				'admin' => (bool)$row['admin']
			);
		}
		return $members;
	}
	
	public function ismember($id)
	{
		$id = (int)$id;
		$this->link->query("
			SELECT `id`
			FROM `groupmembers`
			WHERE `user` = '{$id}' AND `group` = '{$this->_id}'"
		);
		if($this->link->numrows() > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function isadmin($id)
	{
		$id = (int)$id;
		$this->link->query("
			SELECT `admin`
			FROM `groupmembers`
			WHERE `user` = '{$id}' AND `group` = '{$this->_id}'"
		);
		if($this->link->numrows() > 0 && $this->link->result(0,0))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function __get($name)
	{
		if($name == 'slug')
		{
			return self::Slug($name);
		}
		$name = "_{$name}";
		if(isset($this->$name))
		{
			return $this->$name;
		}
	}
	
	public function __set($name, $value)
	{
		$varname = "_{$name}";
		switch($name)
		{
		case 'id':
			throw new Exception("You can't change a group's ID.");
			break;
		case 'link':
			throw new Exception("You can't alter the MySQL link.");
			break;
		case 'founder':
			throw new Exception("You can't change who founded a group!");
			break;
		default:
			if(isset($this->$varname))
			{
				$this->query("
					UPDATE `groups`
					SET `{$name}` = '".$this->link->escape($value)."'
					WHERE `id` = '{$this->_id}'"
				);
				$this->$varname = $value;
			}
			else
			{
				throw new Exception("Trying to alter a nonexistent property!");
			}
			break;
		}
	}
	
	
	
	public static function Create(MySQL $link, $name, $description, $founder, $open, $image)
	{
		$link->query("
			INSERT INTO `groups` (`name`,`description`,`founder`,`open`,`image`)
			VALUES (
				'".$link->escape($name)."',
				'".$link->escape($description)."',
				'".(int)$founder."',
				'".(int)$open."',
				'".$link->escape($image)."'
			)"
		);
		if($link->numrows() > 0)
		{
			$id = $link->id();
			$link->query("
				INSERT INTO `groupmembers` (`user`,`group`,`admin`,`time`)
				VALUES (
					'".(int)$founder."',
					'{$id}',
					1,
					NOW()
				)"
			);
			return new self($link, $id);
		}
		else
		{
			throw new Exception("Group creation failed due to a duplicate name.");
		}
	}
	
	public static function Delete(MySQL $link, $id)
	{
		$id = (int)$id;
		$link->query("
			DELETE FROM `groupmembers`
			WHERE `group` = '{$id}'
		");
		$link->query("
			DELETE FROM `groups`
			WHERE `id` = '{$id}'
		");
	}
	
	public static function Slug($name)
	{
		return strtolower(preg_replace('/[^a-zA-Z0-9]+/','_',$name));
	}
}
?>