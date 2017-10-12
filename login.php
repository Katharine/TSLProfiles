<?php
error_reporting(E_ALL|E_NOTICE);
require_once 'utils.php';
function DrawLogin()
{
	$post = array_merge($_POST,$_GET);
	?>
<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
	<table>
		<tr>
			<td><acronym title="Second Life">SL</acronym> name:</td>
			<td><input type="text" name="name" /></td>
		</tr>
			<td>Password:</td>
			<td><input type="password" name="password" /></td>
		</tr>
	</table>
	<input type="submit" value="Log in" />
	<input type="hidden" name="nextpage" value="<?=isset($post['nextpage'])?htmlentities($post['nextpage']):''?>" />
	<input type="hidden" name="remoteauth" value="<?=(int)(isset($post['remoteauth'])&&$post['remoteauth'])?>" />
	<input type="hidden" name="returnto" value="<?=isset($post['returnto'])?htmlentities($post['returnto']):'/'?>" />
</form>
	<?php
}
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$names = explode(' ',$_POST['name'],2);
	if(!isset($names[1]))
	{
		$names[1] = '';
	}
	if($login->authenticate($names[0],$names[1],$_POST['password']))
	{
		if(isset($_POST['nextpage']) && $_POST['nextpage'] != '')
		{
			$nextpage = $_POST['nextpage'];
			if(isset($_POST['remoteauth']) && $_POST['remoteauth'])
			{
				preg_match('@^(?:http://)?([^/]+)@i',$nextpage, $matches);
				$host = explode('.',$matches[1],2);
				if(!isset($host[1])) $host[1] = $host[0];
				if($host[1] != 'tslblogs.com')
				{
					die("Bad host.");
				}
				else
				{
					$nextpage .= '?remotelogin&token='.$login->id.'-'.$login->key;
					$nextpage .= '&returnto='.urlencode($_POST['returnto']);
				}
			}
			header("Location: $nextpage");
			die();
		}
		$template->StartPage("Logged In");
		$template->StartSidebar();
		$template->DrawSidebar();
		$template->EndSidebar();
		$template->StartWindow("Logged in");
		print "You have successfully logged into TSL Profiles!";
		$template->EndWindow();
	}
	else
	{
		$template->StartPage("Login Failed");
		$template->StartSidebar();
		$template->DrawSidebar();
		$template->EndSidebar();
		$template->StartWindow("Log in");
		print "<p class='error'>You could not be logged in, as either your name or password were incorrect.</p>";
		DrawLogin();
		$template->EndWindow();
	}
}
else
{
	$template->StartPage("Log in");
	$template->StartSidebar();
	$template->DrawSidebar();
	$template->EndSidebar();
	$template->StartWindow("Log in");
	DrawLogin();
	$template->EndWindow();
}
$template->EndPage();
?>