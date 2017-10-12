<?php
require_once 'utils.php';
$slug = '';
if(isset($_GET['id']))
{
	$slug = (int)$_GET['id'];
}
else
{
	$url = URL::parse($_SERVER['REDIRECT_URL']);
	$slug = $url[0];
}
try
{
	$link = new MySQL();
	$login = new Login($link);
	$template = new Template($login);
	$template->AddScript('groupinfo');
	$group = new Group($link,$slug);
}
catch (Exception $e)
{
	header("Content-Type: text/plain");
	die("Error rendering page: ".$e->getMessage());
}
$template->Start("Group Information: {$group->name}");
$template->StartWindow(htmlentities($group->name));
$ismember = $login->loggedin && $group->ismember($login->id);
?>

<script type="text/javascript">
<!--
var GROUP_ID = <?=$group->id?>;
var GROUP_NAME = '<?=addslashes($group->name)?>';
var GROUP_FOUNDER = <?=$group->founder?>;
var GROUP_DESCRIPTION = '<?=str_replace(array("\n","\r"),array('\n',''),addslashes($group->description))?>';
var GROUP_OPEN = <?=(int)$group->open?>;
var gIsLoggedIn = <?=(int)$login->loggedin?>;
var gIsMember = <?=(int)$ismember?>;
// -->
</script>
<div class="tabset" id="tabset">
	<span class="tab" id="chartertab">
		<a href="#">Charter</a>
	</span>
	<span class="tab" id="membertab">
		<a href="#">Members</a>
	</span>
	<?php
	if(!$ismember)
	{
	?>
		<span class="tab" id="jointab">
			<a href="#">Join</a>
		</span>
	<?php
	}
	else
	{
	?>
		<span class="tab" id="settingstab">
			<a href="#">Settings</a>
		</span>
	<?php
	}
	?>
</div>
<div id="charter" style="display: block;">
	<p><?=nl2br(htmlentities($group->description))?></p>
</div>
<div id="members" style="display: none;">
	<ul>
		<li>Loading...</li>
	</ul>
</div>
<?php
if(!$ismember)
{
?>
	<div id="join" style="display: none;">
		<div id="join-permitted" style="display:<?=$group->open()?'block':'none'?>;">
			<p>Anyone is able to join this group.</p>
			<p><a href="#" onclick="joingroup(); return false;">Join</a></p>
		</div>
		<div id="join-inviteonly" style="display:<?=!$group->open()?'block':'none'?>;">
			<p>You must be invited to join this group. Try asking a member, although remember that the group may frown upon being asked for membership.<br />
			If you <em>have</em> been invited, check your IMs for a link to join the group.</p>
		</div>
	</div>
<?php
}
$template->EndWindow();
$template->End();
?>