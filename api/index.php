<?php
require_once '../utils.php';
// Convenience functions, because I'm lazy
function s($title)
{
	global $template;
	$template->StartWindow($title);
}

function e()
{
	global $template;
	$template->EndWindow();
}

$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->StartPage("API Methods");
$template->Sidebar();
s("Overview")
?>
<p>The API can be accessed at <?=API_ROOT?>/&lt;format&gt;/&lt;method&gt;. It supports both GET and POST requests, although if you want to 
use it RESTfully, you should use GET to read and POST to set/delete. The supported formats are:
<ul>
	<li><a href="http://blog.secondlife.com/2006/07/19/web-services-serialization-format/">LLSD</a></li>
	<li><a href="http://json.org">JSON</a></li>
	<li><a href="http://json.org">JSON-tidy</a> - the same as JSON, but indented for human legibility</li>
	<li><a href="http://php.net/serialize">serialize</a></li>
	<li>LSL - an LSL parsable format, using the functions provided below.</li>
</ul>
<p>Responses will always feature a "method" value, which contains the name of the method called, and a "success" value, which is true or false depending on success.</p>
<h2>Success</h2>
<p>If a request is successful, a "response" object/map will be present, containing the response. Here is an example response (JSON):</p>
<pre class="code">
{
	"method":"RequestFoo",
	"success":1,
	"response":
	{
		"foo":"bar"
	}
}
</pre>
<h2>Failure</h2>
<p>If a request fails, "success" will be false, and an "error" will be present. An example (JSON again):</p>
<pre class="code">
{
	"method":"RequestBar",
	"success":0,
	"error":"Bars aren't requestable."
}
</pre>
<?php
e();
s("Useful Utilities")
?>
<p>These are some useful tools for interacting with these APIs:</p>
<ul>
	<li>
		<strong>LSL</strong>
		<ul>
			<li><a href="/utils/lsl.txt">Useful LSL functions</a> for interacting with the API. Includes methods for both making requests and parsing responses.
			Hopefully the comments are self explanatory.</li>
			<li><a href="/utils/lslexample.txt">Example</a> using the helper functions above. You will have to replace the first line of this script with those functions.</li>
		</ul>
	</li>
	<li>
		<strong>PHP5</strong>
		<ul>
			<li><a href="/utils/llsd.phps">LLSD handling functions</a> - Use llsd_decode on the response from the LLSD API to decode it.</li>
			<li><a href="/utils/LSLTypes.phps">LSL types</a> - Needed if you want to use the "serialize" API. It contains useful methods for dealing with vectors and quaternions, and any position types are returned as LLVector3s.
				Also helpful for parsing the vectors returned by the LLSD API.</li>
		</ul>
	</li>
	<li><a href="http://secondlife.com/developers/third_party_reg/llsd_libs/llsd.php-lib">LL's <strong>PHP4</strong> LLSD library</a></li>
	<li><a href="http://secondlife.com/developers/third_party_reg/llsd_libs/llsd.rb-lib">LL's <strong>Ruby</strong> LLSD library</a></li>
	<li><a href="http://secondlife.com/developers/third_party_reg/llsd_libs/llsd.py-lib">LL's <strong>Python</strong> LLSD library</a></li>
	<li><a href="http://secondlife.com/developers/third_party_reg/llsd_libs/LLSD.pm-lib">LL's <strong>PERL</strong> LLSD library</a></li>
</ul>
<?php
e();
s("Methods")
?>
<p>Methods are documented <a href="/methods.php">here</a>.</p>
<?php
e();
$template->EndPage();
?>