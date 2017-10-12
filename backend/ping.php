<?php
require_once '../utils.php';
$headers = apache_request_headers();
$link = new MySQL();
$link->query("
	SELECT `id`
	FROM `users`
	WHERE `key` = '".$link->escape($headers['X-SecondLife-Owner-Key'])."'"
);
if($link->numrows() > 0)
{
	$status = isset($_GET['status'])?(int)$_GET['status']:0;
	$profile = new Profile($link,$link->result(0,'id'));
	$profile->setstatus($status);
	$sim = trim(substr($headers['X-SecondLife-Region'],0,strrpos($headers['X-SecondLife-Region'],'('/*) - editor bug */)));
	$pos = new LLVector3('<'.substr(trim($headers['X-SecondLife-Local-Position']),1,-1).'>');
	try
	{
		$gpos = trim(substr($headers['X-SecondLife-Region'],strrpos($headers['X-SecondLife-Region'],'('/*) - editor bug */)+1),/*( - editor bug*/") \r\n");
		$gpos = new LLVector3("<{$gpos},0>");
		$profile->setgrid(($gpos->x<280192)?'mg':'tg');
	}
	catch(Exception $e)
	{
		//
	}
	$profile->setpos($sim, $pos);
}
?>