<?php
require_once 'utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->AddScript('mutelist');
$template->StartPage('Muted users');
$template->Sidebar();
$template->StartWindow("Muted users");
print '<div id="muted">Loading; please wait...</div>';
$template->EndWindow();
$template->EndPage();
?>