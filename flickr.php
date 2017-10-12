<?php
require_once 'utils.php';
require_once 'Phlickr/Api.php';
$flickr = new Phlickr_Api(FLICKR_KEY,FLICKR_SECRET);
if(!isset($_GET['frob']))
{
	header("Location: ".$flickr->buildAuthUrl('write'));
}
else
{
	try
	{
		$token = $flickr->setAuthTokenFromFrob($_GET['frob']);
		if($flickr->isAuthValid($token))
		{
			$link = new MySQL();
			$login = new Login($link);
			$login->setflickrtoken($token);
			header("Location: /you/settings/#flickr");
		}
		else
		{
			print "Something went wrong.";
		}
	}
	catch(Exception $e)
	{
		print "Something went wrong: ".$e->getMessage();
	}
}
?>