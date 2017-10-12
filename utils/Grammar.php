<?php
class Grammar
{
	public static function correct($string)
	{
		$string = str_replace('s\'s','s\'',$string);
		return $string;
	}
}
?>