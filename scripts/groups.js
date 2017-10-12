var gGroups = new Array();

function ingroup(id)
{
	var ingroup = false;
	gGroups.each(function(item) {
		if(item.id == id)
		{
			ingroup = true;
			throw $break;
		}
	});
	return ingroup;
}

function preparelist()
{
	return $('currentgroups').update('<ul id="currentgrouplist"></ul>').down('ul');
}

function updategrouplist()
{
	var shaded = false;
	var list = preparelist();
	gGroups.sortBy(function(item) {
		return item.name.toLowerCase();
	}).each(function(item) {
		list.appendChild($(document.createElement('li')).update('<a href="/groupinfo.php?id='+item.id+'">'+item.name.escapeHTML()+'</a>').setStyle({backgroundColor: (shaded?'#ddd':'#fff')}));
		shaded = !shaded;
	});
}

function joingroup(id, name, div)
{
	if(ingroup(id))
	{
		return false;
	}	
	new Ajax.Request('/api/JSON/JoinGroup', {
		parameters: {
			userid: readcookie('uid'),
			key: readcookie('sid'),
			group: id
		},
		onSuccess: function(transport) {
			var response = transport.responseText.evalJSON(true);
			if(response.success)
			{
				//preparelist().appendChild($(document.createElement('li')).update('<a href="/groupinfo.php?id='+id+'">'+name.escapeHTML()+'</a>'));
				gGroups += {id: id, name: name};
				updategrouplist();
				div.down('span.thinking').remove();
			}
			else
			{
				div.down('span.thinking').removeClassName('thinking').addClassName('error').update(response.error.escapeHTML());
			}
		}
	});

}

Event.observe(window,'load',function() {
	if(readcookie('uid'))
	{
		new Ajax.Request('/api/JSON/MyGroups', {
			parameters: {
				userid: readcookie('uid'),
				key: readcookie('sid')
			},
			onSuccess: function(transport) {
				var response = transport.responseText.evalJSON(true);
				if(response.success)
				{
					response = response.response;
					gGroups = response.groups;
					if(response.groups.length > 0)
					{
						updategrouplist();
					}
					else
					{
						$('currentgroups').update('You are not currently in any groups');
					}
				}
				else
				{
					$('currentgroups').update(response.error.escapeHTML());
				}
			}
		});
	}
	new Ajax.Request('/api/JSON/ListGroups', {
		onSuccess: function(transport) {
			var response = transport.responseText.evalJSON(true);
			if(response.success)
			{
				$('grouplist').down('p').remove();
				response.response.groups.reverse().each(function(group) {
					var div = $(document.createElement('div'));
					div.addClassName('group').update('<span class="groupname clickable">'+group.name.escapeHTML()+'</span>');
					$('grouplist').appendChild(div);
					Event.observe(div.down('.groupname'),'click',function() {
						if(!div.hasClassName('expanded'))
						{
							var desc = $(document.createElement('div'));
							desc.addClassName('desc');
							div.appendChild(desc);
							div.addClassName('expanded');
							desc.update("Loading...");
							new Ajax.Request('/api/JSON/GroupInfo', {
								parameters: {
									id: group.id
								},
								onSuccess: function(transport) {
									var response = transport.responseText.evalJSON(true);
									if(response.success)
									{
										response = response.response;	
										var text = /* '<div class="sectiontitle">Description</div>'+ */
											'<div class="sectioncontent">'+
											response.description.escapeHTML().gsub('\n','<br />')+
											'</div>'+
											'<a href="/groupinfo.php?id='+group.id+'">More information &gt;&gt;&gt;</a><br />';
										desc.update(text);
										if(response.open && !ingroup(group.id) && readcookie('uid'))
										{
											var a = $(document.createElement('a'));
											//a.setAttribute('href','/joingroup.php?id='+group.id);
											a.update('Join this group');
											a.addClassName('joingroup').addClassName('clickable');
											div.appendChild(a);
											Event.observe(a,'click',function() {
												if(confirm("Are you sure you want to join "+group.name+"?"))
												{
													a.replace('<span class="thinking">Please wait...</span>');
													joingroup(group.id,group.name,div);
												}
											});
										}
									}
									else
									{
										desc.update("Description loading failed.");
									}
								}
							});
						}
						else
						{
							div.removeClassName('expanded');
							div.childElements().each(function(item) {
								if(!item.hasClassName('groupname'))
								{
									item.remove();
								}
							});
						}
					});
				});
			}
			else
			{
				$('grouplist').update('<p>Error loading group list: '+response.error.escapeHTML()+'</p>');
			}
		}
	});
});