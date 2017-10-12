#!/usr/bin/php
<?php
require_once dirname(__FILE__).'/../utils.php';
$link = new MySQL();
$link->query("
	SELECT UNIX_TIMESTAMP(`twitterlasttime`) AS `lasttime`, `twitteremail`,AES_DECRYPT(`twitterpassword`,'".Login::TWITTER_HASH."') AS `password`, `id`
	FROM `users`
	WHERE `twitterenabled` = 1".((isset($argv[1]) && is_numeric($argv[1]))?" AND `id` = '{$argv[1]}'":'')
);
print "Found ".$link->numrows()." accounts to update.\n";
$altlink = clone $link;
$curl = curl_init("http://twitter.com/statuses/user_timeline.json");
curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
curl_setopt($curl,CURLOPT_HEADER,false);
while($row = $link->fetchrow())
{
	curl_setopt($curl,CURLOPT_USERPWD,"{$row['twitteremail']}:{$row['password']}");//?since=".urlencode(date(DATE_RFC1123,$row['lasttime'])));
	print "Fetching data for {$row['twitteremail']}...\n";
	$result = curl_exec($curl);
	$altlink->query("
		UPDATE `users`
		SET `twitterlasttime` = NOW()
		WHERE `id` = '{$row['id']}'"
	);
	$data = curl_getinfo($curl);
	if($data['http_code'] == 200)
	{
		print "Got data. Processing...\n";
		$result = json_decode($result);
		foreach($result as $status)
		{
			if(strtotime($status->created_at) < $row['lasttime'])
			{
				continue;
			}
			
			$altlink->query("
				INSERT INTO `microblog` (`poster`,`content`,`from`,`when`)
				VALUES (
					'{$row['id']}',
					'".$altlink->escape(html_entity_decode(strip_tags($status->text)))."',
					'twitter (".$altlink->escape(strip_tags($status->source)).")',
					FROM_UNIXTIME('".strtotime($status->created_at)."')
				)"
			);
			print "  Added post #{$status->id}\n";
		}
	}
	else
	{
		print "Data grab for {$row['twitteremail']} failed - HTTP error {$data['http_code']}.\n";
	}
}
?>