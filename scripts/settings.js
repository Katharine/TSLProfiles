var gTwitterPassCleared = false;

function passcompare(e, second)
{
	if(second && $('newpass').getValue().length < 4)
	{
		$('passconf').update("Passwords must be at least four characters long").setStyle({color: 'red'});
		$('passbutton').disable();
	}
	else
	{
		if($('newpass').getValue() == $('newpasscheck').getValue())
		{
			if($('newpass').getValue() != '')
			{
				$('passconf').update("Passwords match").setStyle({color: 'green'});
				$('passbutton').enable();
			}
			else
			{
				$('passconf').update("");
				$('passbutton').disable();
			}
		}
		else
		{
			$('passconf').update("Passwords don't match").setStyle({color: 'red'});
			$('passbutton').disable();
		}
		e.focus();
	}
	return false;
}

function createcookie(name,value,days)
{
	if (days)
	{
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function passwordprocess()
{
	var elem = $('passprogress');
	elem.update("Setting password...");
	new Ajax.Request('/api/JSON/SetPassword',{
		parameters: {
			key: readcookie('sid'),
			userid: readcookie('uid'),
			password: $('newpasscheck').getValue()
		},
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				elem.update("Password set.");
				createcookie('sid',response.response.key,31);
				elem.update("Password set and cookies updated.");
			}
			else
			{
				elem.update("Password change failed: "+response.error.escapeHTML());
			}
		}
	});
}

function toggletrack()
{
	var checked = $('trackbox').checked?'1':'0';
	$('trackprogress').update("Saving change...").setStyle({color: 'orange'});
	new Ajax.Request('/api/JSON/SetTracking', {
		parameters: {
			key: readcookie('sid'),
			userid: readcookie('uid'),
			enabled: checked
		},
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				$('trackprogress').update("Saved.").setStyle({color: 'green'});
			}
			else
			{
				$('trackprogress').update("Failed to save settings: "+response.error.escapeHTML()).setStyle({color: 'red'});
			}
		}
	});
}

function toggletwitter()
{
	if($('twitteron').checked)
	{
		new Effect.SlideDown('twitterdetails');
	}
	else
	{
		new Effect.SlideUp('twitterdetails');
	}
}

function cleartwitterpass()
{
	if(!gTwitterPassCleared)
	{
		gTwitterPassCleared = true;
		$('twitterpass').value = '';
	}
}

function settwitter()
{
	$('settwitter').disable();
	var enabled = $('twitteron').checked;
	if(enabled)
	{
		$('twitterstatus').update("Checking details...").setStyle({color: 'orange'});
		var user = $('twittername').getValue();
		var pass = $('twitterpass').getValue();
		if(gTwitterPassCleared)
		{
			new Ajax.Request('/api/JSON/TwitterTestLogin', {
				parameters: {
					user: user,
					pass: pass
				},
				onSuccess: function(transport) {
					var response = transport.responseText.evalJSON(true);
					if(response.success)
					{
						if(response.response.working)
						{
							$('twitterstatus').update("Saving details...");
							new Ajax.Request('/api/JSON/TwitterStoreDetails', {
								parameters: {
									enable: 1, 
									key: readcookie('sid'), 
									userid: readcookie('uid'),
									user: user,
									pass: pass
								},
								onSuccess: function(transport) {
									var response = transport.responseText.evalJSON(true);
									if(response.success)
									{
										$('twitterstatus').update("Twitter was successfully enabled.").setStyle({color: 'green'});
									}
									else
									{
										$('twitterstatus').update("An error occured while saving your Twitter details.").setStyle({color: 'red'});
									}
									$('settwitter').enable();
								}
							});
						}
						else
						{
							$('twitterstatus').update("Invalid Twitter login info.").setStyle({color: 'red'});
							$('settwitter').enable();
						}
					}
					else
					{
						$('twitterstatus').update("Internal API call failed.").setStyle({color: 'red'});
						$('settwitter').enable();
					}
				}
			});
		}
		else
		{
			$('twitterstatus').update("Enabling Twitter...").setStyle({color: 'orange'});
			new Ajax.Request('/api/JSON/TwitterStoreDetails', {
				parameters: {
					enable: 1, 
					key: readcookie('sid'), 
					userid: readcookie('uid')
				},
				onSuccess: function(transport) {
					var response = transport.responseText.evalJSON(true);
					if(response.success)
					{
						$('twitterstatus').update("Twitter was successfully enabled.").setStyle({color: 'green'});
					}
					else
					{
						$('twitterstatus').update("An error occured while enabling Twitter.").setStyle({color: 'red'});
					}
					$('settwitter').enable();
				}
			});
		}
	}
	else
	{
		$('twitterstatus').update("Disabling Twitter...").setStyle({color: 'orange'});
		new Ajax.Request('/api/JSON/TwitterStoreDetails', {
			parameters: {
				key: readcookie('sid'),
				userid: readcookie('uid'),
				enable: 0
			},
			onSuccess: function(transport) {
				var response = transport.responseText.evalJSON(true);
				if(response.success)
				{
					$('twitterstatus').update("Twitter has been disabled.").setStyle({color: 'green'});
				}
				else
				{
					$('twitterstatus').update("Twitter could not be disabled.").setStyle({color: 'red'});
				}
				$('settwitter').enable();
			}
		});
	}
}

function deflickr()
{
	$('flickrlogout').disable();
	$('flickrstatus').update('De-authorising from Flickr...').setStyle({color: 'orange'});
	new Ajax.Request('/api/JSON/DeFlickr', {
		parameters: {
			key: readcookie('sid'),
			userid: readcookie('uid'),
		},
		onSuccess: function(transport) {
			var response = transport.responseText.evalJSON(true);
			if(response.success)
			{
				$('flickron').hide();
				$('flickroff').show();
				$('flickrlogout').enable();
				$('flickrstatus').update("").setStyle({color: 'green'});
			}
			else
			{
				$('flickrstatus').update("Flickr could not be disabled: "+response.error.escapeHTML()).setStyle({color: 'red'});
			}
			$('settwitter').enable();
		}
	});
}

function flickrtags()
{
	var tags = $('flickrtags').disable().value;
	$('flickrtagupdate').disable();
	$('flickrstatus').update('Saving tags...').setStyle({color: 'orange'});
	new Ajax.Request('/api/JSON/SetFlickrTags', {
		parameters: {
			key: readcookie('sid'),
			userid: readcookie('uid'),
			tags: tags
		},
		onSuccess: function(transport) {
			var response = transport.responseText.evalJSON(true);
			if(response.success)
			{
				$('flickrtags').enable();
				$('flickrtagupdate').enable();
				$('flickrstatus').update('Tags saved.').setStyle({color: 'green'});
			}
			else
			{
				$('flickrstatus').update('Flickr tags could not be saved: '+response.error.escapeHTML()).setStyle({color: 'red'});
			}
		}
	});
}