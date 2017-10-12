<?php
require_once 'utils.php';
$link = new MySQL();
$login = new Login($link);
$prefix = '';
if($_GET['subdomain'] != 'tslblogs')
{
	$prefix = "{$_GET['subdomain']}.";
}
if($login->loggedin)
{
	header("Location: http://{$prefix}tslblogs.com/?remotelogin&returnto=".urlencode($_GET['returnto'])."&token={$login->id}-{$login->key}");
}
else if(isset($_GET['forcelogin']))
{
	header("Location: /login.php?nextpage=http://{$prefix}tslblogs.com/&remoteauth=1&returnto=".urlencode($_GET['returnto']));
}
else
{
	header("Location: http://{$prefix}tslblogs.com/?remotelogin&returnto={$_GET['returnto']}&loginfailure=1");
}
?>