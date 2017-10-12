<?php
require_once 'utils.php';
$link = new MySQL();
$login = new Login($link);
$template = new Template($login);
$url = URL::parse($_SERVER['REDIRECT_URL']);
$name = str_replace('_',' ',$url[0]);
$profile = new Profile($link,$name);
$tag = '';
if(isset($url[1]))
{
	$subsection = strtolower($url[1]);
	if($subsection == 'tags' || $subsection == 'tag')
	{
		if(!isset($url[2]))
		{
			// List tags
		}
		else
		{
			$tag = $url[3];
		}
	}
}
$template->AddScript("gallery");
$template->Start(Grammar::correct($profile->getname()."'s photo gallery"));
$template->StartWindow("Photos",'gallerywin');
$gallery = new Gallery($link,$profile);
$images = $gallery->getimages(0,16);
foreach($images as $image)
{
	?>
	<img src="<?=$image->thumb?>" alt="<?=htmlentities($image->caption)?>" title="<?=htmlentities($image->caption)?>" id="thumb-<?=$image->imageid?>" class="imgthumb" />
	
	<?php
}
$template->EndWindow();
$template->StartWindow("Photo Details",'detailwin',false);
?>
<div id="imgholder"></div>
<div id="rating">

</div>
<div id="comments"></div>
<?php
$template->EndWindow();
$template->End();
?>