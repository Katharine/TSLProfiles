#!/usr/bin/php
<?php
if ( !function_exists('sys_get_temp_dir')) {
	function sys_get_temp_dir() {
		if (!empty($_ENV['TMP'])) return realpath($_ENV['TMP']);
		if (!empty($_ENV['TMPDIR'])) return realpath( $_ENV['TMPDIR']);
		if (!empty($_ENV['TEMP'])) return realpath( $_ENV['TEMP']);
		$tempfile=tempnam(uniqid(rand(),true),'');
		if (file_exists($tempfile)) {
			unlink($tempfile);
			return realpath(dirname($tempfile));
		}
	}
}
define('X_MAX',800);
define('Y_MAX',480);
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
file_put_contents(dirname(__FILE__).'/recorded_mail.eml',$mail);
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
	if(strpos($rec,'.lindenlab.com ' ) === false && strpos($rec,'.lindenlab.com)' ) === false)
	{
		die("Invalid mail source.");
	}
}
//if(
//	!preg_match('/from data.agni.lindenlab.com \([0-9.]+\)/',$headers['received'][2]) ||
//	$headers['received'][3] != 'by data.agni.lindenlab.com (Postfix, from userid 1)')
//{
//	die("Invalid mail source.");
//}
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
$data = explode("\n",trim($data[1]));
$details = array();
foreach($data as $d)
{
	$d = explode('=',$d,2);
	$d[1] = trim($d[1]);
	if($d[1][0] == '"' && $d[1][strlen($d[1])-1] == '"')
	{
		$d[1] = trim($d[1],'"');
	}
	$details[$d[0]] = $d[1];
}
$data = $details;
unset($details);
$name = $data['username'];
$sim = $data['sim_name'];
$pos = new LLVector3($data['local_x'],$data['local_y'],$data['local_z']);
if(!preg_match('/<!-- BEGIN POSTCARD BODY -->(.+?)<!-- END POSTCARD BODY -->/s',$mail,$body))
{
	die("No postcard message!");
}
$body = html_entity_decode(html_entity_decode(trim($body[1]),ENT_QUOTES,'UTF-8'),ENT_QUOTES,'UTF-8');
$subject = $headers['subject'];
$link = new MySQL();
$blog = new Microblog($link, $name);
$tempname = substr($headers['message-id'],1,-1);
$tempfile = tempnam(sys_get_temp_dir(),'flickr');
file_put_contents($tempfile,base64_decode(trim($image)));
$img = imagecreatefromjpeg($tempfile);
$x = imagesx($img);
$y = imagesy($img);
if($x > X_MAX)
{
	$img2 = imagecreatetruecolor(X_MAX,$y*(X_MAX/$x));
	imagecopyresampled($img2,$img,0,0,0,0,X_MAX,$y*(X_MAX/$x),$x,$y);
	imagedestroy($img);
	$img = $img2;
	unset($img2);
	$x = X_MAX;
	$y = imagesy($img);
}
if($y > Y_MAX)
{
	$img2 = imagecreatetruecolor($x*(Y_MAX/$y),Y_MAX);
	imagecopyresampled($img2,$img,0,0,0,0,$x*(Y_MAX/$y),Y_MAX,$x,$y);
	imagedestroy($img);
	$img = $img2;
	unset($img2);
	$x = imagesy($img);
	$y = Y_MAX;
}
$watermark = imagecolorallocatealpha($img,255,255,255,80);
imagettftext($img,20,0,18,$y-20,$watermark,'/usr/share/fonts/truetype/arial.ttf',"tsl profiles - ".strtolower($name));
$id = $blog->postimage(dirname(__FILE__)."/../../images/ublog/",$img,$subject,$body,$sim,$pos);
chmod(dirname(__FILE__)."/../../images/ublog/img{$id}.png",0444);
chmod(dirname(__FILE__)."/../../images/ublog/img{$id}.png.thumb",0444);
imagedestroy($img);
$profile = new Profile($link,$name);
$login = new Login($link,$profile->id);
if($login->flickrtoken != '')
{
	require_once(dirname(__FILE__).'/../../utils/pear/Phlickr/Uploader.php');
	$flickr = new Phlickr_Api(FLICKR_KEY,FLICKR_SECRET);
	$flickr->setAuthToken($login->flickrtoken);
	if($flickr->isAuthValid())
	{
		$uploadr = new Phlickr_Uploader($flickr);
		$tags = $login->flickrtags;
		if($login->flickrpostags)
		{
			$tags .= " \"secondlife:region={$sim}\" secondlife:x={$pos->x} secondlife:y={$pos->y} secondlife:z={$pos->z}";
		}
		$uploadr->upload($tempfile,$subject,$body,$tags);
	}
	else
	{
		$login->clearflickrtoken();
	}
}
@unlink($tempfile);
?>