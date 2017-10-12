<?php
class RPC
{
	private $string = '';
	private $int = 0;
	private $channel = '00000000-0000-0000-0000-000000000000';
	private $server = 'xmlrpc.secondlife.com';
	private $path = '/cgi-bin/xmlrpc.cgi';
	
	private function postToHost($host, $path, $data_to_send)
	{
		$fp = @fsockopen($host, 80);
		if(is_resource($fp))
		{
			fputs($fp, "POST $path HTTP/1.1\r\n");
			fputs($fp, "Host: $host\r\n");
			fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
			fputs($fp, "Content-length: ". strlen($data_to_send) ."\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, $data_to_send);
			while(!feof($fp))
			{
				$res .= fgets($fp, 128);
			}
			fclose($fp);
			return substr($res, strpos($res, "\r\n\r\n"));;
		}
		else
		{
			return false;
		}
	}
  
	private function parseResponse($response)
	{
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
		return $result;
	}
  
	private function sendRequest($channel, $intValue, $stringValue)
	{
		$channel = htmlspecialchars($channel);
		$int = (int)$intValue;
		$string = htmlspecialchars($stringValue);
		
		$data = '<?xml version="1.0"?>';
		$data .= '<methodCall>';
		$data .= '<methodName>llRemoteData</methodName>';
		$data .= '<params><param><value><struct>';
		$data .= '<member><name>Channel</name><value><string>'.$channel.'</string></value></member>';
		$data .= '<member><name>IntValue</name><value><int>'.$int.'</int></value></member>';
		$data .= '<member><name>StringValue</name><value><string>'.$string.'</string></value></member>';
		$data .= '</struct></value></param></params></methodCall>';
		
		return $this->parseResponse($this->postToHost($this->server,$this->path, $data));
	}
	
	public function __construct($channel = null)
	{
		if($channel != null)
		{
			$this->channel = $channel;
		}
	}
	
	public function setchannel($channel)
	{
		$this->channel = $channel;
	}
	
	public function setint($channel)
	{
		$this->int = $channel;
	}
	
	public function setstring($string)
	{
		$this->string = $string;
	}
	
	public function send()
	{
		return $this->sendRequest($this->channel,$this->int,$this->string);
	}
}	
?>