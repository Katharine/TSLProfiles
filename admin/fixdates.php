<?php
require_once 'init.php';
$template->StartPage("Admin Panel");
$template->Sidebar();
$template->StartWindow("Tasks");
print '<pre>';
$link->query("
	SELECT `id`,CONCAT(`first`,' ',`last`) AS `name`,`key`
	FROM `users`
	WHERE `joinedsl` = '0000-00-00'"
);
print "Accounts needing repair: ".$link->numrows()."\n\n";
$server = new Server($link);
$failures = 0;
$successes = 0;
$link2 = clone $link;
while($row = $link2->fetchrow())
{
	$id = (int)$row['id'];
	$name = $row['name'];
	$key = $row['key'];
	print "Repairing #{$id} ({$name})... ";
	$dob = $server->requestdata(Server::DATA_BORN, $key);
	$spaceswanted = 35 - strlen($name) - strlen($id);
	if($dob == '0000-00-00' || !$dob)
	{
		print str_repeat(' ',$spaceswanted).'[<span style="color:red; font-weight:bold;">FAILED</span>]';
		++$failures;
	}
	else
	{
		$link->query("
			UPDATE `users`
			SET `joinedsl` = '".$link->escape($dob)."'
			WHERE `id` = '{$id}'"
		);
		if($link->numrows() > 0)
		{
			$spaceswanted -= strlen($dob);
			print htmlentities($dob).str_repeat(' ',$spaceswanted).'[<span style="color:green; font-weight:bold;">OK</span>]';
			++$successes;
		}
		else
		{
			print str_repeat(' ',$spaceswanted).'[<span style="color:red; font-weight:bold;">FAILED</span>]';
			++$failures;
		}
	}
	print "\n";
}
print "\nFinished. {$successes} succeeded, {$failures} failed.\n";
print '</pre><p><a href="index.php">Return</a></p>';
$template->EndWindow();
$template->EndPage();
?>