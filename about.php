<?php
require_once 'utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->StartPage("About");
$template->Sidebar();
$template->StartWindow("About TSL Profiles");
?>
TSL Profiles was born from Linden Lab's removal of ratings from Second Life.</p>
<p>It is, to the best of our knowledge, the only universal profile/rating system available in TSL, and also the only means of two way communication.
<?php
$template->EndWindow();
$template->EndPage();
?>