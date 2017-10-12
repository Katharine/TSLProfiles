<?php
require_once 'init.php';
$template->StartPage("Polling Twitter");
$template->Sidebar();
$template->StartWindow("Progress");
print '<pre>';
ob_flush();
flush();
ob_implicit_flush(true);
ob_end_flush();
passthru(dirname(__FILE__).'/../backend/twittergrab.php');
Finished.
print '</pre>';
$template->EndWindow();
$template->EndPage();
?>