<?php
require_once '../utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->StartPage("Help");
$template->Sidebar();
$template->StartWindow("Help subjects");
?>
<ul class="linklist">
	<li><a href="/help/blog/">&micro;Blog help</a></li>
	<li><a href="/help/profile/">Profile help</a></li>
	<li><a href="/help/ratings/">Rating help</a></li>
	<li><a href="/help/attachment/">Attachment reference</a></li>
</ul>
<?php
$template->EndWindow();
$template->EndPage();
?>