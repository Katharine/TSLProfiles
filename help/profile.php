<?php
require_once '../utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->StartPage("Profile Help");
$template->Sidebar();
$template->StartWindow("Editing");
?>
<p>There are two ways to edit your profile - in-world and on this site. It's much more intuitive on this site, but in-world can be more convenient if you're in-world.</p>
<h3 class="docs">On the site</h3>
<ol>
	<li>Go to <?=$login->loggedin?'<a href="/profiles/'.URL::clean($login->name).'/">your profile</a>':'your profile'?></li>
	<li>Click on an "[Edit]" link.</li>
	<li>Edit the text in the box which appears</li>
	<li>Click "Save"</li>
</ol>
<h3 class="docs">In-world</h3>
<ol>
	<li>Wear the attachment (which you should be doing already)</li>
	<li>Say ",set Some Field = Some Value" (that's a comma!) where "Some Field" is the name of the field to change, and "Some Value" is what to change it to.</li>
</ol>
<?php
$template->EndWindow();
$template->StartWindow("Adding fields");
?>
<h3 class="docs">On the site</h3>
<ol>
	<li>Press the "[Add field]" link on <?=$login->loggedin?'<a href="/profiles/'.URL::clean($login->name).'/">your profile</a>':'your profile'?></li>
	<li>Enter the name of the field in the upper box, and the value in the lower box.</li>
	<li>Press "Save"</li>
</ol>
<h3 class="docs">In-world</h3>
<ol>
	<li>Wear the attachment</li>
	<li>Say ",add Some Field" (that's a comma!) where "Some Field" is the name of the new field.</li>
	<li>Set its value as described in the "Editing" section of this page.</li>
</ol>
<?php
$template->EndWindow();
$template->StartWindow("Displaying In-world");
?>
<!-- Web tab doesn't exist on the TG -->
<!--
<p>There are two ways to display your profile in-world - on your profile's "Web" tab, and hovering above your attachment.</p>
-->
<p>To show your profile above your attachment, simply say ",show" (that's a comma!). 
You may also change the colour by saying ",setcolour Pink" or similar.</p>
<!--
<p>To show your profile, ratings, and last &micro;Blog entry in your SL profile (right click on yourself and pick "Profile"), set your web tab to the following URL:</p>
<p><a href="http://tslprofiles.com/webtab/<?=$login->loggedin?URL::clean($login->name):'Your_Name'?>/">http://tslprofiles.com/webtab/<?=$login->loggedin?URL::clean($login->name):'Your_Name'?>/</a></p>
<p>Note that when you visit this page in your web browser it will redirect to your regular profile. This is done to ensure that people get the right page if they click the "Open" button in your profile.</p>
-->
<?php
$template->EndWindow();
$template->EndPage();
?>