<?php
require_once 'init.php';
$template->StartPage("Admin Panel");
$template->Sidebar();
$template->StartWindow("Tasks");
?>
<ul class="linklist">
	<li><a href="access.php">Change administrators and moderators</a></li>
	<li><a href="twitter.php">Force Twitter update</a> (this has the potential to break things)</li>
	<li><a href="fixdates.php">Fix SL join dates</a></li>
</ul>
<?php
$template->EndWindow();
$template->EndPage();
?>