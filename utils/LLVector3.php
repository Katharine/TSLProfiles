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
?>