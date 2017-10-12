<?php
require_once 'utils.php';
$link = new MySQL();
$login = new Login($link);
$login->logout();
$template = new Template($login);
$template->StartPage("Logged out");
$template->Sidebar();
$template->StartWindow("Logged out");
print "You have logged out of TSL Profiles.";
$template->EndWindow();
?>