<?php
class GalleryImage
{
	const GALLERY_FOLDER = '/images/gallery/';
	const THUMBNAIL_SIZE = 150;
	const MEDIUM_MAX = 500;
	
	public static function Create($link, $img, $imageid, $owner, $title = '', $caption = '', $tags = array())
	{
		$profile = new Profile($link, $owner);
		if(!$profile->getid())
		{
			throw new Exception("No account!");
		}
		$dir = dirname(__FILE__).'/../'.self::GALLERY_FOLDER."/{$imageid}";
		//die($dir);
		mkdir($dir);
		// Make a thumbnail.
		$thumb = imagecreatetruecolor(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);
		$x = imagesx($img);
		$y = imagesy($img);
		$src_x = 0;
		$src_y = 0;
		if($x > $y)
		{
			$src_x = ($x-$y)/2;
		}
		else
		{
			$src_y = ($y-$x)/2;
		}
		$dst_x = 0;
		$dst_y = 0;
		if($x < self::THUMBNAIL_SIZE)
		{
			$src_x = 0;
			$dst_x = (self::THUMBNAIL_SIZE - $x) / 2;
		}
		if($y < self::THUMBNAIL_SIZE)
		{
			$src_y = 0;
			$dst_y = (self::THUMBNAIL_SIZE - $y) / 2;
		}
		$size = ($x > $y)?$y:$x;
		imagecopyresampled($thumb,$img,$dst_x,$dst_y,$src_x,$src_y,self::THUMBNAIL_SIZE,self::THUMBNAIL_SIZE,$size,$size);
		imagepng($thumb,"{$dir}/thumb.png");
		chmod("{$dir}/thumb.png",0444);
		imagedestroy($thumb);
		
		$watermark = imagecolorallocatealpha($img,255,255,255,80);
		$full = imagecreatetruecolor($x,$y);
		imagecopy($full,$img,0,0,0,0,$x,$y);
		imagettftext($full,20,0,18,$y-20,$watermark,'/usr/share/fonts/corefonts/arial.ttf',"tsl profiles - ".strtolower($profile->getname()));
		// Save full version
		imagepng($full,"{$dir}/full.png");
		chmod("{$dir}/full.png",0444);
		imagedestroy($full);
		
		// Make screen-size version.
		if($x <= self::MEDIUM_MAX && $y <= self::MEDIUM_MAX)
		{
			symlink("{$dir}/full.png","{$dir}/medium.png");
		}
		else
		{	
			$med = imagecreatetruecolor($x,$y);
			imagecopy($med,$img,0,0,0,0,$x,$y);
			if($x > self::MEDIUM_MAX)
			{
				$img2 = imagecreatetruecolor(self::MEDIUM_MAX,$y*(self::MEDIUM_MAX/$x));
				imagecopyresampled($img2,$med,0,0,0,0,self::MEDIUM_MAX,$y*(self::MEDIUM_MAX/$x),$x,$y);
				imagedestroy($med);
				$med = $img2;
				unset($img2);
				$x = self::MEDIUM_MAX;
				$y = imagesy($med);
			}
			if($y > self::MEDIUM_MAX)
			{
				$img2 = imagecreatetruecolor($x*(self::MEDIUM_MAX/$y),self::MEDIUM_MAX);
				imagecopyresampled($img2,$med,0,0,0,0,$x*(self::MEDIUM_MAX/$y),self::MEDIUM_MAX,$x,$y);
				imagedestroy($med);
				$med = $img2;
				unset($img2);
				$x = imagesy($med);
				$y = self::MEDIUM_MAX;
			}
			imagettftext($med,20,0,18,$y-20,$watermark,'/usr/share/fonts/corefonts/arial.ttf',"tsl profiles - ".strtolower($profile->getname()));
			imagepng($med,"{$dir}/medium.png");
			imagedestroy($med);
		}
		chmod("{$dir}/medium.png",0444);
		// Finished, set permissions on directory.
		chmod($dir,0555);
		$link->query("
			INSERT INTO `images` (`image`,`owner`,`uploaded`,`title`,`caption`)
			VALUES (
				'".$link->escape($imageid)."',
				'".$profile->getid()."',
				NOW(),
				'".$link->escape($title)."',
				'".$link->escape($caption)."'
			)"
		);
		$image = new self($link,$link->id());
		foreach($tags as $tag)
		{
			$image->addtag($tag);
		}
	}
	
	private $link;
	private $_id;
	private $_title;
	private $_caption;
	private $_owner;
	private $_uploaded;
	private $_imageid;
	
	public function __construct(MySQL $link, $id)
	{
		$this->link = $link;
		if(is_numeric($id))
		{
			$link->query("
				SELECT `id`,`image`,`owner`,UNIX_TIMESTAMP(`uploaded`) AS `uploaded`, `title`, `caption`
				FROM `images`
				WHERE `id` = '{$id}'"
			);
		}
		else if(strlen($id) == 26)
		{
			$link->query("
				SELECT `id`,`image`,`owner`,UNIX_TIMESTAMP(`uploaded`) AS `uploaded`, `title`, `caption`
				FROM `images`
				WHERE `image` = '".$link->escape($id)."'"
			);
		}
		if($link->numrows() == 0)
		{
			throw new Exception("No such image!");
		}
		$data = $link->fetchrow();
		$this->_id = (int)$data['id'];
		$this->_title = $data['title'];
		$this->_caption = $data['caption'];
		$this->_uploaded = (int)$data['uploaded'];
		$this->_owner = new Profile($link, $data['owner']);
		$this->_imageid = $data['image'];
	}
	
	public function __get($var)
	{
		$test = "_{$var}";
		if(isset($this->$test))
		{
			return $this->$test;
		}
		switch($var)
		{
		case 'thumb':
			return self::GALLERY_FOLDER.$this->_imageid.'/thumb.png';
		case 'medium':
			return self::GALLERY_FOLDER.$this->_imageid.'/medium.png';
		case 'full':
			return self::GALLERY_FOLDER.$this->_imageid.'/full.png';
		case 'url':
			return self::GALLERY_FOLDER.$this->_imageid;
		}
		return null;
	}
	
	public function __set($var, $val)
	{
		$realvar = "_{$val}";
		switch($var)
		{
		case 'title':
		case 'caption':
			$this->$realvar = $val;
			$this->link->query("
				UPDATE `images`
				SET `{$var}` = '".$this->link->escape($val)."'
				WHERE `id` = {$this->_id}"
			);
		}
	}
	
	public function __toString()
	{
		return $this->full;
	}
	
	public function gettags()
	{
		$this->link->query("
			SELECT `imagetaguses`.`label` AS `tag`, `imagetags`.`tag` AS `generaltag`
			FROM `imagetaguses`,`imagetags`
			LEFT JOIN ON `imagetaguses`.`tag` = `imagetags`.`id`
			WHERE `imagetaguses`.`image` = {$this->_id}"
		);
		$tags = array();
		while($row = $this->link->fetchrow())
		{
			$tags[$row['generaltag']] = $row['tag'];
		}
		return $tags;
	}
	
	public function addtag($tag)
	{
		$generalised = $this->generalisetag($tag);
		$this->link->query("
			SELECT `id`
			FROM `imagetags`
			WHERE `tag` = '{$generalised}'"
		);
		$tagid = 0;
		if($this->link->numrows() > 0)
		{
			$tagid = $this->link->result(0,'id');
		}
		else
		{
			$this->link->query("
				INSERT INTO `imagetags` (`tag`)
				VALUES ('{$generalised}')"
			);
			$tagid = $this->link->id();
		}
		$this->link->query("
			REPLACE INTO `imagetaguses` (`image`,`tag`,`label`)
			VALUES (
				{$this->_id},
				{$tagid},
				'".$this->link->escape($tag)."'
			)"
		);
	}
	
	private function generalisetag($tag)
	{
		return preg_replace('/[^a-z0-9]/','',strtolower($tag));
	}
}
?>