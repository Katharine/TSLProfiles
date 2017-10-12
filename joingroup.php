<?php
require_once 'utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->Start();
if($login->loggedin)
{
	try
	{
	//	$group = 
	}
	catch(Exception $e)
	{
		//
	}
}
else
{
	$template->StartWindow('Access denied');
	?>
	<p>You may not join a group unless you are logged in!<br />
	<a href="/login.php">Log in</a> or <a href="/start.php">sign up</a>!</p>
	<?php
	$template->EndWindow();
}
$template->End();
?>