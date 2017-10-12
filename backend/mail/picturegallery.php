#!/usr/bin/php
<?php
function unhtmlentities($string)
{
    // replace numeric entities
    $string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
    $string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
    // replace literal entities
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
    return strtr($string, $trans_tbl);
}
require_once(dirname(__FILE__).'/../../utils.php');
$parser = mailparse_msg_create();
$mail = str_replace("\r\n","\n",trim(file_get_contents('php://stdin')));
$headers = explode("\n",substr($mail,0, strpos($mail,"\n\n")));
$parsed = array();
foreach($headers as $header)
{
	$header = explode(': ',trim($header),2);
	$name = strtolower($header[0]);
	if(count($header) == 1)
	{
		continue;
	}
	if(isset($parsed[$name]))
	{
		if(!is_array($parsed[$name]))
		{
			$parsed[$name] = array($parsed[$name],$header[1]);
		}
		else
		{
			$parsed[$name][] = $header[1];
		}
	}
	else
	{
		$parsed[$name] = $header[1];
	}
}
$headers = $parsed;
unset($parsed);
foreach($headers['received'] as $rec)
{
	if($rec[0] == '(')
	{
		continue;
	}
	if(strpos($rec,' data.agni.lindenlab.com ') === false)
	{
		die("Invalid mail source.");
	}
}
mailparse_msg_parse($parser,$mail);
$parts = mailparse_msg_get_structure($parser);
$image = false;
foreach($parts as $part)
{
        $part = mailparse_msg_get_part($parser,$part);
        $data = mailparse_msg_get_part_data($part);
        mailparse_msg_free($part);
        if($data['content-name'] != 'secondlife-postcard.jpg')
        {
                continue;
        }
        $image = str_replace("\r",'',str_replace("\n",'',substr($mail,$data['starting-pos-body'],$data['ending-pos-body'] - $data['starting-pos-body'])));
        break;
}
mailparse_msg_free($part);
if($image === false)
{
	die("Couldn't parse postcard.");
}
preg_match('/<!-- BEGIN POSTCARD DETAILS(.+?)END POSTCARD DETAILS -->/s',$mail,$data);
$data = explode(',',trim($data[1]));
if(count($data) != 5)
{
	die("Invalid identification data!");
}
$name = $data[0];
$sim = $data[1];
$pos = new LLVector3($data[2],$data[3],$data[4]);
if(!preg_match('/<!-- BEGIN POSTCARD BODY -->(.+?)<!-- END POSTCARD BODY -->/s',$mail,$body))
{
	die("No postcard message!");
}
$body = html_entity_decode(html_entity_decode(trim($body[1]),ENT_QUOTES,'UTF-8'),ENT_QUOTES,'UTF-8');
$subject = $headers['subject'];
$link = new MySQL();
$imageid = substr($headers['message-id'],1,26);
$img = imagecreatefromjpeg("data://image/jpeg;base64,".trim($image));
$tagpos = strpos(strtolower($body),"\ntags:");
$tags = array();
if($tagpos !== false)
{
	$tagpos += 6;
	$len = strpos($body,"\n",$tagpos);
	if($len === false)
	{
		$len = strlen($body) - $tagpos;
	}
	else
	{
		$len -= $tagpos;
	}
	$tags = explode(',', substr($body,$tagpos,$len));
	foreach($tags as &$tag)
	{
		$tag = trim($tag);
	}
	$body = trim(substr($body, 0,$tagpos - 7).substr($body,$tagpos+$len));
}
print GalleryImage::Create($link,$img,$imageid,$name,$subject,$body,$tags);
imagedestroy($img);
?>