<?php
require_once '../utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->StartPage("Rating Help");
$template->Sidebar();
$template->StartWindow("Giving Ratings");
?>
<p>There are two ways to give ratings: You can either do so in-world using the attachment, or on this site via profile pages.</p>
<h3 class="docs">Using the attachment</h3>
<ol>
	<li>Stand somewhere near the person you wish to rate</li>
	<li>Say ",rate" (that's a comma, not a full stop)</li>
	<li>Select their name from the list</li>
	<li>Pick a dimension to rate in</li>
	<li>Press the button (from +1 to -1) that represents your view</li>
</ol>
<h3 class="docs">Through the site</h3>
<ol>
	<li>Navigate to the page of the person you wish to rate. You can do this using the <a href="/search/">search</a></li>
	<li>Click the "Ratings" tab</li>
	<li>Click the number in the correct category that reflects the rating.</li>
</ol>
<?php
$template->EndWindow();
$template->EndPage();
?>