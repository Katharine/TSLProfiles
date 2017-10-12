<?php
require_once '../utils.php';
$link = new MySQL();
$login = new Login($link);
$pass = substr(preg_replace('/[^a-zA-Z0-9]/','',base64_encode(str_rot13(md5(rand(0,10000000))))),0,10);
$names = explode(' ',$_GET['name'],2);
$key = $_GET['key'];
$link->query("
	INSERT IGNORE INTO `keys` (`first`,`last`,`key`)
	VALUES (
		'".$link->escape($names[0])."',
		'".$link->escape($names[1])."',
		'".$link->escape($key)."'
	)"
);
if($login->create($names[0],$names[1],$key,$link->escape($pass)))
{
	try
	{
		$gpos = trim(substr($headers['X-SecondLife-Region'],strrpos($headers['X-SecondLife-Region'],'('/*) - editor bug */)+1),/*( - editor bug*/") \r\n");
		$gpos = new LLVector3("<{$gpos},0>");
		if($gpos->x > 0)
		{
			$profile->setgrid(($gpos->x<280192)?'mg':'tg');
		}
	}
	catch(Exception $e)
	{
		//
	}
	print "register\nnewregister\n{$pass}";
}
else
{
	print "register\nregistered";
}
?>
