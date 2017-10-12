<?php
require_once '../utils.php';
$link = new MySQL();
$headers = apache_request_headers();
$key = $link->escape($headers['X-SecondLife-Object-Key']);
$rpc = $link->escape($_GET['channel']);
$link->query("
	REPLACE INTO `servers` (`key`,`channel`,`ping`)
	VALUES (
		'{$key}',
		'{$rpc}',
		NOW()
	)"
);
if($link->numrows() > 0)
{
	print "registered";
}
else
{
	print "error";
}
?>