<?php
require_once 'utils.php';
$link = new MySQL();
$login = new Login($link);
$id = 0;
$name = '';
$init = 'basic';
if(isset($_GET['id']))
{
	$id = $_GET['id'];
}
else
{
	$url = URL::parse($_SERVER['REDIRECT_URL']);
	if($url['section'] == 'profiles' && !empty($url[0]))
	{
		$name = str_replace('_',' ',$url[0]);
		if(isset($url[1]))
		{
			switch(strtolower($url[1]))
			{
			case 'blog':
			case 'microblog':
			case 'ublog':
			case 'µblog':
				$init = 'microblog';
				if(isset($url[2]) && is_numeric($url[2]))
				{
					$init = 'microblog-'.$url[2];
				}
				break;
			case 'ratings':
				$init = 'ratings';
				break;
			case 'profile':
			case 'basic':
				$init = 'basic';
				break;
			case 'comment':
			case 'comments':
				$init = 'comments';
				break;
			case 'notes':
			case 'private':
			case 'privatenotes':
				$init = 'notes';
				break;
			}
		}
	}
}
$full = true;
if(is_numeric($id))
{
	$id = (int)$id;
}
else
{
	$full = false;
}
$loggedin = (int)($login->loggedin && $id==$login->id);
try
{
	$profile = new Profile($link,($id!='')?$id:$name);
	$id = $profile->id;
	if($profile->tslpjoin == NULL)
	{
		$full = false;
		$id = $profile->key;
	}
	$template = new Template($login);
	$template->AddScript('profile');
	$template->AddScript('lightbox');
	$template->AddCSS('lightbox');
	$template->AddRSS(Grammar::correct("{$profile->name}'s &micro;Blog"),'/feeds/'.URL::clean($profile->name));
	$template->StartPage(Grammar::correct("{$profile->name}'s profile"));
	$template->Sidebar();
	$template->StartWindow($profile->name);
	$ie = (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'msie')===false)?false:true;
	?>
	<script type="text/javascript">
	<!--
	var gProfile = '<?=$id?>';
	var gUser = '<?=$login->id?>';
	var gUserName = '<?=addslashes($login->name)?>';
	var gProfileName = '<?=addslashes($profile->name)?>';
	var gLoggedIn = <?=$login->loggedin?'true':'false'?>;
	var gTSLPUser = <?=$full?'true':'false'?>;
	var gFirstTab = '<?=$init?>';
	// -->
	</script>
	<div class="tabset" id="tabset">
		<span class="tab">
			<a <?=$ie?'href="#" ':''?>onclick="loadbasic()">Basic</a>
		</span>
		<span class="tab">
			<a <?=$ie?'href="#" ':''?>onclick="loadratings()">Ratings</a>
		</span>
		<span class="tab">
			<a <?=$ie?'href="#" ':''?>onclick="loadcomments()">Comments</a>
		</span>
		<span class="tab">
			<a <?=$ie?'href="#" ':''?>onclick="loadnotes()">Private Notes</a>
		</span>
		<?php
	if($full)
	{
		?>
		<span class="tab">
			<a <?=$ie?'href="#" ':''?>onclick="loadblog('User')">&micro;Blog</a>
		</span>
		<span class="tab">
			<a <?=$ie?'href="#" ':''?>onclick="loadchat()">Chat</a>
		</span>
		<?php
	}
		?>
	</div>
	<div id="profilecontent" style="min-height: 350px;">Loading data...</div>
	<?php
	$template->EndWindow();
	$template->EndPage();
}
catch(Exception $e)
{
	header("Content-Type: text/plain");
	print "Error rendering page: ".$e->getMessage();
}
?>