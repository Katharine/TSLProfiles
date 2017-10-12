<?php
class LLVector3
{
	public $x;
	public $y;
	public $z;
	
	public function __construct($x = 0, $y = 0, $z = 0)
	{
		if(is_string($x) && $y === 0 && $z === 0)
		{
			$this->parse($x);
		}
		else
		{
			$this->x = $x;
			$this->y = $y;
			$this->z = $z;
		}
	}
	
	public function parse($string)
	{
		$string = str_replace(array('<','>'),array('',''),$string);
		$string = explode(',',$string);
		if(count($string) != 3)
		{
			throw new Exception("Invalid LLVector3");
		}
		foreach($string as &$val)
		{
			$val = trim($val);
			if(!is_numeric($val))
			{
				throw new Exception("Invalid LLVector3");
				break;
			}
		}
		$this->x = $string[0];
		$this->y = $string[1];
		$this->z = $string[2];
	}
	
	public function getdistanceto(self $point)
	{
		return sqrt((($this->x-$point->x)*($this->x-$point->x))+(($this->y-$point->y)*($this->y-$point->y))+(($this->z-$point->z)*($this->z-$point->z)));
	}
	
	public function add(self $other)
	{
		return new self($this->x + $other->x, $this->y + $other->y, $this->z + $other->z);
	}
	
	public function subtract(self $other)
	{
		return new self($this->x - $other->x, $this->y - $other->y, $this->z - $other->z);
	}
	
	public function multiply(LLQuaternion $quat)
	{
		$vq = new LLQuaternion($this->x,$this->y,$this->z,0);
		$nq = new LLQuaternion($quat->x*-1,$quat->y*-1,$quat->z*-1,$quat->w);
		$result = $quat->multiply($vq)->multiply($nq);
		return new self($result->x,$result->y,$result->z);
	}
	
	public function __toString()
	{
		return $this->tostring();
	}
	
	public function tostring()
	{
		return "<{$this->x}, {$this->y}, {$this->z}>";
	}
}

class LLVector4
{
	public $x;
	public $y;
	public $z;
	public $s;
	
	public function __construct($x = 0, $y = 0, $z = 0, $s = 0)
	{
		if(is_string($x) && func_num_args() == 1)
		{
			$this->parse($x);
		}
		else
		{
			$this->x = $x;
			$this->y = $y;
			$this->z = $z;
			$this->s = $s;
		}
	}
	
	public function parse($string)
	{
		$string = str_replace(array('<','>'),array('',''),$string);
		$string = explode(',',$string);
		if(count($string) != 4)
		{
			throw new Exception("Invalid LLVector4");
		}
		foreach($string as &$val)
		{
			$val = trim($val);
			if(!is_numeric($val))
			{
				throw new Exception("Invalid LLVector4");
				break;
			}
		}
		$this->x = $string[0];
		$this->y = $string[1];
		$this->z = $string[2];
		$this->s = $string[3];
	}
	
	public function __toString()
	{
		return $this->tostring();
	}
	
	public function tostring()
	{
		return "<{$this->x}, {$this->y}, {$this->z}, {$this->s}>";
	}
}

class LLQuaternion
{
	public $w;
	public $x;
	public $y;
	public $z;
	
	public function __construct($x = 0, $y = 0, $z = 0, $w = NULL)
	{
		if(is_string($x) && func_num_args() == 1)
		{
			$this->parse($x);
		}
		else
		{
			$this->x = $x;
			$this->y = $y;
			$this->z = $z;
			if($w === NULL)
			{
				$sum = 1 - $x*$x - $y*$y - $z*$z;
				$this->w = ($sum > 0)?sqrt($sum):0;
			}
			else
			{
				$this->w = $w;
			}
		}
	}
	
	public function parse($string)
	{
		$string = str_replace(array('<','>'),array('',''),$string);
		$string = explode(',',$string);
		if(count($string) != 4)
		{
			throw new Exception("Invalid LLQuaternion");
		}
		foreach($string as &$val)
		{
			$val = trim($val);
			if(!is_numeric($val))
			{
				throw new Exception("Invalid LLQuaternion");
				break;
			}
		}
		$this->x = $string[0];
		$this->y = $string[1];
		$this->z = $string[2];
		$this->w = $string[3];
	}
	
	public function multiply(self $that)
	{
		$ret = new self();
		$ret->w = $that->w*$this->w - $that->x*$this->x - $that->y*$this->y - $that->z*$this->z;
		$ret->x = $that->w*$this->x + $that->x*$this->w + $that->y*$this->z - $that->z*$this->y;
		$ret->y = $that->w*$this->y + $that->y*$this->w + $that->z*$this->x - $that->x*$this->z;
		$ret->z = $that->w*$this->z + $that->z*$this->w + $that->x*$this->y - $that->y*$this->x;
		return $ret;
	}
	
	public function __toString()
	{
		return $this->tostring();
	}
	
	public function tostring()
	{
		return "<{$this->x}, {$this->y}, {$this->z}, {$this->w}>";
	}
}

class LLUUID
{
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
