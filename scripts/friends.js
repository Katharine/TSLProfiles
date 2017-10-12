Event.observe(window,'load',loadlist);

function afterupdate()
{
	if(!$('friendlist'))
	{
		return;
	}
	var even = true;
	$('friendlist').getElementsBySelector('li').partition(function(n) {
		return n.hasClassName('statusonline');		
	}).each(function (a) {
		a.sortBy(function(li) {
			var text = li.down('span');
			if(text.down('a'))
			{
				return text.down('a').innerHTML.toLowerCase();
			}
			else
			{
				return text.innerHTML.toLowerCase();
			}
		}).each(function(li) {
				$('friendlist').appendChild(li);
				li.setStyle({backgroundColor: (even?'#fff':'#ddd')});
				even = !even;
		});
	});
}

function addtolist(key, name, fade)
{
	if(!$('friendlist'))
	{
		Effect.Fade($('friends').down('span'), {
			afterFinish: function(obj) {
				$('friends').update('<ul id="friendlist"></ul><br />');
				addtolist(key, name, fade);
			}
		});
		return;
	}
	if(!$('friend_'+key))
	{
		var li = document.createElement('li');
		li.setAttribute('id','friend_'+key);
		if(fade)
		{
			li.style.display = 'none';
		}
		$(li).setStyle({backgroundColor: '#fff'});
		var lis = $('friendlist').getElementsBySelector('li');
		if(lis.length > 0)
		{
			var last = lis[lis.length - 1];
		}
		$('friendlist').appendChild(li);
		$(li).update('<span><a href="/profiles/'+urlclean(name)+'/">'+name+'</a></span> <span>(waiting)</span> [<a class="clickable">Delete</a>]');
		if(fade)
		{
			Effect.Appear(li);
		}
		Event.observe(li.down('span').next('a'),'click', function(event) {
			var elem = $(Event.element(event));
			var item = li;
			var initcolour = li.getStyle('background-color');
			elem.replace('<span style="color:#aaa">Deleting...</span>');
			new Ajax.Request('/api/JSON/DeleteFriend', {
				parameters: {
					userid: readcookie('uid'),
					key: readcookie('sid'),
					id: key
				},
				onSuccess: function(transport) {
					var response = eval('('+transport.responseText+')');
					if(response.success)
					{
						Effect.Fade(item, {
							afterFinish: function(obj) {
								if(!$('friendlist').down('li').next('li'))
								{
									$('friends').update('<span style="display: none;">You deleted all your friends! D:</span>');
									Effect.Appear($('friends').down('span'));
								}
								else
								{
									item.remove();
									afterupdate();
								}
							}
						});
						//item.remove();
					}
					else
					{
						item.down().next('span',1).update("Failed").setStyle({color: '#f00'});
					}
				}
			});
		});
		/*
		new Ajax.Request('/api/JSON/GetUserID', {
			parameters: {
				first: name.split(' ')[0],
				last: name.split(' ')[1]
			},
			onSuccess: function(transport) {
				var response = eval('('+transport.responseText+')');
				if(response.success)
				{
					$('friend_'+key).down('span').update('<a href="/profile.php?id='+response.response.id+'">'+name+'</a>');
				}
				else
				{
					$('friend_'+key).down('span').update('<a href="/profile.php?id='+key+'">'+name+'</a>');
				}
			}
		});
		*/
		new Ajax.Request('/api/JSON/UserOnline', {
			parameters: {
				id: key
			},
			onSuccess: function(transport) {
				var response = eval('('+transport.responseText+')');
				if(response.success)
				{
					if(response.response.online)
					{
						$('friend_'+key).setStyle({fontWeight: 'bold'}).addClassName('statusonline').down().next('span').update('(Online)');
						afterupdate();
					}
					else
					{
						$('friend_'+key).down().next('span').update('');
					}
				}
			}
		});
	}
	afterupdate();
}

function loadlist()
{
	new Ajax.Request('/api/JSON/GetFriendList', {
		parameters: {
			userid: readcookie('uid'),
			key: readcookie('sid')
		},
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				response = response.response;
				$('content').update('<div id="friends"></div><div></div>');
				if(response.friends.length > 0)
				{
					$('friends').update('<ul id="friendlist"></ul>');
					response.friends.sortBy(function(s) {
						return s.name;
					}).each(function(item) {
						addtolist(item.key,item.name);
					});
				}
				else
				{
					$('friends').update('<span>You have no friends! D:</span>');
				}
				$('friends').next('div').update(
					'<p>Add a friend:<br />'+
					'<input type="text" id="friendinput" size="30" />'+
					'<input type="button" value="Add" id="addfriend" />'+
					'</p>'+
					'<div class="auto_complete" id="friendoptions"></div>'
				);
				new Ajax.Autocompleter('friendinput','friendoptions','/scripts/autocomplete.php',{minChars: 3, paramName: 'q', frequency: 0.05});
				Event.observe($('addfriend'),'click',function() {
					$('friendinput').disable().next('input').disable().value = 'Adding...';
					var name = $('friendinput').getValue();
					new Ajax.Request('/api/JSON/NameToKey', {
						parameters: {
							name: name
						},
						onSuccess: function(transport) {
							var response = eval('('+transport.responseText+')');
							if(response.success)
							{
								var key = response.response.key;
								new Ajax.Request('/api/JSON/AddFriend', {
									parameters: {
										userid: readcookie('uid'),
										key: readcookie('sid'),
										id: key
									},
									onSuccess: function(transport) {
										var response = eval('('+transport.responseText+')');
										if(response.success)
										{
											addtolist(key, name, true);
											$('friendinput').clear().enable().next('input').enable().value = 'Add';
										}
										else
										{
											$('addfriend').enable().value = 'Add';
											$('friendinput').enable().value = response.error.escapeHTML();
											$('friendinput').activate();
										}
									}
								});
							}
							else
							{
								$('addfriend').enable().value = 'Add';
								$('friendinput').enable().value = response.error.escapeHTML();
								$('friendinput').activate();
							}
						}
					});
				});
			}
			else
			{
				$('content').update("Error loading friend list: "+response.error.escapeHTML()).setStyle({color: '#f00', fontWeight: 'bold'});
			}
		}
	});
}