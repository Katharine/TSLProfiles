<?php
require_once 'init.php';
$template->AddScript('adminaccess');
$template->StartPage("Access Levels");
$template->Sidebar();
$template->StartWindow("Administrators");
print <<<HTML
<div id="Administratordiv" class="plist">
Loading...
</div>
HTML;
$template->EndWindow();
$template->StartWindow("Moderators");
print <<<HTML
<div id="Moderatordiv" class="plist">
Loading...
</div>
HTML;
$template->EndWindow();
$template->EndPage();
?>