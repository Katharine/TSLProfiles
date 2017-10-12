Event.observe(window,'load',function() {
	loadlist('Administrator');
	loadlist('Moderator');
});

function afterupdate(list)
{
	if(!$(list+'list'))
	{
		return;
	}
	var even = true;
	$(list+'list').getElementsBySelector('li').sortBy(function(li) {
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
		$(list+'list').appendChild(li);
		li.setStyle({backgroundColor: (even?'#fff':'#ddd')});
		even = !even;
	});
}

function addtolist(list, key, name, fade)
{
	if(!$(list+'list'))
	{
		Effect.Fade($(list).down('span'), {
			afterFinish: function(obj) {
				$(list).update('<ul id="'+list+'list"></ul><br />');
				addtolist(list, key, name, fade);
			}
		});
		return;
	}
	if(!$(list+'_'+key))
	{
		var li = document.createElement('li');
		li.setAttribute('id',list+'_'+key);
		if(fade)
		{
			li.style.display = 'none';
		}
		$(li).setStyle({backgroundColor: '#fff'});
		var lis = $(list+'list').getElementsBySelector('li');
		if(lis.length > 0)
		{
			var last = lis[lis.length - 1];
		}
		$(li).update('<span><a href="/profiles/'+urlclean(name)+'/">'+name+'</a></span>'+((key!=readcookie('uid'))?' [<a class="clickable">Remove</a>]':''));
		$(list+'list').appendChild(li);
		if(fade)
		{
			Effect.Appear(li);
		}
		if(li.down('span').next('a'))
		{
			Event.observe(li.down('span').next('a'),'click', function(event) {
				var elem = $(Event.element(event));
				var item = li;
				var initcolour = li.getStyle('background-color');
				elem.replace('<span style="color:#aaa">Deleting...</span>');
				new Ajax.Request('/api/JSON/Delete'+list, {
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
									if(!$(list+'list').down('li').next('li'))
									{
										$(list).update('<span style="display: none;">You deleted all the '+list.toLowerCase()+'s.</span>');
										Effect.Appear($(list).down('span'));
									}
									else
									{
										item.remove();
										afterupdate(list);
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
		}
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
	}
	afterupdate(list);
}

function loadlist(list)
{
	new Ajax.Request('/api/JSON/Get'+list+'List', {
		parameters: {
			userid: readcookie('uid'),
			key: readcookie('sid')
		},
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				response = response.response;
				$(list+'div').update('<div id="'+list+'"></div><div></div>');
				if(response.entries.length > 0)
				{
					$(list).update('<ul id="'+list+'list"></ul>');
					response.entries.sortBy(function(s) {
						return s.name.toLowerCase();
					}).each(function(item) {
						addtolist(list,item.id,item.name);
					});
				}
				else
				{
					$(list).update('<span>There are no '+list.toLowerCase()+'s.</span>');
				}
				$(list).next('div').update(
					'<p>Add '+list.toLowerCase()+':<br />'+
					'<input type="text" id="'+list+'input" size="30" />'+
					'<input type="button" value="Add" id="add'+list+'" />'+
					'</p>'+
					'<div class="auto_complete" id="'+list+'options"></div>'
				);
				new Ajax.Autocompleter(list+'input',list+'options','/scripts/autocomplete.php',{minChars: 3, paramName: 'q', frequency: 0.05});
				Event.observe($('add'+list),'click',function() {
					$(list+'input').disable().next('input').disable().value = 'Adding...';
					var name = $(list+'input').getValue();
					new Ajax.Request('/api/JSON/GetUserID', {
						parameters: {
							first: name.split(' ')[0],
							last: name.split(' ')[1]
						},
						onSuccess: function(transport) {
							var response = eval('('+transport.responseText+')');
							if(response.success)
							{
								var key = response.response.id;
								new Ajax.Request('/api/JSON/Add'+list, {
									parameters: {
										userid: readcookie('uid'),
										key: readcookie('sid'),
										id: key
									},
									onSuccess: function(transport) {
										var response = eval('('+transport.responseText+')');
										if(response.success)
										{
											addtolist(list, key, name, true);
											$(list+'input').clear().enable().next('input').enable().value = 'Add';
										}
										else
										{
											$('add'+list).enable().value = 'Add';
											$(list+'input').enable().value = response.error.escapeHTML();
											$(list+'input').activate();
										}
									}
								});
							}
							else
							{
								$('add'+list).enable().value = 'Add';
								$(list+'input').enable().value = response.error.escapeHTML();
								$(list+'input').activate();
							}
						}
					});
				});
			}
			else
			{
				$(list+'div').update("Error loading "+list+" list: "+response.error.escapeHTML()).setStyle({color: '#f00', fontWeight: 'bold'});
			}
		}
	});
}