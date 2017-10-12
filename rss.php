<?php
require_once 'utils.php';
header("Content-Type: text/xml; charset=ISO-8859-1");
$link = new MySQL();
$rss = new UniversalFeedCreator();
$url = URL::parse($_SERVER['REDIRECT_URL']);
$posts = false;
$name = 'TSLP Planet';
if($url['section'] == 'feed')
{
	$posts = Microblog::GetAllPosts($link,50);
	$rss->link = SITE_ROOT;
	$rss->syndicationURL = SITE_ROOT.'/feed/';
}
else
{
	$name = str_replace('_',' ',$url[0]);
	$profile = new Profile($link, str_replace('_',' ',$url[0]));
	$name = Grammar::correct("{$profile->name}'s &#181;Blog");
	$blog = new Microblog($link, $profile->id);
	$posts = $blog->mergedentries(50);
	$rss->link = SITE_ROOT.$profile->url;
	$rss->syndicationURL = SITE_ROOT.'/feeds/'.URL::clean($profile->name);
}
$rss->title = $name;
$rss->description = "";
foreach($posts as $post)
{
	$item = new FeedItem();
	$item->author = $post['poster']['name'];
	$item->date = $post['time'];
	if(isset($post['image']))
	{
		$item->guid = "tslp-image-{$post['imageid']}";
		$item->title = $post['name'];
		$item->link = SITE_ROOT."/profiles/".URL::clean($post['poster']['name'])."/blog/{$post['imageid']}";
		$sim = htmlentities($post['sim'],ENT_QUOTES);
		$src = SITE_ROOT."/images/ublog/img{$post['imageid']}.png";
		$desc = htmlentities($post['content'],ENT_QUOTES);
		$item->descriptionHtmlSyndicated = true;
		$item->description = <<<EOF
<p>
	<img src="{$src}">
	<br />
	<a href="secondlife://{$sim}/{$post['x']}/{$post['y']}/{$post['z']}/">{$sim} ({$post['x']}, {$post['y']}, {$post['z']})</a>
</p>
<p>
	{$desc}
</p>
EOF;
	}
	else
	{
		$item->guid = "tslp-text-{$post['id']}";
		$item->title = "&#181;Blog entry by {$post['poster']['name']}";
		$item->link = SITE_ROOT."/profiles/".URL::clean($post['poster']['name'])."/blog/#post-{$post['id']}";
		$item->description = htmlentities($post['content'],ENT_QUOTES);
	}
	$rss->addItem($item);
}
print $rss->createFeed("RSS2.0");
?>
