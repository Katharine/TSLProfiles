<?php
class Twitter
{
	public static function TestDetails($user, $pass)
	{
		$curl = curl_init("http://twitter.com/account/verify_credentials.json");
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_USERPWD,"{$user}:{$pass}");
		$result = curl_exec($curl);
		//die($result);
		$ret = json_decode($result);
		curl_close($curl);
		return $ret->authorized;
	}

	private $login;
	private $curl;
	
	private function mktinyurl($url)
	{
		curl_setopt($this->curl,CURLOPT_URL,"http://tinyurl.com/api-create.php?url=".urlencode($url));
		curl_setopt($this->curl,CURLOPT_HTTPGET,true);
		$tinyurl = trim(curl_exec($this->curl));
		return (strlen($tinyurl)<strlen($url)&&$tinyurl!='')?$tinyurl:$url;
	}
	
	private function shorturls($text)
	{
		return preg_replace('/(http:\/\/[a-zA-Z0-0-.]+\/[^ ;:)]+)/e','$this->mktinyurl("$1")',$text);
	}
	
	public function __construct(Profile $login)
	{
		$this->login = $login;
		$this->curl = curl_init();
		curl_setopt($this->curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($this->curl,CURLOPT_HEADER,false);
	}
	
	public function __destruct()
	{
		curl_close($this->curl);
	}
	
	public function post($text)
	{
		$curl = curl_init("http://twitter.com/statuses/update.json");
		$login = $this->login->twitterlogin;
		curl_setopt($curl,CURLOPT_USERPWD,$login);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_POSTFIELDS,"source=tslprofiles&status=".urlencode(substr($text,0,140)));
		curl_setopt($curl,CURLOPT_POST,true);
		exec(dirname(__FILE__).'/../backend/twittergrab.php '.escapeshellarg($this->login->id));
		$result = curl_exec($curl);
		$data = curl_getinfo($curl);
		curl_close($curl);
		if($data['http_code'] >= 200 && $data['http_code'] < 300)
		{
			$result = json_decode($result);
			$link = new MySQL();
			$link->query("
				UPDATE `users`
				SET `twitterlasttime` = FROM_UNIXTIME('".(strtotime($result->created_at)+5)."')
				WHERE `id` = '{$this->login->id}'"
			);
			return true;
		}
		else
		{
			return false;
		}
	}
}
?>