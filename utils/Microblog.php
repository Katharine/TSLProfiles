<?php
class Microblog
{
	private $link;
	private $profile;
	private $user;
	
	public function __construct(MySQL $link, $user)
	{
		$this->link = $link;
		try
		{
			$this->profile = new Profile($link, $user);
			if($this->profile->id)
			{
				$user = $this->profile->id;
			}
			else
			{
				throw new Exception("error");
			}
		}
		catch(Exception $e)
		{
			throw new Exception("Unknown user #{$user}!");
		}
		$this->user = (int)$user;
	}
	
	public function post($post, $from)
	{
		if(trim($post) == '')
		{
			throw new Exception("You can't make blank posts!");
		}
		else
		{
			$this->link->query("
				INSERT INTO `microblog` (`poster`,`content`,`from`,`when`)
				VALUES(
					'{$this->user}',
					'".$this->link->escape($post)."',
					'".$this->link->escape($from)."',
					NOW()
				)"
			);
			if($this->profile->twitterenabled)
			{
				$twit = new Twitter($this->profile);
				$twit->post($post);
			}
			return true;
		}
	}
	
	public function postimage($dir, $img, $name, $message, $sim, LLVector3 $pos)
	{
		$this->link->query("
			INSERT INTO `blogimages` (`owner`,`time`,`name`,`message`,`sim`,`x`,`y`,`z`)
			VALUES (
				'{$this->user}',
				NOW(),
				'".$this->link->escape($name)."',
				'".$this->link->escape($message)."',
				'".$this->link->escape($sim)."',
				{$pos->x},
				{$pos->y},
				{$pos->z}
			)"
		);
		$id = $this->link->id();
		imagepng($img, "{$dir}/img{$id}.png");
		$y = imagesy($img);
		if($y > 120)
		{
			$x = imagesx($img);
			$thumb = imagecreatetruecolor($x*(120/$y),120);
			imagecopyresampled($thumb,$img,0,0,0,0,$x*(120/$y),120,$x,$y);
			imagepng($thumb, "{$dir}/img{$id}.png.thumb");
			imagedestroy($thumb);
		}
		else
		{
			imagepng($img, "{$dir}/img{$id}.png.thumb");
		}
		if($this->profile->twitterenabled)
		{
			$twit = new Twitter($this->profile);
			$twit->post(SITE_ROOT."/profiles/".URL::clean($this->profile->name)."/blog/{$id}/: {$message}");
		}
		return $id;
	}
	
	public function entries($count = 0)
	{
		if(!is_numeric($count))
		{
			throw new Exception("Numbers should be numeric... >_>");
		}
		$ids = array();
		$this->link->query("
			SELECT `id`
			FROM `microblog`
			WHERE `poster` = '{$this->user}'
			ORDER BY `when` DESC
			".(($count>0)?"LIMIT {$count}":'')
		);
		while($row = $this->link->fetchrow())
		{
			$ids[] = $row['id'];
		}
		return $ids;
	}
	
	public function imageentries($count = 0)
	{
		if(!is_numeric($count))
		{
			throw new Exception("Numbers should be numeric... >_>");
		}
		$ids = array();
		$this->link->query("
			SELECT `id`
			FROM `blogimages`
			WHERE `owner` = '{$this->user}'
			ORDER BY `time` DESC
			".(($count>0)?"LIMIT {$count}":'')
		);
		while($row = $this->link->fetchrow())
		{
			$ids[] = $row['id'];
		}
		return $ids;
	}
	
	public function deletetext($id)
	{
		if(!is_numeric($id))
		{
			throw new Exception("Numbers should be numeric... >_>");
		}
		$ids = array();
		$this->link->query("
			DELETE FROM `microblog`
			WHERE `poster` = '{$this->user}' AND `id` = '{$id}'"
		);
		if(!$this->link->numrows())
		{
			throw new Exception("Text post #{$id} from {$this->user} doesn't exist!");
		}
	}
	
	public function deleteimage($id)
	{
		if(!is_numeric($id))
		{
			throw new Exception("Numbers should be numeric... >_>");
		}
		$ids = array();
		$this->link->query("
			DELETE FROM `blogimages`
			WHERE `owner` = '{$this->user}' AND `id` = '{$id}'"
		);
		if(!$this->link->numrows())
		{
			throw new Exception("Image post #{$id} from {$this->user} doesn't exist!");
		}
	} 
	
	public function readimage($id)
	{
		return self::ReadImagePost($this->link, $id);
	}
	
	public function read($id)
	{
		return self::ReadTextPost($this->link, $id);
	}
	
	public function mergedentries($count)
	{
		return self::GetAllPosts($this->link, $count, $this);
	}
	
	public static function ReadImagePost(MySQL $link, $id)
	{
		if(!is_numeric($id))
		{
			throw new Exception("Expected a numeric ID!");
		}
		else
		{
			$link->query("
				SELECT `owner`,`sim`,`x`,`y`,`z`,`name`,`message`,UNIX_TIMESTAMP(`time`) AS `time`
				FROM `blogimages`
				WHERE `id` = '{$id}'"
			);
			if($link->numrows() == 0)
			{
				throw new Exception("Unknown post ID #{$id}!");
			}
			else
			{
				$result = $link->fetchrow();
				foreach($result as $key => $val)
				{
					if(is_numeric($key))
					{
						unset($result[$key]);
					}
				}
				$result['time'] = (int)$result['time'];
				$result['owner'] = (int)$result['owner'];
				$result['x'] = (int)$result['x'];
				$result['y'] = (int)$result['y'];
				$result['z'] = (int)$result['z'];
				$result['content'] = substr($result['message'],0,255);
				unset($result['message']);
				return $result;
			}
		}
	}
	
	public static function ReadTextPost(MySQL $link, $id)
	{
		if(!is_numeric($id))
		{
			throw new Exception("Expected a numeric ID!");
		}
		else
		{
			$link->query("
				SELECT `poster`,`content`,`from`,UNIX_TIMESTAMP(`when`) AS `time`
				FROM `microblog`
				WHERE `id` = '{$id}'"
			);
			if($link->numrows() == 0)
			{
				throw new Exception("Unknown post ID #{$id}!");
			}
			else
			{
				$result = $link->fetchrow();
				foreach($result as $key => $val)
				{
					if(is_numeric($key))
					{
						unset($result[$key]);
					}
				}
				$result['time'] = (int)$result['time'];
				$result['poster'] = (int)$result['poster'];
				return $result;
			}
		}
	}
	
	public static function TextPostIDs(MySQL $link, $count = 0)
	{
		if(!is_numeric($count))
		{
			throw new Exception("Numbers should be numeric... >_>");
		}
		$ids = array();
		$link->query("
			SELECT `id`
			FROM `microblog`
			ORDER BY `when` DESC
			".(($count>0)?"LIMIT {$count}":'')
		);
		while($row = $link->fetchrow())
		{
			$ids[] = $row['id'];
		}
		return $ids;
	}
	
	public static function ImagePostIDs(MySQL $link, $count = 0)
	{
		if(!is_numeric($count))
		{
			throw new Exception("Numbers should be numeric... >_>");
		}
		$ids = array();
		$link->query("
			SELECT `id`
			FROM `blogimages`
			ORDER BY `time` DESC
			".(($count>0)?"LIMIT {$count}":'')
		);
		while($row = $link->fetchrow())
		{
			$ids[] = $row['id'];
		}
		return $ids;
	}
	
	public static function GetAllPosts(MySQL $link, $limit = 0, $user = false)
	{
		if($user !== false && !($user instanceof self))
		{
			$user = new self($link, $user);
		}
		$ids = ($user === false)?self::TextPostIDs($link, $limit):$user->entries($limit);
		$posts = array();
		foreach($ids as $id)
		{
			$post = ($user === false)?self::ReadTextPost($link, $id):$user->read($id);
			$post['poster'] = array(
				'id' => $post['poster']
			);
			$post['id'] = $id;
			$profile = new Profile($link, $post['poster']['id']);
			$post['poster']['name'] = $profile->name;
			$time = $post['time'];
			while(isset($posts[$time])) ++$time;
			$posts[$time] = $post;
		}
		$ids = ($user === false)?self::ImagePostIDs($link, $limit):$user->imageentries($limit);
		foreach($ids as $id)
		{
			$post = ($user === false)?self::ReadImagePost($link, $id):$user->readimage($id);
			$post['poster'] = array(
				'id' => $post['owner']
			);
			unset($post['owner']);
			$profile = new Profile($link, $post['poster']['id']);
			$post['poster']['name'] = $profile->name;
			$post['image'] = SITE_ROOT."/images/ublog/img{$id}.png";
			$post['imageid'] = (int)$id;
			$post['from'] = 'a postcard';
			$time = $post['time'];
			while(isset($posts[$time])) ++$time;
			$posts[$time] = $post;
		}
		krsort($posts);
		$posts = array_values($posts);
		if($limit > 0)
		{
			$posts = array_slice($posts,0,$limit);
		}
		return $posts;
	}
}
?>