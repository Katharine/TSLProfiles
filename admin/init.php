<?php
require_once '../utils.php';
$link = new MySQL();
$login = new Login($link);
if(!$login->admin)
{
	header("Location: /login.php?nextpage={$_SERVER['REQUEST_URI']}");
	die();
}
$template = new Template($login);
?>