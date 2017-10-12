<?php
class Server
{
	const DATA_ONLINE				= 1;
	const DATA_NAME					= 2;
	const DATA_BORN					= 3;
	const DATA_RATING				= 4; // Deprecated
	const DATA_PAYINFO				= 8;

	const SERVER_REQUESTAGENTDATA	= 1;

	private $link;
	private $servers = array();

	private function pickserver()
	{
		shuffle($this->servers);
		return $this->servers[array_rand($this->servers)];
	}

	private function sendrequest($int, $string)
	{
		$int = (int)$int;
		$this->link->query("
			SELECT `response`
			FROM `responsecache`
			WHERE `queryint` = '{$int}' AND `querystring` = '".$this->link->escape($string)."' AND `time` >= DATE_SUB(NOW(),INTERVAL 5 MINUTE)"
		);
		if($this->link->numrows() > 0)
		{
			return unserialize($this->link->result(0,0));
		}
		$channel = htmlspecialchars($this->pickserver());
		$string = htmlspecialchars($string);
		$data = '<?xml version="1.0"?>';
		$data .= '<methodCall>';
		$data .= '<methodName>llRemoteData</methodName>';
		$data .= '<params><param><value><struct>';
		$data .= '<member><name>Channel</name><value><string>'.$channel.'</string></value></member>';
		$data .= '<member><name>IntValue</name><value><int>'.$int.'</int></value></member>';
		$data .= '<member><name>StringValue</name><value><string>'.$string.'</string></value></member>';
		$data .= '</struct></value></param></params></methodCall>';
		$fp = @fsockopen('xmlrpc.secondlife.com', 80);
		if(is_resource($fp))
		{
			fputs($fp, "POST /cgi-bin/xmlrpc.cgi HTTP/1.1\r\n");
			fputs($fp, "Host: xmlrpc.secondlife.com\r\n");
			fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
			fputs($fp, "Content-length: ". strlen($data) ."\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, $data);
			while(!feof($fp))
			{
				$res .= fgets($fp, 128);
			}
			fclose($fp);
			$response = substr($res, strpos($res, "\r\n\r\n"));
			if($response === false)
			{
				return false;
			}
			$result = array();
			if (preg_match_all('#<name>(.+)</name><value><(string|int)>(.*)</\2></value>#U', $response, $regs, PREG_SET_ORDER))
			{
				foreach($regs as $key=>$val)
				{
					$result[$val[1]] = $val[3];
				}
			}
			$this->link->query("
				REPLACE INTO `responsecache` (`queryint`,`querystring`,`response`,`time`)
				VALUES (
					'{$int}',
					'".$this->link->escape($string)."',
					'".$this->link->escape(serialize($result))."',
					NOW()
				)"
			);
			return $result;
		}
		else
		{
			return false;
		}
	}

	public function __construct(MySQL $link)
	{
		$this->link = $link;
		$results = $this->link->query("
			SELECT `channel`
			FROM `servers`",
			true
		);
		while($row = mysql_fetch_assoc($results))
		{
			$this->servers[] = $row['channel'];
		}
	}

	public function requestdata($type, $target)
	{
		$return = $this->sendrequest(self::SERVER_REQUESTAGENTDATA,"{$type} {$target}");
		if(isset($return['faultCode']))
		{
			return false;
		}
		else
		{
			return $return['StringValue'];
		}
	}
}
?>