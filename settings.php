<?php
require_once 'utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->AddScript('settings');
$template->StartPage("Settings");
$template->Sidebar();
$template->StartWindow("Privacy");
?>
<input type="checkbox" id="trackbox" name="trackbox" onchange="toggletrack();"<?=$login->track?' checked="checked"':''?> /><label for="trackbox">Allow people to see your location.</label><br />
<span id='trackprogress'></span>
<?php
$template->EndWindow();
$template->StartWindow("Password");
?>
<table>
	<tr>
		<td>Enter your new password:</td>
		<td><input type="password" id="newpass" onkeyup="passcompare(this,false)" /></td>
	</tr>
	<tr>
		<td>Re-enter your new password:</td>
		<td><input type="password" id="newpasscheck" onkeyup="passcompare(this,true)" /> <span id="passconf"></span></td>
	</tr>
</table>
<input type="button" onclick="passwordprocess();" value="Change password" id="passbutton" disabled="disabled" /><br />
<span id="passprogress"></span>
<?php
$template->EndWindow();
$template->StartWindow("Twitter Integration");
$data = explode(':',$login->profile->twitterlogin,2);
?>
<input type="checkbox" id="twitteron" name="twitteron" onchange="toggletwitter();"<?=$login->profile->twitterenabled?' checked="checked"':''?> /> <label for="twitteron">Synchronise with  <a href="http://twitter.com" target="_blank">Twitter</a>. (Note: enabling this may slightly slow down &micro;Blog posting)</label></p>
<div id="twitterdetails" <?=$login->profile->twitterenabled?'':' style="display: none;"'?>>
	<table>
		<tr>
			<td>Your Twitter username:</td>
			<td><input type="text" id="twittername" value="<?=$data[0]?>" onchange="cleartwitterpass();" /></td>
		</tr>
		<tr>
			<td>Your Twitter password:</td>
			<td><input type="password" id="twitterpass" value="<?=str_repeat('*',strlen($data[1]))?>" onfocus="cleartwitterpass();" /></td>
		</tr>
	</table>
</div>
<p><input type="button" value="Update Settings" onclick="settwitter();" id="settwitter" /></p>
<p id="twitterstatus"></p>
<?php
$template->EndWindow();
$template->StartWindow("Flickr Integration");
?>
<a name="flickr"></a>
<div id="flickron" <?=$login->hasflickrauth?'':' style="display:none;"'?>>
	<p>Your account is currently set up to use Flickr.</p>
	<p>
		Flickr tags: <input type="text" id="flickrtags" name="flickrtags" value="<?=htmlentities($login->flickrtags)?>" />
		<input type="button" id="flickrtagupdate" value="Update" onclick="flickrtags();" />
	</p>
	<!-- <p>Flickr privacy: </p> -->
	<p><input type="button" id="flickrlogout" value="De-authorise Flickr" onclick="deflickr();" /></p>
</div>
<div id="flickroff" <?=!$login->hasflickrauth?'':' style="display:none;"'?>>
	<p>Your account is not currently set up to use Flickr. If it was, you could send your &micro;Blog entries to Flickr.</p>
	<p><a href="/flickr.php">Enable Flickr</a></p>
</div>
<p id="flickrstatus"></p>
<?php
$template->EndWindow();
$template->EndPage();
?>