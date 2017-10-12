<?php
require_once '../utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->StartPage("&micro;Blog Help");
$template->Sidebar();
$template->StartWindow("Text posts");
?>
<p>There are two ways to send a basic text post - in-world and through your profile.</p>
<h3 class="docs">In-world</h3>
<ol>
	<li>Wear the attachment</li>
	<li>Say ",blog Something exciting happened!" (that's a comma!) to add "Something exciting happened!" to your &micro; blog</li>
</ol>
<h3 class="docs">On this site</h3>
<ol>
	<li>Log in</li>
	<li>Visit <?php
		if($login->loggedin)
		{
			print '<a href="/profiles/'.URL::clean($login->name).'">your profile</a>';
		}
		else
		{
			print 'your profile';
		}
	?>.</li>
	<li>Click the "&micro;Blog" tab.</li>
	<li>Type an entry in the box</li>
	<li>Click "Submit"</li>
</ol>
<?php
$template->EndWindow();
$template->StartWindow("Image posts");
?>
<p>To make an image post, perform the following steps:</p>
<ol>
	<li>Click "Snapshot" in the Second Life toolbar</li>
	<li>Pick the "Send a postcard" radio button</li>
	<li>Put the "quality" slider around 90</li>
	<li>Take the snapshot</li>
	<li>Enter "<strong>blog@tslprofiles.com</strong>" in the "To:" field</li>
	<li>Type a brief caption in the "Subject" field and a short post to accompany it as the message</li>
	<li>Send the postcard</li>
	<li>Be patient: email out of Second Life can be slow!</li>
</ol>
<?php
$template->EndWindow();
$template->StartWindow("Keeping track");
?>
<p>To help you keep track of &micro;Blogs, we provide RSS feeds, which most recent browsers (including Internet Explorer 7, Safari and Firefox) can subscribe to.</p>
<p>For the global feed, which tracks everyone's &micro;Blogs, subscribe to <a href="/feed/">http://tslprofiles.com/feed/</a>.
For an individual user's feed, subscribe to http://tslprofiles.com/feeds/Their_Name, e.g. <a href="/feeds/Katharine_Berry">http://tslprofiles.com/feeds/Katharine_Berry</a>.</p>
<?php
$template->EndWindow();
$template->StartWindow("Twitter");
?>
<p>If you use <a href="http://twitter.com" target="_blank">Twitter</a>, you may like to enable Twitter syncing. This causes your µBlog entries to also be submitted to Twitter, and your Twitter entries to be pulled onto your µBlog.
If this is the case, simply do the following:</p>
<ol>
	<li>Visit your <?=$login->loggedin?'<a href="/you/settings">settings page</a>':'settings page'?></li>
	<li>Tick the box labelled "Enable Twitter syncing"</li>
	<li>Enter your Twitter username and password in the boxes that appear</li>
	<li>Click the button and wait. If it says it was successful, your &micro;Blog entries will now appear in Twitter and your Twitter entries in your &micro;Blog.</li>
</ol>
<p>Please remember that "Tweets" (entries on Twitter) are only allowed to be 160 characters long, so &micro;Blog entries longer than that will be cut off. 
Any images you send will be added to Twitter as a tinyurl pointing back to the image on your µBlog.</p>
<?php
$template->EndWindow();
$template->EndPage();
?>