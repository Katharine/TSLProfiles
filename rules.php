<?php
require_once 'utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->Start("Rules");
$template->StartWindow("Meta");
?>
<p>By attaching a TSL Profiles attachment, you agree to be bound by these terms and conditions. Failure to comply can result in account removal.<br />
The administrator's decision on all matters is final and unquestionable. So there.</p>
<?php
$template->EndWindow();
$template->StartWindow("General");
?>
<p>Any content posted on TSL Profiles is the opinion of the poster, not that of TSL Profiles or its administrators.<br />
TSL Profiles does not take any responsibility for users' opinions.</p>
<p>TSL Profiles does not make any guarantee that the service will always be available, and is not responsible for any loss
that may be caused by the service being unavailable.</p>
<p>Accounts will be removed on request</p>
<?php
$template->EndWindow();
$template->StartWindow("Ratings");
?>
<p>By using TSL Profiles, You agree that You will not rate any Agent without reason for doing so.
"I don't like them." is not a valid reason to rate someone negatively in a skill category, although it may
be valid reason to do so in a more subjective category, e.g. "Friendliness."</p>
<p>Ratings will <strong>never</strong> be removed on request. Clearly abusive ratings will be cleared.</p>
<?php
$template->EndWindow();
$template->StartWindow("Chat");
?>
<p>All text sent via TSL Profiles' "chat" tab is temporarily stored on the server, until this is no longer needed.
Chat text will be removed as soon as the other end retrieves it, making the average time that chat is logged for about one second.<br />
However, it is possible that the other end will fail to pick up the chat. In this case, the chat will remain on the server until the daily
data purge, run at midnight GMT.</p>
<?php
$template->EndWindow();
$template->End();
?>