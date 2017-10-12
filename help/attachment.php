<?php
require_once '../utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->StartPage('Attachment Documentation');
$template->Sidebar();
$template->StartWindow('Profile display');
?>
<p>You can display your profile in-world using your TSL Profiles attachment. To do so, simply say "<code>,show</code>"</p>
<p>To hide it again, say "<code>,hide</code>"</p>
<?php
$template->EndWindow();
$template->StartWindow('Profile Editing');
?>
<h2>Editing fields</h2>
<p>To edit a field using your attachment, use the following command:</p>
<div class="code">,set <i>field name</i> = <i>new field value</i></div>
<p><i>Field name</i> is the name of the field you want to set (e.g. Mood), and <i>new field value</i> is what you want to set it to. The spaces around the "=" are mandatory.</p>
<h2>Adding fields</h2>
<p>To add a field, use the following command:</p>
<div class="code">,add <i>field name</i></div>
<p>This will add the field with the value "unspecified." You can then use the <code>,set</code> command to set it.</p>
<h2>Deleting fields</h2>
<p>To delete a field, use the following command:</p>
<div class="code">,delete <i>field name</i></div>
<p>This will remove the field. You will not be asked for confirmation.</p>
<?php
$template->EndWindow();
$template->StartWindow('Rating')
?>
<p>To rate someone in your vicinity, say "<code>,rate</code>".<br />
This will trigger a series of dialog boxes allowing you to rate anyone around you. They do not need to have a TSL Profiles account.</p>
<p>If you just want to view someone's rating, click "Ignore" when asks you in what you want to rate them, as it shows their current ratings there.</p>
<?php
$template->EndWindow();
$template->StartWindow('Chat')
?>
<p>Chats can only be started from the website.<br />
If you mute someone when they request a chat, you can unmute them using the website, specifically the <a href="/you/muted/">muted users</a> page.</p>
<p>When ending calls one end should hang up. This can be done from the website using the "Hang up" button, or from in-world by saying "hangup" on channel 5 ("<code>/5hangup</code>")<br />
If you get a message telling you that a call was cancelled because you are currently engaged, when you aren't, use the "hangup" command to rectify the error.</p>
<?php
$template->EndWindow();
$template->StartWindow('&micro;Blog');
?>
<p>The &micro;Blog allows you to post short (less than 255 character) updates on stuff. You can update it from SL using your web browser by visiting its tab in your profile, or from Second Life
by using the <code>,blog</code> command, which is used as follows:</p>
<div class="code">,blog <i>Something to say</i></div>
<?php
$template->EndWindow();
$template->StartWindow("Password Reset");
?>
<p>So, you ignored the prompt to change your password before you forgot it, and have now forgotten you random ten character string?</p>
<p>Well then, not all is lost - simply say "<code>,passreset</code>" and you can have <em>another</em> random ten character password!</p>
<?php
$template->EndWindow();
$template->EndPage();
?>