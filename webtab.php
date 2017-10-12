<?php
require_once 'utils.php';
$url = URL::parse($_SERVER['REDIRECT_URL']);
$name = '';
if($url['section'] == 'webtab' && !empty($url[0]))
{
	$name = str_replace('_',' ',$url[0]);
}
if(strpos($_SERVER['HTTP_USER_AGENT'],'Second Life') === false)
{
	header("Location: /profiles/{$url[0]}/");
	die();
}
try
{
	$link = new MySQL();
	$profile = new Profile($link,$name);
	$ratings = $profile->getratings();
	ksort($ratings);
	header("Content-Type: text/xml");
?>
<?='<?'?>xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>Web Tab</title>
		<link rel="stylesheet" type="text/css" href="/css/webtab.css" />
		<script src="/scripts/webtab.js" type="text/javascript" />
	</head>
	<body>
		<div id="ratings">
			<p class="label" title="Ratings:"><span>Ratings:</span></p>
			<table id="ratingtable">
				<?php
				$shaded = false;
				foreach($ratings as $cat => $rating)
				{
					?>
				<tr class="<?=$shaded?'shaded':'unshaded'?>">
					<td><?=htmlentities($cat)?></td>
					<td class="numeric">+<?=number_format($rating['positive'])?></td>
					<td class="numeric"><?=($rating['negative']?'':'-').number_format($rating['negative'])?></td>
				</tr>				
					<?php
					$shaded = !$shaded;
				}
				?>
			</table>
		</div>
		<div id="mblog">
			<p class="label" title="&micro;Blog entry:"><span>&micro;Blog entry:</span></p>
			<div class="value"><p><?php
			$blog = new Microblog($link, $profile->id);
			$entries = $blog->entries(1);
			if(count($entries) > 0)
			{
				$entry = $blog->read($entries[0]);
				print htmlentities($entry['content']);
			}
			else
			{
				print "&nbsp;";
			}
			?></p></div>
		</div>
		<div id="fields">
			<table>
			<?php
			$fields = $profile->getfields();
			foreach($fields as $field => $value)
			{
				?>
				<tr>
					<td class="label fields" title="<?=htmlentities($field).':'?>"><span><?=htmlentities($field).':'?></span></td>
					<td class="value fields"><?=htmlentities($value)?></td>
				</tr>	
				<?php
			}
			?>
			</table>
		</div>
	</body>
</html>
<?php
}
catch(Exception $e)
{
	@header("Content-Type: text/plain");
	die("Error rendering page: ".$e->getMessage());
}
?>