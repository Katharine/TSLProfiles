<?php
require_once 'utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->StartPage("Browse Profiles");
$template->SideBar();
$template->StartWindow("Profiles");
$link->query("
	SELECT `id`, CONCAT(`first`,' ',`last`) AS `name`, UNIX_TIMESTAMP(`joinedsl`) AS `joined`, (
		SELECT SUM(`rating`)
		FROM `ratings`
		WHERE `ratee` = `users`.`id`
	) AS `rating`
	FROM `users`
	ORDER BY `name`"
);
$letter = '';
while($row = $link->fetchrow())
{
	if(strtoupper($row['name'][0]) != $letter)
	{
		$letter = strtoupper($row['name'][0]);
		print "<a name=\"letter-{$letter}\"></a><h2>{$letter}</h2>";
	}
	?>
		<div class="person">
			<span class='browsename'><a href="/profiles/<?=URL::clean($row['name'])?>/"><?=$row['name']?></a></span><br />
			<!--
			<div class='browsedata'>
				Joined <acronym title='Second Life'>SL</acronym> on the <?=date('jS F, Y',$row['joined'])?>.<br />
				Overall rating: <?=number_format($row['rating'])?>.
			</div>
			-->
		</div>
	<?php
}
$template->EndWindow();
$template->EndPage();
?>