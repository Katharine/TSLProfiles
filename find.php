<?php
require_once 'utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->AddScript('find');
$template->StartPage();
$template->Sidebar();
$template->StartWindow("Find Someone");
?>
SL Name: <input type="text" id="searchbox" /> <input type="button" value="Search" id="searchbutton" />
<?php
$template->EndWindow();
$template->StartWindow("Results");
print '<p>Results in <span class="hasaccount">strong text</span> have TSL Profiles accounts, and therefore more options.</p><div id="results">You have to search for someone before you can see results. ;p</div>';
$template->EndWindow();
$template->EndPage();
?>