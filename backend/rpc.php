<?php
require_once '../utils.php';
$headers = apache_request_headers();
$link = new MySQL();
$owner = $link->escape($headers['X-SecondLife-Owner-Key']);
$link->query("UPDATE `users` SET `rpc` = '".$link->escape($_GET['channel'])."' WHERE `key` = '{$owner}'");
?>