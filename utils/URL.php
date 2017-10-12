<?php
class URL
{
	public static function clean($url)
	{
		return preg_replace('/[^a-zA-Z0-9_-]/','_',$url);
	}
	
	public static function parse($url)
	{
		$url = preg_replace('~/+~','/',$url);
		$parts = explode('/',trim($url,'/'));
		$parts['section'] = array_shift($parts);
		return $parts;
	}
	
	public static function Linkify($string)
	{
		return preg_replace('/(http:\/\/[a-zA-Z0-0-.]+\/[^ ;)]+)/','<a href="$1">$1</a>',$string); 
	}
}
?>