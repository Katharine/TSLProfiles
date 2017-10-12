<?php
require_once 'utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->AddScript('groups');
$template->Start();
$template->StartWindow('Group List');
if($login->loggedin)
{
	print '<div id="currentgroups">Loading...</div>';
}
?>
<div id="grouplist">
<p>Loading...</p>
</div>
<?php
$template->EndWindow();
$template->End();
?>