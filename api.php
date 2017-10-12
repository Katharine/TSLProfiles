<?php
require_once 'utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->StartPage("APIs");
$template->Sidebar();
$template->StartWindow("TSL Profiles API");
print 'Documentation of the API is at <a href="'.API_ROOT.'">'.API_ROOT.'</a>.';
$template->EndWindow();
$template->StartWindow("Your API key");
?>
User ID: <span class="code"><?=htmlentities($_COOKIE['uid'])?></span><br />
API key: <span class="code"><?=htmlentities($_COOKIE['sid'])?></span>
<p>Please bear in mind that this code will change whenever your password does.<br />
When utilising the API via LSL's llHTTPRequest you will be authenticated as yourself, unless you specify an alternative userid and API key.</p>
<?php
$template->EndWindow();
$template->EndPage();
?>