<?php
/********************************************************************************************************
* Template Functions
* Simply include the file, and run the functions
*
* Thor Camus
* Ripped to small little bits by Dolus "This Layout Makes My Eyes Bleed" Naumova
* Who then put it back together again.
* Unlike all the king's horses and all the king's men.
* With Humpty-Dumpty.
* Because they couldn't put him back together again.
* But I did.
* With this code.
* Which is all better now.
* Then it was dismantled and yet reassembled again by Katharine Berry
* Because it needed prettifying.
*********************************************************************************************************/

class Template
{
	private $login = NULL;
	private $scripts = array();
	private $css = array();
	private $rss = array();
	private $prefix = '/';

	public function __construct(Login $login)
	{
		if(is_object($login))
		{
			$this->login = $login;
		}
		else
		{
			throw new Exception("A Login object must be provided when the Template is constructed!");
		}
		if($_SERVER['HTTP_HOST'] != 'tslprofiles.com' && $_SERVER['HTTP_HOST'] != 'tslprofiles.com.')
		{
			if($_SERVER['HTTP_HOST'] != 'new.tslprofiles.com') {
				$this->prefix = 'http://tslprofiles.com/';
			}
		}
	}
	
	public function AddScript($script)
	{
		if(strpos($script,'http://') === 0)
		{
			$this->scripts[] = $script;
		}
		else
		{
			$this->scripts[] = "{$this->prefix}scripts/".urlencode($script).'.js';
		}
	}

	public function AddCSS($css)
	{
		$this->css[] = "css/".urlencode($css).".css";
	}
	
	public function AddRSS($title, $feed)
	{
		$this->rss[$title] = $feed;
	}

	public function StartPage($title="",$head='',$body='')
	{
		ob_start();
		if($title == '')
		{
			$title = 'TSL Profiles';
		}
		else
		{
			$title = "TSL Profiles - {$title}";
		}
	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<html>
	<head>
		<title><?=$title?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link rel="stylesheet" type="text/css" href="<?=$this->prefix?>css/stylesheet.css">
		<!--[if lt IE 7]><style>
			.gainlayout { height: 0; }
		</style><![endif]-->
		<!--[if IE 7]><style>
			.gainlayout { zoom: 1;}
		</style><![endif]-->

		<script type="text/javascript" src="<?=$this->prefix?>scripts/utils.js"></script>
		<script type="text/javascript" src="<?=$this->prefix?>scripts/libs.js"></script>
		<script type="text/javascript">
		<!-- 
			var gIsModerator = <?=$this->login->mod?'true':'false'?>;
			var gIsAdmin = <?=$this->login->admin?'true':'false'?>;
		// -->
		</script>
		<?php
			foreach($this->css as $css)
			{
				print "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$this->prefix}{$css}\">\n";
			}
			foreach($this->scripts as $script)
			{
				//if(file_exists("{$_SERVER['DOCUMENT_ROOT']}{$script}.compressed.js") && filemtime("{$_SERVER['DOCUMENT_ROOT']}{$script}.compressed.js") > filemtime("{$_SERVER['DOCUMENT_ROOT']}{$script}.js"))
				//{
				//	$script .= '.compressed';
				//}
				print "<script type=\"text/javascript\" src=\"{$script}\"></script>\n";
			}
			foreach($this->rss as $title => $feed)
			{
				print "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"{$title}\" href=\"{$feed}\">\n";
			}
		?>
	</head>

	<body>
		<div id="container">
			<div id="header"><a href="<?=$this->prefix?>">TSL Profiles <sup style='font-size: xx-large;'><!-- <em>Almost</em> --> Beta</sup></a></div>
	<?php
	}
	
	public function StartSidebar()
	{
		print '		<div id="sidebar">';
	}

	public function DrawSidebar()
	{
		require 'sidebar.php';
	}

	public function EndSidebar()
	{
		echo '        </div><div id="main">';
	}

	public function Sidebar()
	{
		$this->StartSidebar();
		$this->DrawSidebar();
		$this->EndSidebar();
	}
	
	public function Start($title = '')
	{
		$this->StartPage($title);
		$this->Sidebar();
	}
	
	public function End()
	{
		$this->EndPage();
	}

	public function EndPage()
	{
	?>
			 </div>
		</div>
	   <div id="footer">
	   		Second Life&reg; and Linden Lab&reg; are trademarks or registered trademarks of Linden Research, Inc. All rights reserved. No infringement is intended.<br />
	   		By using this site you agree to abide by the <a href="<?=$this->prefix?>tacos/">TACOS</a>.
	   	</div>
		<script src="http://www.google-analytics.com/urchin.js" type="text/javascript"></script>
		<script type="text/javascript">
			_uacct = "UA-1709739-1";
			urchinTracker();
		</script>
	</body>

</html>
	<?php
	//$content = ob_get_clean();
	//print tidy_repair_string($content,array( 'wrap' => 0),'utf8'); 
	}

	public function StartWindow($Title, $id = '', $display = true)
	{
		echo '<div class="windowContainer"'.($display?'':' style="display:none;"').(($id=='')?'':' id="'.$id.'"').'><div class="windowTitle">' . $Title . '</div><p>';
	}

	public function EndWindow()
	{
		echo '</div>';
	}

	public function StartBlock($Title)
	{
		echo '<div class="windowContainer"><div class="windowTitle">' . $Title . '</div>';
	}

	function EndBlock()
	{
		echo '</div>';
	}
}
?>