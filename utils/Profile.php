<?php
class Profile
{
	private $link;
	private $_id;
	private $_first;
	private $_last;
	private $_key;
	private $_sljoin;
	private $_tslpjoin;
	private $_gender;
	private $_track;
	private $_status;
	private $_grid;
	private $_twitterenabled;
	private $_twitteruser;
	private $_twitterpass;

	public function __construct(MySQL $link, $id)
	{
		$this->link = $link;
		if(is_numeric($id))
		{
			$this->_id = (int)$id;
			$results = $this->link->query("
				SELECT `first`,`last`,UNIX_TIMESTAMP(`joinedsl`) AS `sljoin`,UNIX_TIMESTAMP(`joinedtslp`) AS `tslpjoin`,`gender`,`key`,`track`,`status`,`grid`, `twitterenabled`,`twitteremail`,AES_DECRYPT(`twitterpassword`, '".Login::TWITTER_HASH."') AS `twitterpass`
				FROM `users`
				WHERE `id` = '{$this->_id}'",
				true
			);
			if(mysql_num_rows($results) > 0)
			{
				$data = mysql_fetch_assoc($results);
				$this->_first = $data['first'];
				$this->_last = $data['last'];
				$this->_key = $data['key'];
				$this->_track = $data['track'];
				$this->_sljoin = $data['sljoin'];
				$this->_tslpjoin = $data['tslpjoin'];
				$this->_gender = $data['gender'];
				$this->_status = (int)$data['status'];
				$this->_grid = strtolower($data['grid']);
				$this->_twitterenabled = (bool)$data['twitterenabled'];
				$this->_twitteruser = $data['twitteremail'];
				$this->_twitterpass = $data['twitterpass'];
			}
			else
			{
				throw new Exception("Attempted to load a Profile object for non-existent user #{$this->_id}!");
			}
		}
		else if(strlen($id) == 36 && strlen(preg_replace('/[^a-f0-9]/i','',$id)) == 32 && preg_replace('/[^-]/','',$id) == '----')
		{
			$this->_key = $id;
			$this->link->query("
				SELECT `id`
				FROM `users`
				WHERE `key` = '{$this->_key}'"
			);
			if($this->link->numrows() > 0)
			{
				$this->__construct($this->link, $this->link->result(0,'id'));
			}
			else
			{
				$this->link->query("
					SELECT `first`,`last`
					FROM `keys`
					WHERE `key` = '{$this->_key}'"
				);
				if($this->link->numrows() > 0)
				{
					$this->_first = $this->link->result(0,'first');
					$this->_last = $this->link->result(0,'last');
				}
				else
				{
					throw new Exception("Unknown key!");
				}
			}
		}
		else if(strpos($id,' ') > 0)
		{
			$name = explode(' ',$id);
			$this->link->query("SELECT `id` FROM `users` WHERE `first` = '".$this->link->escape($name[0])."' AND `last` = '".$this->link->escape($name[1])."'");
			if($this->link->numrows() > 0)
			{
				$this->__construct($this->link, $this->link->result(0,'id'));
			}
			else
			{
				$this->link->query("SELECT `key` FROM `keys` WHERE `first` = '".$this->link->escape($name[0])."' AND `last` = '".$this->link->escape($name[1])."'");
				if($this->link->numrows() > 0)
				{
					$this->__construct($this->link, $this->link->result(0,'key'));
				}
				else
				{
					throw new Exception("Unknown name '{$id}!'");
				}
			}
		}
		else
		{
			throw new Exception("Invalid key '{$id}'");
		}
	}
	
	public function __get($var)
	{
		switch($var)
		{
			case 'name':
				return "{$this->_first} {$this->_last}";
			case 'blog':
				return 'http://' . strtolower("{$this->_first}{$this->_last}") . '.' . BLOG_SITE . '/';
			case 'twitterlogin':
				return $this->_twitteruser.':'.$this->_twitterpass;
			case 'online':
				return $this->checkonline();
			case 'pos':
				return $this->getpos();
			case 'url':
				return "/profiles/".URL::clean($this->name)."/";
			case 'image':
				return $this->getimage();
		}
		$real = "_{$var}";
		if(isset($this->$real))
		{
			return $this->$real;
		}
		return null;
	}

	public function setpos($sim, LLVector3 $pos)
	{
		$this->link->query("
			UPDATE `users`
			SET `sim` = '".$this->link->escape($sim)."', `x` = '{$pos->x}', `y` = '{$pos->y}', `z` = '{$pos->z}'
			WHERE `id` = '{$this->_id}'"
		);
	}

	public function getpos()
	{
		if($this->_track)
		{
			$results = $this->link->query("
				SELECT `sim`,`x`,`y`,`z`
				FROM `users`
				WHERE `id` = '{$this->_id}'",
				true
			);
			if(mysql_num_rows($results) == 0)
			{
				throw new Exception("User #{$this->_id} lost!");
				return false;
			}
			else
			{
				return array(
					'sim' => mysql_result($results,0,'sim'),
					'pos' => new LLVector3(
						mysql_result($results,0,'x'),
						mysql_result($results,0,'y'),
						mysql_result($results,0,'z')
					)
				);
			}
		}
		else
		{
			return false;
		}
	}

	public function checkonline()
	{
		$server = new Server($this->link);
		return $server->requestdata(Server::DATA_ONLINE,$this->_key);
	}

	public function getfields()
	{
		$results = $this->link->query("
			SELECT `field`,`value`
			FROM `fields`
			WHERE `user` = '{$this->_id}'",
			true
		);
		$fields = array('Gender' => ucfirst($this->gender));
		while($row = mysql_fetch_assoc($results))
		{
			$fields[$row['field']] = $row['value'];
		}
		$this->link->query("
			SELECT `fieldorder`
			FROM `users`
			WHERE `id` = '{$this->_id}'"
		);
		$order = ($this->link->numrows()>0)?$this->link->result(0,0):'';
		if($order == '')
		{
			return $fields;
		}
		else
		{
			$newfields = array();
			$order = unserialize($order);
			ksort($order);
			foreach($order as $o)
			{
				if(isset($fields[$o]))
				{
					$newfields[$o] = $fields[$o];
					unset($fields[$o]);
				}
			}
			$fields = array_merge($newfields,$fields);
			return $fields;
		}
	}

	public function addfield($field)
	{
		$this->link->query("
			INSERT IGNORE INTO `fields` (`user`,`field`)
			VALUES (
				'{$this->_id}',
				'".$this->link->escape($field)."'
			)"
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

	public function deletefield($field)
	{
		$this->link->query("
			DELETE FROM `fields`
			WHERE `user` = '{$this->_id}' AND `field` = '".$this->link->escape($field)."'"
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

	public function setgender($value)
	{
		$this->link->query("
			UPDATE `users`
			SET `gender` = '".$this->link->escape(strtolower($value))."'
			WHERE `id` = '{$this->_id}'",
			$link
		);
		$this->_gender = strtolower($value);
		return true;
	}

	public function updatefield($field, $value)
	{
		if(trim($value) == '')
		{
			throw new Exception("Fields cannot be blank.");
		}
		else
		{
			if(strtolower($field) == 'gender')
			{
				return $this->setgender($value);
			}
			else
			{
				$this->link->query("
					UPDATE `fields`
					SET `value` = '".$this->link->escape($value)."'
					WHERE `field` = '".$this->link->escape($field)."' AND `user` = '{$this->_id}'"
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
		}
	}

	public function addcomment($user, $comment)
	{
		//
	}

	public function setrating($from, array $ratings)
	{
		$from = (int)$from;
		if($from == $this->_id)
		{
			throw new Exception("A user tried to rate themselves!");
		}
		else
		{
			if(mysql_num_rows($this->link->query("SELECT `id` FROM `users` WHERE `id` = '{$from}'",true)) > 0)
			{
				foreach($ratings as $cat => $rating)
				{
					$rating = (int)$rating;
					$this->link->query("
						SELECT `id`
						FROM `ratingcats`
						WHERE `name` = '".$this->link->escape($cat)."'"
					);
					if($this->link->numrows() > 0)
					{
						$this->link->query("
							REPLACE INTO `ratings` (`rater`,`ratee`,`rating`,`category`)
							VALUES(
								'{$from}',
								'{$this->_key}',
								'{$rating}',
								'".$this->link->result(0,'id')."'
							)"
						);
					}
					else
					{
						throw new Exception("Attempted to add rating from non-existent category!");
					}
				}
				return true;
			}
			else
			{
				throw new Exception("Non-existent user #{$from} attempted to rate {$this->_first} {$this->last}!");
			}
		}
	}

	public function getrated($user, $category)
	{
		$user = (int)$user;
		$this->link->query("
			SELECT `rating`
			FROM `ratings`
			WHERE `rater` = '{$user}' AND `ratee` = '{$this->_key}' AND `category` = (
				SELECT `id`
				FROM `ratingcats`
				WHERE `name` = '".$this->link->escape($category)."'
			)"
		);
		if($this->link->numrows() == 0)
		{
			return 0;
		}
		else
		{
			return (int)$this->link->result(0,'rating');
		}
	}

	public function getratings()
	{
		$this->link->query("
			SELECT (
				SELECT SUM( `rating` ) 
				FROM `ratings` AS `r2` 
				WHERE `ratee` = '{$this->_key}' AND `rating` > 0 AND `r2`.`category` = `ratings`.`category` 
			) AS `positive_rating` , (
				SELECT SUM( `rating` ) 
				FROM `ratings` AS `r3` 
				WHERE `ratee` = '{$this->_key}' AND `rating` < 0 AND `r3`.`category` = `ratings`.`category` 
			) AS `negative_rating` , (
				SELECT `name` 
				FROM `ratingcats` 
				WHERE `ratings`.`category` = `ratingcats`.`id` 
			) AS `cat` 
			FROM `ratings` 
			WHERE `ratee` = '{$this->_key}'
			GROUP BY `category`"
		);
		$ratings = array();
		while($row = $this->link->fetchrow())
		{
			$ratings[ucfirst($row['cat'])] = array(
				'positive' => (int)$row['positive_rating'],
				//'negative' => round(((int)$row['negative_rating'])/1.5)
				'negative' => (int)$row['negative_rating']
			);
		}
		$this->link->query("
			SELECT `name`
			FROM `ratingcats`"
		);
		while($row = $this->link->fetchrow())
		{
			if(!isset($ratings[ucfirst($row['name'])]))
			{
				$ratings[ucfirst($row['name'])] = array(
					'positive' => 0,
					'negative' => 0
				);
			}
		}
		return $ratings;
	}
	
	function setstatus($status)
	{
		$status = (int)$status;
		$this->link->query("
			UPDATE `users`
			SET `status` = '{$status}'
			WHERE `id` = '{$this->_id}'
		");
		$this->_status = $status;
	}
	
	function setcomment($from, $comment, $time=null)
	{
		if($from == $this->_id)
		{
			throw new Exception("Users cannot rate themselves");
		}
		if($time == null)
		{
			$time = time();
		}
		$from = (int)$from;
		$comment = $this->link->escape($comment);
		$time = (int)$time;
		$this->link->query("SELECT `id` FROM `comments` WHERE `commenter` = '{$from}' AND `commentee` = '{$this->_key}'");
		if($this->link->numrows() == 0)
		{
			$this->link->query("
				INSERT INTO `comments` (`commenter`,`commentee`,`comment`,`time`)
				VALUES (
					'{$from}',
					'{$this->_key}',
					'{$comment}',
					FROM_UNIXTIME({$time})
				)"
			);
		}
		else
		{
			$this->link->query("
				UPDATE `comments`
				SET `comment` = '{$comment}', `time` = FROM_UNIXTIME({$time})
				WHERE `commenter` = '{$from}' AND `commentee` = '{$this->_key}'"
			);
		}
	}
	
	function removecomment($from)
	{
		$from = (int)$from;
		$this->link->query("
			DELETE FROM `comments`
			WHERE `commentee` = '{$this->_key}' AND `commenter` = '{$from}'"
		);
	}
	
	function getcomments()
	{
		$this->link->query("
			SELECT CONCAT(`users`.`first`,' ',`users`.`last`) AS `name`,`comments`.`commenter` AS `commenter`,`comments`.`comment` AS `text`,UNIX_TIMESTAMP(`comments`.`time`) AS `time`
			FROM `comments`
			LEFT JOIN (`users`)
			ON (`comments`.`commenter` = `users`.`id`)
			WHERE `commentee` = '{$this->_key}'
			ORDER BY `comments`.`id` DESC"
		);
		$comments = array();
		while($row = $this->link->fetchrow())
		{
			$comments[] = array(
				'commenter' => array(
					'id' => $row['commenter'],
					'name' => $row['name']
				),
				'comment' => $row['text'],
				'time' => $row['time']
			);					
		}
		return $comments;
	}
	
	function setnote($from, $note)
	{
		$from = (int)$from;
		$note = $this->link->escape($note);
		$this->link->query("
			REPLACE INTO `notes` (`noter`,`notee`,`note`)
			VALUES (
				'{$from}',
				'{$this->_key}',
				'{$note}'
			)"
		);
	}
	
	function getnote($from)
	{
		$from = (int)$from;
		$this->link->query("
			SELECT `note`
			FROM `notes`
			WHERE `noter` = '{$from}' AND `notee` = '{$this->_key}'"
		);
		if($this->link->numrows() > 0)
		{
			return $this->link->result(0,0);
		}
		else
		{
			return '';
		}
	}
	
	public function getimage()
	{
		$data = @json_decode(file_get_contents("http://services.katharineberry.co.uk/search/resident/{$this->_key}"));
		if($data != false && $data->born != "")
		{
			$image = new LLUUID($data->image);
			if($image == new LLUUID())
			{
				return SITE_ROOT."/images/noimage.png";
			}
			else
			{
				return "http://secondlife.com/app/image/".$image->tostringhyphenated()."/2";
			}
		}
		else
		{
			return "http://secondlife.com/services/user/small_image/{$this->_key}.jpg";
		}
	}
	
	public function setgrid($grid)
	{
		$grid = $this->link->escape($grid);
		$this->link->query("
			UPDATE `users`
			SET `grid` = '{$grid}'
			WHERE `id` = '{$this->_id}'"
		);
		$this->_grid = strtolower($grid);
	}
}
?>
