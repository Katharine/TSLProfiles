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

function genlist($stuff)
{
	foreach($stuff as $name => $val)
	{
		if(is_array($val))
		{
			print "\t\t<li><code>".htmlentities($name)."</code><ul>";
			print genlist($val);
			print "</ul>\n";
		}
		else
		{
			print "\t\t<li><code>".htmlentities($name)."</code> = <i>".htmlentities($val)."</i></li>\n";
		}
	}
}

function method($methodname, $description, array $params, array $return)
{
	global $template;
	$template->StartWindow($methodname);
	?>
	<h1 style="text-transform:none;"><?=htmlentities($methodname)?></h1>
	<h2>Description</h2>
	<p><?=htmlentities($methodname)?></p>
	<h2>Parameters</h2>
	<ul>
		<?php
		$request = '';
		$done = array();
		foreach($params as $name => $val)
		{
			if(!in_array(substr($name,strpos($name,' ')+1),$done))
			{
				$done[] = substr($name,strpos($name,' ')+1);
				$type = substr($name,0,strpos($name,' '));
				$rval = 'foo';
				switch($type)
				{
					case 'string':
						$rval = array_rand(array_flip(array('foo','bar','baz','foobar')));
						break;
					case 'integer':
						$rval = rand(1,10);
						break;
					case 'vector':
						$rval = '<'.rand(0,255).', '.rand(0,255).', '.rand(0,255).'>';
						break;
					case 'boolean':
						$rval = rand(0,1);
						break;
				}
				$request .= "&".urlencode(substr($name,strpos($name,' ')+1))."=".urlencode($rval);
			}
			print "\t\t<li><code>".htmlentities($name)."</code> = <i>".htmlentities($val)."</i></li>\n";
		}
		$request = substr($request,1);
		?>
	</ul>
	<p>Request format (GET LLSD): <a href="<?=API_ROOT?>/LLSD/<?="{$methodname}?{$request}"?>" target="_blank"><?=API_ROOT?>/LLSD/<?="{$methodname}?{$request}"?></a></p>
	<h2>Response</h2>
	<ul>
		<?php
		genlist($return);
		?>
	</ul>
	<?php
	$template->EndWindow();
}

$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$template->StartPage("API Methods");
$template->Sidebar();
$template->StartWindow('API Methods');
?>
<p>This list of API methods documents everything you can do with the TSL Profiles API.<br />
Note that if you call these using LSL's <a href="http://lslwiki.net/lslwiki/wakka.php?wakka=llHTTPRequest">llHTTPRequest</a>,
'userid' and 'key' will be filled in for you, so don't bother passing those unless you want to authenticate
as someone other than yourself.</p>
<?php
$template->EndWindow();
method(
	'GetUserID',
	'GetUserID returns the TSLP ID of someone given their SL name.',
	array(
		'string first' => 'FirstName',
		'string last' => 'LastName'
	),
	array(
		'integer id' => 'UserID'
	)
);
method(
	'UserOnline',
	'UserOnline returns the specified user\'s online status. Responses are cached for five minutes.',
	array(
		'integer id' => 'UserID'
	),
	array(
		'boolean online' => 'Online status'
	)
);
method(
	'LocateUser',
	'Returns the specified user\'s location in SL, if permitted.',
	array(
		'integer id' => 'UserID'
	),
	array(
		'string sim' => 'Current Region',
		'vector position' => 'Current Position'
	)
);
method(
	'GetProfileInfo',
	'Returns the specified user\'s profile',
	array(
		'integer id' => 'UserID'
	),
	array(
		'string name' => 'SL name',
		'string key' => 'SL key',
		'integer sljoin' => 'SL join date',
		'integer tslpjoin' => 'TSLP join date',
		'map fields' => array(
			'string field 1' => 'value 1',
			'string field 2' => 'value 2',
			'string field x' => 'value x'
		)
	)
);
method(
	'DeleteProfileField',
	'Deletes the specified profile field',
	array(
		'integer userid' => 'UserID',
		'string key' => 'APIkey',
		'string field' => 'Field to delete'
	),
	array(
		'map fields' => array(
			'string field 1' => 'value 1',
			'string field 2' => 'value 2',
			'string field x' => 'value x'
		)
	)
);
method(
	'AddProfileField',
	'Adds a new profile field, and sets an initial value',
	array(
		'integer userid' => 'UserID',
		'string key' => 'API key',
		'string field' => 'Field Name',
		'string value' => 'Field value'
	),
	array(
		'map fields' => array(
			'string field 1' => 'value 1',
			'string field 2' => 'value 2',
			'string field x' => 'value x'
		)
	)
);
method(
	'UpdateProfileField',
	'Changes a field\'s value',
	array(
		'integer userid' => 'UserID',
		'string key' => 'API key',
		'string field' => 'Field Name',
		'string value' => 'New field value'
	),
	array(
		'map fields' => array(
			'string field 1' => 'value 1',
			'string field 2' => 'value 2',
			'string field x' => 'value x'
		)
	)
);
method(
	'GetRatings',
	'Returns the user\'s ratings, and optionally what you rated them if your login is specified.',
	array(
		'integer id' => 'User ID to get ratings for.',
		'string id' => 'Alternate way to specify user to get ratings for - SL agent key',
		'integer userid' => 'Your user ID (optional)',
		'string key' => 'Your API key (optional)'
	),
	array(
		'map ratings' => array(
			'map Category1' => array(
				'positive' => 'Aggregate positive rating in category1',
				'negative' => 'Aggregate negative rating in category1',
				'rated' => 'Your rating in category1. Only if userid and key were provided.'
			),
			'map Category2' => array(
				'positive' => 'Aggregate positive rating in category2',
				'negative' => 'Aggregate negative rating in category2',
				'rated' => 'Your rating in category2. Only if userid and key were provided.'
			)
		)
	)
);
method(
	'SetRating',
	'Sets your rating of the specified user',
	array(
		'integer id' => 'User ID to get ratings for.',
		'string id' => 'Alternate way to specify user to get ratings for - SL agent key',
		'integer userid' => 'Your user ID',
		'string key' => 'Your API key'
	),
	array(
		'map ratings' => array(
			'map Category1' => array(
				'positive' => 'Aggregate positive rating in category1',
				'negative' => 'Aggregate negative rating in category1',
				'rated' => 'Your rating in category1.'
			),
			'map Category2' => array(
				'positive' => 'Aggregate positive rating in category2',
				'negative' => 'Aggregate negative rating in category2',
				'rated' => 'Your rating in category2.'
			)
		)
	)
);
method(
	'SetPassword',
	'Sets your password. Note that doing this will change your API key.',
	array(
		'integer userid' => 'Your user ID',
		'string key' => 'Your API key',
		'string password' => 'Your new password'
	),
	array(
		'string key' => 'Your new API key.'
	)
);
method(
	'SetTracking',
	'Enables or disables public tracking of your position',
	array(
		'integer userid' => 'Your user ID',
		'string key' => 'Your API key',
		'boolean enabled' => 'Whether you can be tracked or not.'
	),
	array()
);
method(
	'GetMuteList',
	'Returns the names and IDs of people on your mute list',
	array(
		'integer userid' => 'Your user ID',
		'string key' => 'Your API key'
	),
	array(
		'array muted' => array(
			'map 0' => array(
				'string name' => 'SL name of muted user',
				'integer id' => 'ID of muted user'
			),
			'map 1' => array(
				'string name' => 'SL name of muted user',
				'integer id' => 'ID of muted user'
			),
			'map 2' => array(
				'string name' => 'SL name of muted user',
				'integer id' => 'ID of muted user'
			)
		)
	)
);
method(
	'Unmute',
	'Unmutes a muted user',
	array(
		'integer userid' => 'Your user ID',
		'string key' => 'Your API key',
		'id' => 'User to unmute'
	),
	array(
		'array muted' => array(
			'map 0' => array(
				'string name' => 'SL name of muted user',
				'integer id' => 'ID of muted user'
			),
			'map 1' => array(
				'string name' => 'SL name of muted user',
				'integer id' => 'ID of muted user'
			),
			'map 2' => array(
				'string name' => 'SL name of muted user',
				'integer id' => 'ID of muted user'
			)
		)
	)
);
method(
	'Mute',
	'Mutes an annoying user',
	array(
		'integer userid' => 'Your user ID',
		'string key' => 'Your API key',
		'id' => 'User to mute'
	),
	array(
		'array muted' => array(
			'map 0' => array(
				'string name' => 'SL name of muted user',
				'integer id' => 'ID of muted user'
			),
			'map 1' => array(
				'string name' => 'SL name of muted user',
				'integer id' => 'ID of muted user'
			),
			'map 2' => array(
				'string name' => 'SL name of muted user',
				'integer id' => 'ID of muted user'
			)
		)
	)
);
method(
	'InitiateChatSession',
	'Starts a chat session and sends a request to the target user in SL',
	array(
		'integer userid' => 'Your user ID',
		'string key' => 'Your API key',
		'integer target' => 'The target\'s User ID'
	),
	array(
		'string sessionid' => 'Your chat session\'s ID.'
	)
);
method(
	'RingChatSession',
	'Check a chat session\'s status',
	array(
		'string sessionid' => 'Your chat session ID'
	),
	array(
		'boolean completed' => 'Whether the system is finished waiting',
		'boolean connected' => 'Whether the connection was successful. (Only if completed is true)',
		'string reason' => 'Why the connection was not successful, if it wasn\'t'
	)
);
method(
	'PollChat',
	'Poll the chat for new messages',
	array(
		'string sessionid' => 'Your chat session ID',
		'string lines' => 'A newline separated string of lines to send',
		'string origin' => 'Either "in" or "out". You want "out"'
	),
	array(
		'string name' => 'The name of the user on the other side',
		'boolean terminated' => 'Whether the chat was ended',
		'integer num' => 'The number of lines waiting for you',
		'array lines' => 'An array of lines from the other user'
	)
);
method(
	'TerminateChatSession',
	'Ends a chat session. Always use this when leaving.',
	array(
		'sessionid' => 'Your chat session ID'
	),
	array()
);
method(
	'GetStatus',
	'Check if the target is AFK or busy',
	array(
		'integer id' => 'The ID of the target user'
	),
	array(
		'boolean afk' => 'True if the user is away',
		'boolean busy' => 'True if the user is busy'
	)
);
method(
	'ListUsers',
	'Gets a list of users matching the term. Pass an empty string for "search" to get everyone.',
	array(
		'string search' => 'The beginning of the SL name of the person being searched for.'
	),
	array(
		'integer count' => 'The number of results returned.',
		'map resultid' => array(
			'first' => "The first name of that result",
			'last' => "The last name of that result"
		)
	)
);
method(
	'GetStatus',
	'Returns the current status of the specified avatar',
	array(
		'integer id' => 'The user ID of the target avatar'
	),
	array(
		'boolean afk' => 'True if the agent is AFK',
		'boolean busy' => 'True if the agent is "Busy" according to SL'
	)
);
method(
	'GetComments',
	'Returns an array of comments on the specified agent',
	array(
		'integer id' => 'User ID to get ratings for.',
		'string id' => 'Alternate way to specify user to get ratings for - SL agent key'
	),
	array(
		'array comments' => array(
			'map 0' => array(
				'map commenter' => array(
					'id' => 'Commenter ID',
					'name' => 'Commenter name'
				),
				'string comment' => 'The comment on you'
			),
			'map ...' => 'etc.'
		)
	)
);
method(
	'GiveComment',
	'Gives the specified user a comment',
	array(
		'integer userid' => "Your user ID",
		'string key' => "Your API key",
		'string command' => 'Your comment'
	),
	array()
);
method(
	'GetComments',
	'Returns a list of comments for the specified agent',
	array(
		'integer id' => 'User ID to get comments for.',
		'string id' => 'Alternate way to specify user to get comments for - SL agent key'
	),
	array(
		'array comments' => array(
			'map 0' => array(
				'map commenter' => array(
					'integer id' => 'User ID',
					'string name' => 'SL Name'
				),
				'string comment' => 'The text of the comment',
				'integer time' => 'When the comment was left'
			),
			'map 1' => '...'
		)
	)
);
$template->EndPage();
?>