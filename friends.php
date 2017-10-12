<?php
require_once 'utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->AddScript('friends');
$template->StartPage("Friends list");
$template->Sidebar();
$template->StartWindow("Friends");
?>
<h1>Friend List</h1>
<div id="content" class="plist">Loading...</div>
<?php
$template->EndWindow();
$template->EndPage();
?>