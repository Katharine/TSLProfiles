<?php
$this->StartBlock("TSL Profiles");
print "<a href=\"{$this->prefix}about/\">About</a><br />\n";
if(!$this->login->loggedin)
{
	print "<a href=\"{$this->prefix}start/\">Get Started!</a><br />\n";
}
else
{
	print "<a href=\"{$this->prefix}apiinfo/\">API info</a><br />\n";
}
print "<a href=\"{$this->prefix}browse/\">Browse Profiles</a><br />\n<a href=\"{$this->prefix}search/\">Find Profiles</a><br />\n<a href=\"{$this->prefix}help/\">Help</a>\n";
if($this->login->admin)
{
	print "<br /><a href=\"{$this->prefix}admin\">Administration</a>\n";
}
$this->EndBlock();
if($this->login->loggedin)
{
	$this->StartBlock('Your Profile');
	print preg_replace('~([^:])/+~','$1/',"<a href=\"{$this->prefix}{$this->login->profile->url}\">Your profile</a><br />
	<a href=\"{$this->prefix}you/muted/\">Muted users</a><br />
	<a href=\"{$this->prefix}you/friends/\">Friend List</a><br />
	<a href=\"{$this->prefix}you/settings/\">Settings</a><br />
	<a href=\"{$this->prefix}you/logout/\">Log out</a>");
	$this->EndBlock();
}
else if($this->prefix == '/')
{
	$this->StartBlock('Log in')
	?>
	<form action="<?=$this->prefix?>login.php" method="post" style="border: 0px; margin:0px;">
		<table width="100%" style='border:0px;margin:0px;'>
			<tr>
				<td width='0%'>Name:</td>
				<td width='100%'><input type="text" name="name" size="3" style="width: 95%;" /></td>
			</tr>
			<tr>
				<td>Password:</td>
				<td><input type="password" name="password" size="3" style="width: 95%;" /></td>
			</tr>
		</table>
		<input type="submit" value="Log in" />
		<input type="hidden" name="nextpage" value="<?=($_SERVER['PHP_SELF'] != '/logout.php')?$_SERVER['REQUEST_URI']:''?>" />
	</form>
	<?php
	$this->EndBlock();
}
?>