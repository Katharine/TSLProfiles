<?php
require_once 'utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->StartPage("Get Started!");
$template->Sidebar();
$template->StartWindow("Get Started");
?>
We're now open!<br />
Attachments can be obtained from <a href="secondlife://Andretti/186/34/23">Andretti (186, 34, 23)</a> (they're in the pink box). 
Be sure to check <a href="/rules.php">the rules</a> first though!</p>
<p>When first worn, this attachment will register you with the site and give you your initial password.
While you do not have to use the site at all to use this service, it is recommended that you do so in order
to access all the features we have to offer.
<?php
$template->EndWindow();
$template->EndPage();
?>