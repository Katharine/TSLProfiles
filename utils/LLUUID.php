<?php
class LLUUID
{
	// Simcaps only use hyphenated strings, so supporting anything else seems redundent.
	// Then again, it doesn't do anything involving quaternions or vectors that would be relevent
	// either, and we implemented those.
	private $_uuid;
	
	public function __construct($uuid = '00000000-0000-0000-0000-000000000000')
	{
		$this->_uuid = $this->_hyphenate($uuid);
	}
	
	public function __toString()
	{
		return $this->tostring();
	}
	
	public function tostring()
	{
		return $this->_uuid;
	}
	
	public function tostringunhyphenated()
	{
		return str_replace('-','',$this->_uuid);
	}
	
	public function tostringhyphenated()
	{
		return $this->tostring();
	}
	
	public function __get($var)
	{
		if($var == 'key')
		{
			return $this->tostring();
		}
		return NULL;
	}
	
	public function __set($var, $val)
	{
		if($var == 'key')
		{
			if($this->_verify($val))
			{
				$this->_uuid = $this->_hyphenate($val);
			}
			else
			{
				throw new Exception("Malformed UUID: {$val}");
			}
		}
	}
	
	private function _verify($uuid)
	{
		if(strlen($uuid) == 36)
		{
			$uuid = str_replace('-','',$uuid);
		}
		if(strlen($uuid) == 32)
		{
			 return strlen(preg_replace('/[^a-f0-9]/i','',$uuid)) == 32;
		}
		else
		{
			return false;
		}
	}
	
	private function _hyphenate($uuid)
	{
		if(!$this->_verify($uuid))
		{
			throw new Exception("Malformed UUID: {$uuid}");
		}
		$uuid = str_replace('-','',strtolower($uuid));
		return substr($uuid,0,8).'-'.substr($uuid,8,4).'-'.substr($uuid,12,4).'-'.substr($uuid,16,4).'-'.substr($uuid,20,12);
	}
	
	public static function random()
	{
	
		return new self(md5(mt_rand(0,2000000000)));
	}
}
?>