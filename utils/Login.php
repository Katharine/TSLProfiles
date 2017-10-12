<?php
class Login
{
	const PASSWORD_SALT = 'dqvSKnYL9JbiRigBmGCOP91BOZtdu0UBri0rtr18g7nKB8m6fnyR6xI6Mhcx3Zg';
	const TWITTER_HASH = 'spouDrlevoariumletroEstlapo$s7lediegl+s-lU&#Oa-iazlec7uxo2stou-l';
	
	// These make up a bitfield.
	const SPECIAL_NONE		= 0x0;
	const SPECIAL_ADMIN		= 0x1;
	const SPECIAL_MODERATOR	= 0x2;
	
	private $link;
	private $_name;
	private $_pass;
	private $_id = 0;
	private $_loggedin = false;
	private $_track;
	private $_twitterenabled;
	private $_twittername;
	private $_twitterpass;
	private $_status;
	private $_special;
	private $_flickrtoken;
	private $_flickrtags;
	private $_flickrprivacy;
	private $_flickrpostags;
	
	public $profile;

	private function setcookies($id, $pass, $real = true)
	{
		$pass = $this->md52key($pass);
		if($real)
		{
			setcookie('sid', $pass,time()+(86400*31),'/');
			setcookie('uid', $id,  time()+(86400*31),'/');
		}
		$_COOKIE['sid'] = $pass;
		$_COOKIE['uid'] = $id;
	}

	private function key2md5($key)
	{
		return str_replace('-','',base64_decode(str_pad($_COOKIE['sid'],(ceil(strlen($_COOKIE['sid'])/3)*3),'=',STR_PAD_RIGHT)));
	}
	
	private function md52key($md5)
	{
		return rtrim(base64_encode($md5),'=');
	}
	
	private function pass2md5($pass)
	{
		return md5(self::PASSWORD_SALT.$pass);
	}

	private function checkcookies($fake = false)
	{
		if(($fake !== false) || (isset($_COOKIE['uid']) && isset($_COOKIE['sid'])))
		{
			$this->_id = (int)(($fake===false)?$_COOKIE['uid']:$fake);
			$this->_pass = $this->key2md5(($fake===false)?$_COOKIE['sid']:'');
			$results = $this->link->query("
				SELECT 
					CONCAT(`first`,' ',`last`) AS `name`, 
					`password`, 
					`track`, 
					`twitterenabled`,
					`twitteremail`,
					`special`,
					AES_DECRYPT(`twitterpassword`,'".self::TWITTER_HASH."') AS `twitterpass`,
					`flickrtoken`,
					`flickrtags`,
					`flickraccess`,
					`flickrpostags`
				FROM `users`
				WHERE `id` = '{$this->_id}'".(($fake===false)?" AND `password` = '{$this->_pass}'":''),
				true
			);
			if(mysql_num_rows($results) == 1)
			{
				$data = mysql_fetch_assoc($results);
				$this->_name = $data['name'];
				$this->_pass = $data['password'];
				$this->_track = (bool)$data['track'];
				$this->_loggedin = ($fake === false);
				$this->_twitterenabled = (bool)$data['twitterenabled'];
				$this->_twittername = $data['twitteremail'];
				$this->_twitterpass = $data['twitterpass'];
				$this->_special = (int)$data['special'];
				$this->_flickrtoken = $data['flickrtoken'];
				$this->_flickrtags = $data['flickrtags'];
				$this->_flickrprivacy = $data['flickraccess'];
				$this->_flickrpostags = $data['flickrpostags'];
				$this->profile = new Profile($this->link, $this->_id);
			}
			else
			{
				$this->_loggedin = false;
			}
		}
	}

	public function __construct(MySQL $link, $forcelogin = false)
	{
		$this->link = $link;
		$this->checkcookies($forcelogin);
	}
	
	public function __get($var)
	{
		switch($var)
		{
			case 'hasflickrauth':
				return $this->_flickrtoken != '';
			case 'key':
				return $this->md52key($this->_pass);
			case 'admin':
				return $this->_loggedin && ($this->_special & self::SPECIAL_ADMIN);
			case 'mod':
				return $this->_loggedin && (($this->_special & self::SPECIAL_ADMIN) || ($this->_special & SPECIAL_MODERATOR));
				
		}
		$real = "_{$var}";
		if(isset($this->$real))
		{
			return $this->$real;
		}
	}
	
	public function setflickrtoken($token)
	{
		if(!$this->_loggedin)
		{
			throw new Exception("User not logged in!");
		}
		$this->link->query("
			UPDATE `users`
			SET `flickrtoken` = '".$this->link->escape($token)."'
			WHERE `id` = '{$this->_id}'"
		);
		$this->_flickrtoken = $token;
	}
	
	public function clearflickrtoken()
	{
		$this->setflickrtoken('');
	}
	
	public function getflickrtoken()
	{
		return $this->_flickrtoken;
	}
	
	public function usetwitter()
	{
		return $this->_twitterenabled;
	}
	
	public function twitterlogin()
	{
		return $this->_twittername.':'.$this->_twitterpass;
	}
	
	public function settwitter($enabled)
	{
		$enabled = (int)$enabled;
		$this->link->query("
			UPDATE `users`
			SET `twitterenabled` = '{$enabled}'
			WHERE `id` = {$this->_id}"
		);
		$this->_twitterenabled = (bool)$enabled;
	}
	
	public function settwitterlogin($email, $pass)
	{
		$this->_twittername = $email;
		$this->_twitterpass = $pass;
		$this->link->query("
			UPDATE `users`
			SET
				`twitteremail` = '".$this->link->escape($email)."', 
				`twitterpassword` = AES_ENCRYPT('".$this->link->escape($pass)."','".self::TWITTER_HASH."')
			WHERE `id` = {$this->_id}"
		);
	}

	public function authenticate($first, $last, $password, $setcookie = true)
	{
		$first = $this->link->escape($first);
		$last = $this->link->escape($last);
		$password = md5(self::PASSWORD_SALT.$password);
		$results = $this->link->query("
			SELECT `id`
			FROM `users`
			WHERE `first` = '{$first}' AND `last` = '{$last}' AND `password` = '{$password}'",
			true
		);
		if(mysql_num_rows($results) == 1)
		{
			$this->setcookies(mysql_result($results,0,'id'), $password, $setcookie);
			$this->checkcookies();
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function settrack($track)
	{
		$this->_track = (bool)$track;
		$this->link->query("
			UPDATE `users`
			SET `track` = '".(int)$this->_track."'
			WHERE `id` = '{$this->_id}'"
		);
		return $this->_track;
	}
	
	public function setpass($password)
	{
		$password = $this->pass2md5($password);
		$this->_pass = $password;
		$this->link->query("
			UPDATE `users`
			SET `password` = '{$password}'
			WHERE `id` = '{$this->_id}'"
		);
		return true;
	}

	public function create($first, $last, $key, $password)
	{
		$first = $this->link->escape($first);
		$last = $this->link->escape($last);
		$key = $this->link->escape($key);
		$password = $this->pass2md5($password);
		$this->link->query("
			INSERT IGNORE INTO `users` (`first`,`last`,`key`,`password`,`joinedtslp`)
			VALUES (
				'{$first}',
				'{$last}',
				'{$key}',
				'{$password}',
				NOW()
			)"
		);
		if($this->link->numrows() > 0)
		{
			$id = $this->link->id();
			$server = new Server($this->link);
			$sljoin = $this->link->escape($server->requestdata(Server::DATA_BORN, $key));
			$this->link->query("
				UPDATE `users`
				SET `joinedsl` = '{$sljoin}'
				WHERE `id` = {$id}"
			);
			$this->link->query("
				SELECT `field`,`value`
				FROM `defaultfields`"
			);
			while($row = $this->link->fetchrow())
			{
				$this->link->query("
					INSERT INTO `fields` (`user`, `field`, `value`)
					VALUES (
						'{$id}',
						'".$this->link->escape($row['field'])."',
						'".$this->link->escape($row['value'])."'
					)"
				);
			}
			return true;
		}
		else
		{
			return false;
		}
	}

	public function logout()
	{
		setcookie('sid','',time()-10,'/');
		setcookie('uid','',time()-10,'/');
		unset($_COOKIE['sid']);
		unset($_COOKIE['uid']);
		$this->_loggedin = false;
	}

	public function apiauth($id, $key)
	{
		$_COOKIE['uid'] = (int)$id;
		if($key == self::PASSWORD_SALT)
		{
			$this->link->query("SELECT `password` FROM `users` WHERE `id` = '{$_COOKIE['uid']}'");
			if($this->link->numrows() > 0)
			{
				$_COOKIE['sid'] = $this->md52key($this->link->result(0,0));
			}
			else
			{
				$_COOKIE['sid'] = '';
			}
		}
		else
		{
			$_COOKIE['sid'] = $key;
		}
		$this->checkcookies();
		return $this->_loggedin;
	}
	
	public function getmutelist()
	{
		$this->link->query("
			SELECT `banned` AS `id`, (SELECT CONCAT(`first`,' ',`last`) FROM `users` WHERE `users`.`id` = `banlist`.`banned`) AS `name`
			FROM `banlist`
			WHERE `user` = '{$this->_id}'"
		);
		$banned = array();
		while($row = $this->link->fetchrow())
		{
			$banned[] = array(
				'id' => $row['id'],
				'name' => $row['name']
			);
		}
		return $banned;
	}
	
	public function unmute($id)
	{
		$this->link->query("
			DELETE FROM `banlist`
			WHERE `user` = '{$this->_id}' AND `banned` = '".(int)$id."'"
		);
	}
	
	public function mute($id)
	{
		$this->link->query("
			INSERT IGNORE INTO `banlist` (`user`,`banned`)
			VALUES (
				'{$this->_id}',
				'".(int)$id."'
			)"
		);
	}
	
	public function setflickrtags($tags)
	{
		$this->link->query("
			UPDATE `users`
			SET `flickrtags` = '".$this->link->escape($tags)."'
			WHERE `id` = '{$this->_id}'"
		);
	}
	
	public function setflickrprivacy($privacy)
	{
		$this->link->query("
			UPDATE `users`
			SET `flickrprivacy` = '".$this->link->escape($privacy)."'
			WHERE `id` = '{$this->_id}'"
		);
	}
}
?>