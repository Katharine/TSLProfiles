<?php
require_once '../utils.php';
function handler($errno, $errstr, $file, $line)
{
	/*
	if($errno == E_STRICT || $errno == E_NOTICE || $errno == 0)
	{
		return true;
	}
	global $type;
	respond($type,array(
		'success' => (int)false,
		'error' => "PHP error: {$errstr} in $file on $line"
	));
	die();
	*/
	if($errno == E_RECOVERABLE_ERROR)
	{
		global $type;
		respond($type,array(
			'success' => (int)false,
			'error' => "PHP error: {$errstr} in $file on $line"
		));
		die();
	}
	return true;
}
set_error_handler('handler');
function text_encode($data, $prefix = '')
{
	///*
	$text = '';
	foreach($data as $key => $val)
	{
		if(is_array($val) || is_object($val))
		{
			$text .= text_encode($val,"{$prefix}{$key}.")."\n";
		}
		else
		{
			if(is_bool($val))
			{
				$val = (int)$val;
			}
			$text .= "{$prefix}{$key}\n{$val}\n";
		}
	}
	if($prefix === '')
	{
		$text .= "warning\nThe 'text' API return type is deprecated. Please use LSL and the new parser functions, as they can handle newlines.\n";
	}
	return trim($text);
	//*/
	//return "success\n0\nerror\nYou are using an outdated attachment. Please visit secondlife://Boscombe/114/167/21 or IM Katharine Berry for a new one.";
}

function lsl_encode($data, $prefix = '')
{
	$text = '';
	foreach($data as $key => $val)
	{
		if(is_array($val) || is_object($val))
		{
			$text .= lsl_encode($val,"{$prefix}{$key}.")."\n";
		}
		else
		{
			if(is_bool($val))
			{
				$val = (int)$val;
			}
			else if(is_string($val))
			{
				$dat = explode("\n",$val);
				foreach($dat as &$str)
				{
					$str = rtrim($str);
					if(strlen($str) > 0)
					{
						if(trim($str,'\\') == '')
						{
							$str .= str_repeat('\\',strlen($str)).'\\';
						}
						else
						{
							$slashcount = 0;
							for($i = strlen($str) - 1; $i > 0 && $str[$i] == '\\'; --$i)
							{
								++$slashcount;
								$str = substr($str,0,$i);
							}
							$str .= str_repeat('\\',$slashcount*2).'\\';
						}
					}
					else
					{
						$str = '\\';
					}
				}
				$val = implode("\n",$dat);
				$val = substr($val,0,-1);
			}
			$text .= "{$prefix}{$key}\n{$val}\n";
		}
	}
	return trim($text);
}

function cleandata(array &$data)
{
	foreach($data as &$item)
	{
		if(is_array($item))
		{
			cleandata($item);
		}
		else if(is_numeric($item))
		{
			if(round($item) == $item)
			{
				$item = (int)$item;
			}
			else
			{
				$item = (float)$item;
			}
		}
	}
	return $data;
}

function respond($type, array $data)
{
	//cleandata($data);
	if($type == 'serialize')
	{
		header("Content-Type: text/plain");
		print serialize($data);
	}
	else if($type == 'llsd')
	{
		header("Content-Type: text/xml");
		print llsd_encode($data);
	}
	else if($type == 'text')
	{
		header("Content-Type: text/plain");
		print text_encode($data);
	}
	else if($type == 'lsl')
	{
		header("Content-Type: text/plain");
		print lsl_encode($data);
	}
	else
	{
		header("Content-Type: text/plain");
		$json = json_encode($data);
		if($type == 'json-tidy')
		{
			$strOut = '';
			$identPos = 0;
			for($loop = 0;$loop<= strlen($json) ;$loop++)
			{
				$_char = substr($json,$loop,1);
				//part 1
				if($_char == '}' || $_char == ']')
				{
					$strOut .= chr(13);
					$identPos--;
					for($ident = 0;$ident < $identPos;$ident++)
					{
						$strOut .= chr(9);
					}
				}
				//part 2
				$strOut .= $_char;
				//part 3
				if($_char == ',' || $_char == '{' || $_char == '[')
				{
					$strOut .= chr(13);
					if($_char == '{' || $_char == '[')
					{
						$identPos++;
					}
					for($ident = 0;$ident < $identPos;$ident++)
					{
						$strOut .= chr(9);
					}
				}
			}
			$json = $strOut;
		}
		print $json;
	}
}

function auth($quit = true, $adminonly = false)
{
	global $type;
	global $link;
	$userid = (int)$_GET['userid'];
	$key = $_GET['key'];
	$login = new Login($link);
	if($login->apiauth($userid, $key))
	{
		if(!$adminonly || $login->admin)
		{
			return $login;
		}
		else if($quit)
		{
			respond($type, array(
				'success' => false,
				'error' => 'Your access level is not high enough.'
			));
			die();
		}
		else
		{
			return false;
		}
	}
	else if($quit)
	{
		respond($type, array(
			'success' => false,
			'error' => 'Invalid User ID/API Key'
		));
		die();
	}
	return false;
}

$_GET = array_merge($_GET,$_POST);
$url = explode('/',rtrim($_SERVER['REDIRECT_URL'],'/'));
$request = array_pop($url);
$type = strtolower(array_pop($url));
$link = new MySQL();
if(!isset($_GET['userid']))
{
	if(preg_match('/sim[0-9]+\..+\.lindenlab\.com/i',gethostbyaddr($_SERVER['REMOTE_ADDR'])))
	{
		$headers = apache_request_headers();
		$link->query("SELECT `id` FROM `users` WHERE `key` = '".$link->escape($headers['X-SecondLife-Owner-Key'])."'");
		if($link->numrows() > 0)
		{
			$_GET['userid'] = $link->result(0,0);
			$_GET['key'] = Login::PASSWORD_SALT;
		}
	}
}
$response = array(
	'method' => $request,
);
if($request == 'UserOnline')
{
	try
	{
		$profile = new Profile($link,$_GET['id']);
		$response['success'] = true;
		$response['response'] = array('online' => (int)$profile->online);
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'GetUserID')
{
	$link->query("
		SELECT `id`
		FROM `users`
		WHERE `first` = '".$link->escape($_GET['first'])."' AND `last` = '".$link->escape($_GET['last'])."'"
	);
	if($link->numrows() > 0)
	{
		$response['success'] = true;
		$response['response'] = array('id' => $link->result(0,'id'));
	}
	else
	{
		$response['success'] = false;
		$response['error'] = "The agent '{$_GET['first']} {$_GET['last']}' does not exist!";
	}
}
else if($request == 'LocateUser')
{
	try
	{
		$profile = new Profile($link, $_GET['id']);
		$location = $profile->pos;
		if($location)
		{
			$response['success'] = true;
			$response['response'] = $location;
		}
		else
		{
			$response['success'] = false;
			$response['error'] = "You are not permitted to locate this user.";
		}
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'GetProfileInfo')
{
	try
	{
		$profile = new Profile($link,$_GET['id']);
		$response['success'] = true;
		$response['response'] = array(
			'key' => $profile->key,
			'name' => $profile->name,
			'sljoin' => $profile->sljoin,
			'tslpjoin' => $profile->tslpjoin,
			'grid' => $profile->grid,
			'fields' => $profile->getfields(),
			'image' => $profile->image
		);
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'DeleteProfileField')
{
	if(trim($_GET['field']) != '')
	{
		try
		{
			$login = auth();
			$profile = new Profile($link, (isset($_GET['profile']) && $login->mod)?$_GET['profile']:$_GET['userid']);
			$result = false;
			if($request == 'DeleteProfileField')
			{
				$result = $profile->deletefield($_GET['field']);
			}
			if($result)
			{
				$response['success'] = true;
				$response['response'] = array('fields' => $profile->getfields());
			}
			else
			{
				$response['success'] = false;
				$response['error'] = 'Invalid field name.';
			}
		}
		catch(Exception $e)
		{
			$response['success'] = false;
			$response['error'] = $e->getMessage();
		}
	}
	else
	{
		$response['success'] = false;
		$response['error'] = "'Field' cannot be blank.";
	}
}
else if($request == 'AddProfileField')
{
	if(trim($_GET['field']) != '')
	{
		if(strtolower(trim($_GET['field'])) == 'gender')
		{
			$response['success'] = false;
			$response['error'] = "'Gender' is a reserved field.";
		}
		else
		{
			try
			{
				auth();
				$profile = new Profile($link, $_GET['userid']);
				if($profile->addfield($_GET['field'])&&$profile->updatefield($_GET['field'],$_GET['value']))
				{
					$response['success'] = true;
					$response['response'] = array('fields' => $profile->getfields());
				}
				else
				{
					$response['success'] = false;
					$response['error'] = 'Fields cannot be blank or have duplicate names.';
				}
			}
			catch(Exception $e)
			{
				$response['success'] = false;
				$response['error'] = $e->getMessage();
			}
		}
	}
	else
	{
		$response['success'] = false;
		$response['error'] = "'Field' cannot be blank.";
	}
}
else if($request == 'UpdateProfileField')
{
	$login = auth();
	try
	{
		$profile = new Profile($link, (isset($_GET['profile']) && $login->mod)?$_GET['profile']:$_GET['userid']);
		if($profile->updatefield($_GET['field'], $_GET['value']))
		{
			$response['success'] = true;
			$response['response'] = array('fields' => $profile->getfields());
		}
		else
		{
			$response['success'] = false;
			$response['error'] = 'The specified field does not exist.';
		}
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'SetProfileFieldOrder')
{
	$login = auth();
	$link->query("
		UPDATE `users`
		SET `fieldorder` = '".$link->escape(serialize($_GET['field']))."'
		WHERE `id` = '".(int)((isset($_GET['profile']) && $login->mod)?$_GET['profile']:$_GET['userid'])."'
	");
	$response['success'] = true;
	$response['response'] = array();
}
else if($request == 'GetRatings')
{
	try
	{
		$loggedin = auth(false);
		$profile = new Profile($link, $_GET['id']);
		$ratings = $profile->getratings();
		if($loggedin)
		{
			foreach($ratings as $cat => &$val)
			{
				$val['rated'] = $profile->getrated($_GET['userid'],$cat);
			}
		}
		$response['success'] = true;
		ksort($ratings);
		$response['response'] = array('ratings' => $ratings);
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'SetRating')
{
	auth();
	$userid = (int)$_GET['userid'];
	try
	{
		$profile = new Profile($link,$_GET['target']);
		$rating = 0;
		if($_GET['rating'] > 0)
		{
			$rating = 1;
		}
		else if($_GET['rating'] < 0)
		{
			$rating = -1;
		}
		$profile->setrating($userid, array($_GET['category'] => $rating));
		$response['success'] = true;
		$ratings = $profile->getratings();
		foreach($ratings as $cat => &$val)
		{
			$val['rated'] = $profile->getrated($_GET['userid'],$cat);
		}
		ksort($ratings);
		$response['response'] = $ratings;
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'InitiateChatSession')
{
	$target = (int)$_GET['target'];
	$userid = (int)$_GET['userid'];
	auth();
	$login = new Login($link);
	$login->apiauth($userid,$_GET['key']);
	$link->query("
		SELECT `user`
		FROM `banlist`
		WHERE `user` = '{$target}' AND `banned` = '{$userid}'"
	);
	if($link->numrows() == 0)
	{
		$link->query("
			SELECT `rpc`
			FROM `users`
			WHERE `id` = '{$target}'"
		);
		if($link->numrows() > 0)
		{
			$rpc = $link->result(0,'rpc');
			if($rpc == '00000000-0000-0000-0000-000000000000')
			{
				$response['success'] = false;
				$response['error'] = "Device unreachable.";
			}
			else
			{
				$request = new RPC($rpc);
				$request->setint(1);
				$key = time().'-'.$userid.'-'.rand(0,10000000);
				$link->query("
					INSERT INTO `chats` (`id`,`origin`,`target`,`lastmessage`)
					VALUES (
						'".$link->escape($key)."',
						'{$userid}',
						'".$target."',
						NOW()
					)"
				);
				if($link->numrows() == 0)
				{
					$response['success'] = false;
					$response['error'] = "Chat session create failed due to an unexpected error.";
				}
				$request->setstring($key."<br />".$login->name);
				$r = $request->send();
				if($r['IntValue'] == 1)
				{
					$response['success'] = true;
					$response['response'] = array('sessionid' => $key);
				}
				else
				{
					$link->query("
						DELETE FROM `chats`
						WHERE `id` = '".$link->escape($key)."'"
					);
					$response['success'] = false;
					$response['error'] = "The number you have dialled is currently off the network.";
				}
			}
		}
		else
		{
			$response['success'] = false;
			$response['error'] = "No such user.";
		}
	}
	else
	{
		$response['success'] = false;
		$response['error'] = "The target has blocked you from chatting to them.";
	}
}
else if($request == 'RingChatSession')
{
	$session = $link->escape($_GET['sessionid']);
	$link->query("
		SELECT `accepted`,`reason`
		FROM `chats`
		WHERE `id` = '{$session}'"
	);
	if($link->numrows() > 0)
	{
		$status = $link->result(0,'accepted');
		$response['success'] = true;
		if($status == 'pending')
		{
			$response['response'] = array('completed' => (int)false);
		}
		else
		{
			$response['response'] = array('completed' => (int)true);
			if($status == 'yes')
			{
				$response['response']['connected'] = 1;
			}
			else
			{
				$response['response']['connected'] = 0;
				$response['response']['reason'] = $link->result(0,'reason');
				$link->query("DELETE FROM `chats` WHERE `id` = '{$session}'");
			}
		}
	}
	else
	{
		$response['success'] = false;
		$response['error'] = 'Unknown session ID';
	}
}
else if($request == 'AnswerChatRequest')
{
	$session = $link->escape($_GET['sessionid']);
	$accepted = $_GET['accepted'];
	$reason = isset($_GET['reason'])?$link->escape($_GET['reason']):'';
	$link->query("
		UPDATE `chats`
		SET `accepted` = '".($accepted?'yes':'no')."', `reason` = '{$reason}'
		WHERE `id` = '{$session}'"
	);
	if($link->numrows() > 0)
	{
		$response['success'] = true;
		$response['response'] = array();
	}
	else
	{
		$response['success'] = false;
		$response['error'] = "Unknown chat session ID.";
	}
	if(isset($_GET['mute']) && $_GET['mute'])
	{
		$link->query("
			SELECT `origin`,`target`
			FROM `chats`
			WHERE `id` = '{$session}'"
		);
		$banned = (int)$link->result(0,'origin');
		$user = (int)$link->result(0,'target');
		$link->query("
			INSERT IGNORE INTO `banlist` (`user`,`banned`)
			VALUES (
				'{$user}',
				'{$banned}'
			)"
		);
	}
}
else if($request == 'PollChat')
{
	$session = $link->escape($_GET['sessionid']);
	$heard = explode("\n",trim($_GET['lines']));
	foreach($heard as $line)
	{
		if(trim($line) == '')
		{
			continue;
		}
		$link->query("
			INSERT INTO `chatmessages` (`session`,`origin`,`message`,`time`)
			VALUES (
				'{$session}',
				'".$link->escape($_GET['origin'])."',
				'".$link->escape($line)."',
				NOW()
			)"
		);
	}
	$origin = ($_GET['origin']=='out')?'in':'out';
	$link->query("
		SELECT `message`
		FROM `chatmessages`
		WHERE `session` = '{$session}' AND `origin` = '{$origin}'
		ORDER BY `time` ASC"
	);
	$lines = array();
	while($row = $link->fetchrow())
	{
		$lines[] = $row['message'];
	}
	$link->query("
		DELETE FROM `chatmessages`
		WHERE `session` = '{$session}' AND `origin` = '{$origin}'"
	);
	$link->query("
		SELECT `".(($origin=='out')?'origin':'target')."`
		FROM `chats`
		WHERE `id` = '{$session}'"
	);
	$name = '(unknown)';
	try
	{
		$profile = new Profile($link,$link->result(0,0));
		$name = $profile->name;
	}
	catch(Exception $e)
	{
		//
	}
	$response['success'] = true;
	$link->query("SELECT COUNT(`id`) FROM `chats` WHERE `id` = '{$session}'");
	$terminated = !$link->result(0,0);
	$response['response'] = array(
		'name' => $name,
		'terminated' => (int)$terminated,
		'num' => count($lines),
		'lines' => $lines
	);
}
else if($request == 'TerminateChatSession')
{
	$session = $link->escape($_GET['session']);
	$link->query("
		DELETE FROM `chats`
		WHERE `id` = '{$session}'"
	);
	if($link->numrows() > 0)
	{
		$response['success'] = true;
		$response['response'] = array();
	}
	else
	{
		$response['success'] = false;
		$response['error'] = 'Unknown session ID';
	}
}
else if($request == 'SetPassword')
{
	$new = $_GET['password'];
	$userid = (int)$_GET['userid'];
	$key = $_GET['key'];
	$login = new Login($link);
	if($login->apiauth($userid,$key))
	{
		if(strlen($new) >= 4)
		{
			$login->setpass($new);
			$newkey = $login->key;
			$response['success'] = true;
			$response['response'] = array('key' => $newkey);
		}
		else
		{
			$response['success'] = false;
			$response['error'] = "Password too short.";
		}
	}
	else
	{
		$response['success'] = false;
		$response['error'] = "Invalid User ID/API key combination.";
	}
}
else if($request == 'SetTracking')
{
	$track = (bool)$_GET['enabled'];
	$login = new Login($link);
	if($login->apiauth($_GET['userid'],$_GET['key']))
	{
		$login->settrack($track);
		$response['success'] = true;
		$response['response'] = array();
	}
	else
	{
		$response['success'] = false;
		$response['error'] = "Invalid User ID/API key combination.";
	}
}
else if($request == 'GetMuteList')
{
	$id = (int)$_GET['userid'];
	$key = $_GET['key'];
	$login = new Login($link);
	if($login->apiauth($id,$key))
	{
		$response['success'] = true;
		$response['response'] = array(
			'muted' => $login->getmutelist()
		);
	}
	else
	{
		$response['success'] = false;
		$response['error'] = "Invalid User ID/API key combination.";
	}
}
else if($request == 'Unmute')
{
	$id = (int)$_GET['userid'];
	$key = $_GET['key'];
	$login = new Login($link);
	if($login->apiauth($id,$key))
	{
		$login->unmute($_GET['id']);
		$response['success'] = true;
		$response['response'] = array(
			'muted' => $login->getmutelist()
		);
	}
	else
	{
		$response['success'] = false;
		$response['error'] = "Invalid User ID/API key combination.";
	}
}
else if($request == 'Mute')
{
	$id = (int)$_GET['userid'];
	$key = $_GET['key'];
	$login = new Login($link);
	if($login->apiauth($id,$key))
	{
		$login->mute($_GET['id']);
		$response['success'] = true;
		$response['response'] = array(
			'muted' => $login->getmutelist()
		);
	}
	else
	{
		$response['success'] = false;
		$response['error'] = "Invalid User ID/API key combination.";
	}
}
else if($request == 'ListUsers')
{
	$name = isset($_GET['search'])?$_GET['search']:'-';
	$link->query("
		SELECT `first`,`last`,`id`
		FROM `users`
		WHERE CONCAT(`first`,' ',`last`) LIKE '".str_replace(array('%','_'),array('\\%','\\_'),$link->escape($name))."%'
		ORDER BY `first`,`last`"
	);
	$r = array();
	while($row = $link->fetchrow())
	{
		$r[$row['id']] = array(
			'first' => $row['first'],
			'last' => $row['last']
		);
	}
	$response['success'] = true;
	$response['response'] = array(
		'count' => count($r),
		'results' => $r
	);
}
else if($request == 'SetRPC')
{
	auth();
	$userid = (int)$_GET['userid'];
	$chan = $link->escape($_GET['channel']);
	$link->query("UPDATE `users` SET `rpc` = '{$chan}' WHERE `id` = '{$userid}'");
	$response['success'] = true;
	$response['response'] = array(
		'rpc' => $chan
	);
}
else if($request == 'GetStatus')
{
	try
	{
		$profile = new Profile($link,(int)$_GET['id']);
		$status = $profile->status;
		if($status > -1)
		{
			$response['success'] = true;
			$response['response'] = array(
				'afk' => ($status & LSL::AGENT_AWAY)?1:0,
				'busy' => ($status & LSL::AGENT_BUSY)?1:0
			);
		}
		else
		{
			$response['success'] = false;
			$response['error'] = 'Status data is not being transmitted.';
		}
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'GetComments')
{
	try
	{
		$profile = new Profile($link,$_GET['id']);
		$response['success'] = true;
		$response['response'] = array(
			'comments' => $profile->getcomments()
		);
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'GiveComment')
{
	$login = auth();
	$userid = (int)((isset($_GET['commenter']) && $login->mod)?$_GET['commenter']:$_GET['userid']);
	try
	{
		$profile = new Profile($link,$_GET['id']);
		if(trim($_GET['comment']) == '')
		{
			$profile->removecomment($userid);
		}
		else
		{
			$profile->setcomment($userid,$_GET['comment']);
		}
		$response['success'] = true;
		$response['response'] = array();
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'SetNotes')
{
	auth();
	$userid = (int)$_GET['userid'];
	try
	{
		$profile = new Profile($link,$_GET['id']);
		$profile->setnote($userid,$_GET['note']);
		$response['success'] = true;
		$response['response'] = array();
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'GetNotes')
{
	auth();
	$userid = (int)$_GET['userid'];
	try
	{
		$profile = new Profile($link,$_GET['id']);
		$response['success'] = true;
		$response['response'] = array(
			'note' => $profile->getnote($userid)
		);
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'FindPeople')
{
	$query = $_GET['search'];
	$limit = (isset($_GET['limit'])&&$_GET['limit']>0&&$_GET['limit']<=50)?(int)$_GET['limit']:10;
	if(strlen($query) < 1)
	{
		$response['success'] = false;
		$response['error'] = "Query too short.";
	}
	else
	{
		$query = $link->escape($query);
		$link->query("
			SELECT CONCAT(`first`,' ',`last`) AS `name`, (
				SELECT `users`.`id` FROM `users`
				WHERE `users`.`key` = `keys`.`key`
			) AS `account`, `key`
			FROM `keys`
			WHERE CONCAT(`first`,' ',`last`) LIKE '{$query}%'
			ORDER BY `first`,`last`
			LIMIT {$limit}"
		);
		$names = array();
		while($row = $link->fetchrow())
		{
			$names[] = array(
				'name' => $row['name'],
				'account' => (int)$row['account'],
				'key' => $row['key']
			);
		}
		$response['success'] = true;
		$response['response'] = array('names' => $names);
	}
		
}
else if($request == 'GetFriendList')
{
	$userid = (int)$_GET['userid'];
	$key = $_GET['key'];
	$login = new Login($link);
	if($login->apiauth($userid,$key))
	{
		try
		{
			$list = new FriendsList($link,$login);
			$response['success'] = true;
			$response['response'] = array('friends' => $list->getlist());
		}
		catch(Exception $e)
		{
			$response['success'] = false;
			$response['error'] = $e->getMessage();
		}
	}
	else
	{
		$response['success'] = false;
		$response['error'] = 'Invalid User ID/API Key';
	}
}
else if($request == 'AddFriend')
{
	$userid = $_GET['userid'];
	$id = $_GET['id'];
	$key = $_GET['key'];
	$login = new Login($link);
	if($login->apiauth($userid,$key))
	{
		try
		{
			$list = new FriendsList($link,$login);
			$list->add($id);
			$response['success'] = true;
			$response['response'] = array();
		}
		catch(Exception $e)
		{
			$response['success'] = false;
			$response['error'] = $e->getMessage();
		}
	}
	else
	{
		$response['success'] = false;
		$response['error'] = 'Invalid User ID/API Key';
	}
}
else if($request == 'DeleteFriend')
{
	$userid = $_GET['userid'];
	$id = $_GET['id'];
	$key = $_GET['key'];
	$login = new Login($link);
	if($login->apiauth($userid,$key))
	{
		try
		{
			$list = new FriendsList($link,$login);
			$list->remove($id);
			$response['success'] = true;
			$response['response'] = array();
		}
		catch(Exception $e)
		{
			$response['success'] = false;
			$response['error'] = $e->getMessage();
		}
	}
	else
	{
		$response['success'] = false;
		$response['error'] = 'Invalid User ID/API Key';
	}
}
else if($request == 'NameToKey')
{
	$name = explode(' ',$link->escape($_GET['name']),2);
	if(!isset($name[1]))
	{
		$name[1] = '';
	}
	$link->query("
		SELECT `key`
		FROM `keys`
		WHERE `first` = '{$name[0]}' AND `last` = '{$name[1]}' AND `first` != '' AND `last` != ''"
	);
	if($link->numrows() > 0)
	{
		$response['success'] = true;
		$response['response'] = array(
			'key' => $link->result(0,'key')
		);
	}
	else
	{
		$response['success'] = false;
		$response['error'] = "No such agent.";
	}
}
else if($request == 'GetLoginInfo')
{
	$first = $_GET['first'];
	$last = $_GET['last'];
	$pass = $_GET['password'];
	$login = new Login($link);
	if($login->authenticate($first, $last, $pass,false))
	{
		$response['success'] = true;
		$response['response'] = array(
			'userid' => $_COOKIE['uid'],
			'key' => $_COOKIE['sid']
		);
	}
	else
	{
		$response['success'] = false;
		$response['error'] = "Invalid username/password combination.";
	}
}
else if($request == 'GetUserBlogEntries')
{
	$id = $_GET['id'];
	$limit = (isset($_GET['limit'])&&is_numeric($_GET['limit'])&&$_GET['limit']>0)?(int)$_GET['limit']:0;
	try
	{
		$blog = new Microblog($link, $id);
		$response['success'] = true;
		$response['response'] = array(
			'posts' => $blog->mergedentries($limit)
		);
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'MakeBlogEntry')
{
	auth();
	$userid = (int)$_GET['userid'];
	try
	{
		$blog = new Microblog($link, $userid);
		$blog->post($_GET['post'], isset($_GET['from'])?$_GET['from']:'api');
		$response['success'] = true;
		$response['response'] = array();
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'DeleteTextBlogEntry')
{
	$login = auth();
	try
	{
		if(!$login->mod)
		{
			throw new Exception("Only moderators can delete blog posts.");
		}
		$blog = new Microblog($link, (int)$_GET['blog']);
		$blog->deletetext((int)$_GET['post']);
		$response['success'] = true;
		$response['response'] = array();
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'DeleteImageBlogEntry')
{
	$login = auth();
	try
	{
		if(!$login->mod)
		{
			throw new Exception("Only moderators can delete blog posts.");
		}
		$blog = new Microblog($link, (int)$_GET['blog']);
		$blog->deleteimage((int)$_GET['post']);
		$response['success'] = true;
		$response['response'] = array();
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'CreateGroup')
{
	auth();
	$userid = (int)$_GET['userid'];
	try
	{
		Group::Create($link,$_GET['name'], $_GET['description'], $userid, (bool)$_GET['open'], $_GET['image']);
		$response['success'] = true;
		$response['response'] = array();
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'GroupMembers')
{
	$group = (int)$_GET['group'];
	try
	{
		$group = new Group($link, $group);
		$members = $group->members();
		$return = array();
		foreach($members as $member)
		{
			$return[] = $member['id'];
		}
		$response['success'] = true;
		$response['response'] = array(
			'count' => count($members),
			'members' => $return
		);
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'GroupInfo')
{
	try
	{
		$group = new Group($link, $_GET['id']);
		$r = array(
			'description' => $group->description,
			'open' => (bool)$group->open,
			'image' => $group->image
		);
		$response['response'] = $r;
		$response['success'] = true;
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'RemoveFromGroup')
{
	auth();
	try
	{
		$group = new Group($link,$_GET['group']);
		$group->remove(isset($_GET['id'])?(int)$_GET['id']:(int)$_GET['userid']);
		$response['success'] = true;
		$response['response'] = array();
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'JoinGroup')
{
	auth();
	try
	{
		$group = new Group($link, $_GET['group']);
		$group->add($_GET['userid'],false);
		$response['success'] = true;
		$response['response'] = array();
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'ListGroups')
{
	$query = isset($_GET['search'])?"WHERE `name` LIKE '%".$link->escape($_GET['search'])."%'":'';
	$link->query("
		SELECT `id`,`name`
		FROM `groups`
		{$query}
		ORDER BY `name`"
	);
	$groups = array();
	while($row = $link->fetchrow())
	{
		$groups[] = array(
			'id' => $row['id'],
			'name' => $row['name']
		);
	}
	$response['success'] = true;
	$response['response'] = array('groups' => $groups);
}
else if($request == 'MyGroups')
{
	auth();
	$userid = (int)$_GET['userid'];
	$link->query("
		SELECT `group`, (
			SELECT `groups`.`name`
			FROM `groups`
			WHERE `groups`.`id` = `groupmembers`.`group`
		) AS `groupname`
		FROM `groupmembers`
		WHERE `user` = '{$userid}'
		ORDER BY `groupname`"
	);
	$groups = array();
	while($row = $link->fetchrow())
	{
		$groups[] = array(
			'id' => (int)$row['group'],
			'name' => $row['groupname']
		);
	}
	$response['success'] = true;
	$response['response'] = array('groups' => $groups);
}
else if($request == 'TwitterTestLogin')
{
	$response['response'] = array('working' => Twitter::TestDetails($_GET['user'],$_GET['pass']));
	$response['success'] = true;
}
else if($request == 'TwitterStoreDetails')
{
	$login = new Login($link);
	if($login->apiauth($_GET['userid'],$_GET['key']))
	{
		$login->settwitter((int)$_GET['enable']);
		if(isset($_GET['user']) && isset($_GET['pass']))
		{
			$login->settwitterlogin($_GET['user'],$_GET['pass']);
		}
		$response['success'] = true;
		$response['response'] = array();
	}
	else
	{
		$response['success'] = false;
		$response['error'] = "Invalid User ID/API key combination.";
	}
}
else if($request == 'GetModeratorList' || $request == 'GetAdministratorList')
{
	auth(true,true);
	$check = ($request == 'GetModeratorList')?Login::SPECIAL_MODERATOR:Login::SPECIAL_ADMIN;
	$link->query("
		SELECT 
			CONCAT(`first`,' ',`last`) AS `name`,
			`id`
		FROM `users`
		WHERE (`special` & {$check}) > 0"
	);
	$results = array();
	while($row = $link->fetchrow())
	{
		$results[] = array(
			'name' => $row['name'],
			'id' => (int)$row['id']
		);
	}
	$response['success'] = true;
	$response['response'] = array(
		'entries' => $results
	);
}
else if($request == 'AddModerator' || $request == 'AddAdministrator')
{
	auth(true,true);
	$bit = ($request == 'AddModerator')?Login::SPECIAL_MODERATOR:Login::SPECIAL_ADMIN;
	$id = (int)$_GET['id'];
	$link->query("
		UPDATE `users`
		SET `special` = (`special` | {$bit})
		WHERE `id` = '{$id}'"
	);
	$response['success'] = true;
	$response['response'] = array();
}
else if($request == 'DeleteModerator' || $request == 'DeleteAdministrator')
{
	auth(true,true);
	$bit = ($request == 'DeleteModerator')?~Login::SPECIAL_MODERATOR:~Login::SPECIAL_ADMIN;
	$id = (int)$_GET['id'];
	$link->query("
		UPDATE `users`
		SET `special` = (`special` & {$bit})
		WHERE `id` = '{$id}'"
	);
	
	$response['success'] = true;
	$response['response'] = array();
}
else if($request == 'DeFlickr')
{
	auth()->clearflickrtoken();
	$response['success'] = true;
	$response['response'] = array();
}
else if($request == 'GetFlickrSettings')
{
	$login = auth();
	$response['response'] = array(
		'tags' => $login->flickrtags,
		'privacy' => $login->flickrprivacy
	);
	$response['success'] = true;
}
else if($request == 'SetFlickrTags')
{
	auth()->setflickrtags($_GET['tags']);
	$response['response'] = array();
	$response['success'] = true;
}
else if($request == 'SetFlickrPrivacy')
{
	auth()->setflickrprivacy($_GET['privacy']);
	$response['response'] = array();
	$response['success'] = true;
}
else if($request == 'GetBlogComments')
{
	$blog = new Microblog($link, $_GET['blog']);
	$comments = $blog->comments($_GET['type'], (int)$_GET['entry']);
	$response['response'] = array(
		'comments' => $comments
	);
}
else if($request == 'AddBlogComment')
{
	$id = auth()->getid();
	try
	{
		$id = Microblog::AddBlogComment($link,$id, $_GET['type'], (int)$_GET['post'], $_GET['comment']);
		$response['response'] = array(
			'id' => $id
		);
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else if($request == 'DeleteBlogComment')
{
	try
	{
		$login = auth();
		$comment = (int)$_GET['comment'];
		$link->query("
			SELECT `entry`,`commenter`
			FROM `blogcomments`
			WHERE `id` = {$comment}"
		);
		if($link->numrows() == 0)
		{
			throw new Exception("No such comment");
		}
		extract($link->fetchrow(),EXTR_PREFIX_ALL,'db');
		$link->query("
			SELECT `poster` AS `blog_owner`
			FROM `microblog`
			WHERE `id` = '{$db_entry}'"
		);
		extract($link->fetchrow(),EXTR_PREFIX_ALL,'db');
		if($db_blog_owner != $login->getid() && $db_commenter != $login->getid() && !$login->ismod())
		{
			throw new Exception("Not authorised!");
		}
		$blog = new Microblog($link, $db_blog_owner);
		$blog->deletecomment($comment);
	}
	catch(Exception $e)
	{
		$response['success'] = false;
		$response['error'] = $e->getMessage();
	}
}
else
{
	$response['success'] = false;
	$response['error'] = "Unknown method.";
}
$response['success'] = (bool)$response['success'];
respond($type,$response);
?>
