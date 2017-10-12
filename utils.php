<?php
// Require this in every page
if(get_magic_quotes_gpc())
{
	foreach($_GET as &$g)
	{
		if(!is_array($g))
			$g = stripslashes($g);
	}
	foreach($_POST as &$p)
	{
		if(!is_array($p))
			$p = stripslashes($p);
	}
	foreach($_COOKIE as &$c)
	{
		if(!is_array($c))
			$c = stripslashes($c);
	}
}
define('SITE_ROOT','http://tslprofiles.com');
define('API_ROOT','http://api.tslprofiles.com');
define('FLICKR_KEY', '[redacted]');
define('FLICKR_SECRET', '[redacted]');
define('BLOG_SITE','tslblogs.com');
$GLOBALS['loaded'] = array();
function __autoload($class)
{
	//print "/* Loading {$class}... */";
	require_once dirname(__FILE__)."/utils/{$class}.php";
	$GLOBALS['loaded'][] = $class;
}
require_once dirname(__FILE__)."/utils/llsd.php";
?>