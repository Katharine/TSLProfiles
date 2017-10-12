<?php
require_once 'utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->AddRSS('Collated &micro;Blogs', '/feed/');
$template->Start();
$template->StartWindow("Welcome!");
print 'Welcome to TSL Profiles, the Teen Grid\'s best <em>(only)</em> profile and rating site!</p>
<p><a href="/start/">Grab an attachment</a> and get started!';
$template->EndWindow();
$template->StartWindow("Recent Images");
$ids = Microblog::ImagePostIDs($link,3);
print '<table width="100%"><tr>';
foreach($ids as $row)
{
	$id = $row;
	$row = Microblog::ReadImagePost($link,$row);
	$profile = new Profile($link, $row['owner']);
	?>
	<td style="width: 33%; margin-left: auto; margin-right: auto; text-align: center;">
		<a href="<?=$profile->url?>/blog/<?=$id?>/">
			<img src="/images/ublog/img<?=$id?>.png.thumb" title="<?=htmlentities($row['name'])?>" alt="<?=htmlentities($row['name'])?>" border="0" />
		</a>
	</td>
	<?php
}
print '</tr></table>';
$template->EndWindow();
$template->StartWindow("Recent &micro;Blog entries");
$ids = Microblog::TextPostIDs($link,5);
foreach($ids as $row)
{
	$id = $row;
	$row = Microblog::ReadTextPost($link,$row);
	$profile = new Profile($link, $row['poster']);
	?>
	<div class="blogentry">
		<span class="blogname"><a href="<?=$profile->url?>/blog/"><?=$profile->name?></a>: </span>
		<span class="blogtext"><?=URL::Linkify(htmlentities($row['content']))?></span>
		<span class="blogmeta"> (from <?=htmlentities($row['from'])?>)</span>
	</div>
	<?php
}
$template->EndWindow();
$template->End();
?>