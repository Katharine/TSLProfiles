// I <3 Prototype
Position.includeScrollOffsets = true;
var gTab = '';
var gProfile = 0;
var gLightbox = null;

//- mark Basic

function loadbasic()
{
	var profileid = gProfile;
	var loggedin = gLoggedIn && gProfile == gUser;
	if(gTab == 'chat-on')
	{
		return;
	}
	gTab = 'basic';
	$('profilecontent').update("Loading profile...");
	new Ajax.Request("/api/JSON/GetProfileInfo", {
		parameters: {id: profileid},
		onSuccess: function(transport) {
			var content = $('profilecontent');
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				var data = response.response;
				var toupdate = '<div class="profileright">'+
						'<img style="margin-bottom: 10px;" src="'+data.image+'" width="200" height="150" /><br />'+
						'<span id="profileonline">(waiting)</span><br /><span id="profilelocation"></span'+
						//'<span id="profilegrid">'+(gTSLPUser?(((data.grid=='mg')?'Main Grid':'Teen Grid')):'Unknown')+'</span>'+
					'</div>'+
					'<h1 style="margin:0px;padding:0px;">'+data.name+'</h1>'+
					'<table>'+
						(gTSLPUser?'<tr><td>Joined SL:</td><td>The '+formattime(data.sljoin)+'</td></tr>'+
						'<tr><td>Joined TSL Profiles:</td><td>The '+formattime(data.tslpjoin)+'</td></tr>':'<tr><td>'+gProfileName.escapeHTML()+' does not use TSL Profiles, so no profile data is available.</td></tr>')+
					'</table>';
					if(gTSLPUser)
					{
						toupdate += '<div class="fields" id="fieldlist">'+generatefields(loggedin,data.fields)+'</div>'+
						(loggedin?'<br /><div id="addfield">[<a onclick="addfield()">Add field</a>]</div>'+
						'<p>To reorder the fields, just drag them around</p>':'');
					}
				content.update(toupdate);
				setTimeout('loadonlinestatus("'+profileid+'","profileonline")',500);
				if(gTSLPUser && (loggedin || gIsModerator))
				{
					makesortable('fieldlist');
				}
			}
			else
			{
				content.update("Error fetching data: "+response.error);
			}
		}
	});
}


function loadonlinestatus(profileid,place)
{
	new Ajax.Request("/api/JSON/UserOnline", {
		parameters: {id: profileid},
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				if(response.response.online == "1")
				{
					$(place).update("Online").setStyle({color: 'green'});
					loadposition(profileid,$(place).next('span'));
				}
				else
				{
					$(place).update("Offline").setStyle({color: 'red'});
				}
			}
			else
			{
				$(place).update("(error)").setStyle({color: 'red'});
			}
		}
	});
}

function loadposition(profileid,place)
{
	new Ajax.Request('/api/JSON/LocateUser', {
		parameters: {id: profileid},
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				var sim = response.response.sim;
				var pos = response.response.pos;
				$(place).update('<a href="http://slurl.com/secondlife/'+sim.escapeHTML()+'/'+pos.x+'/'+pos.y+'/'+pos.z+'">'+sim.escapeHTML()+' ('+pos.x+', '+pos.y+', '+pos.z+')</a>');
			}
		}
	});
}

function makesortable(elem)
{
	Sortable.create('fieldlist',{
		tag: 'div',
		scroll: window,
		handle: 'fieldname',
		onUpdate: function(s) {
			var query = '';
			Sortable.sequence(s).each(function(item) {
				query += '&field['+(i++)+']='+encodeURI(item);
			});
			new Ajax.Request('/api/JSON/SetProfileFieldOrder',{
				postBody: 'profile='+encodeURI(gProfile)+'&userid='+encodeURI(readcookie('uid'))+'&key='+encodeURI(readcookie('sid'))+query
			});
		}
	});
}

function generatefields(loggedin,fields)
{
	var fieldoutput = '';
	for(i in fields)
	{
		fieldoutput += '<div class="field" id="basicfield_'+i.escapeHTML()+'">';
			if(loggedin || gIsModerator)
			{
				fieldoutput += '<span class="editfield">'+
				'[<span><a onclick="editfield(this,\''+addslashes(i).escapeHTML()+'\');">Edit</a></span>]';
				if(i != 'Gender')
				{
					fieldoutput += ' [<span><a onclick="deletefield(this,\''+addslashes(i).escapeHTML()+'\');">Delete</a></span>]';
				}
				fieldoutput += '</span> ';
			}
			fieldoutput += '<span class="fieldname"'+((loggedin||gIsModerator)?' style="cursor:pointer";':'')+'>'+i.escapeHTML()+':</span>'+
			'<div class="fieldvalue">'+fields[i].toString().escapeHTML()+'</div>'+
			'</div>';
	}
	return fieldoutput;
}


function addfield()
{
	$('addfield').update(
		'<input type="text" />:<br /><input type="text" style="width: 450px;" /><br />'+
		'<input type="button" onclick="completeadd()" value="Save" />'
	);
	$('addfield').down('input').activate();
}

function completeadd()
{
	var elem = $('addfield');
	var field = elem.down('input').getValue();
	var value = elem.down('input').next('input').getValue();
	elem.update("<em>Saving new field...</em>");
	new Ajax.Request('/api/JSON/AddProfileField',{
		parameters: {
			userid: readcookie('uid'),
			key: readcookie('sid'),
			field: field,
			value: value
		},
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				$('fieldlist').update(generatefields(true,response.response.fields));
				makesortable('fieldlist');
				$('addfield').update('[<a onclick="addfield()">Add field</a>]');
			}
			else
			{
				elem.update("Save failed: "+response.error.escapeHTML()).setStyle({color: '#f00', fontWeight: 'bold'});
			}
		}
	});
}

function editfield(a,field)
{
	var toreplace = $(a).up(1).next('div');
	$(a).up().update('Editing').setStyle({color: '#aaa'});
	var content = toreplace.innerHTML.unescapeHTML();
	if(field != 'Gender')
	{
		toreplace.replace('<br /><span id="edit-'+field.escapeHTML()+'"><input type="text" style="width: 450px;" value="'+content.escapeHTML()+'" />'+
			'<br /><input type="button" onclick="saveedit(\''+addslashes(field).escapeHTML()+'\')" value="Save" /></span>');
		$('edit-'+field.escapeHTML()).down('input').activate();
	}
	else
	{
		var option = '';
		option += '<option value="male"'+((content=='Male')?' selected="selected"':'')+'>Male</option>';
		option += '<option value="female"'+((content=='Female')?' selected="selected"':'')+'>Female</option>';
		option += '<option value="both"'+((content=='Both')?' selected="selected"':'')+'>Both</option>';
		option += '<option value="none"'+((content=='None')?' selected="selected"':'')+'>None</option>';
		option += '<option value="unspecified"'+((content=='Unspecified')?' selected="selected"':'')+'>Unspecified</option>';
		toreplace.replace('<br /><span id="edit-Gender"><select size="1">'+option+'</select> '+
			'<input type="button" onclick="saveedit(\'Gender\');" value="Save" />');
	}
}

function saveedit(field)
{
	var elem = $('edit-'+field);
	var content = '';
	if(field == 'Gender')
	{
		content = elem.down('select').getValue();
	}
	else
	{
		content = elem.down('input').getValue();
	}
	elem.update("<em>Saving...</em>");
	new Ajax.Request('/api/JSON/UpdateProfileField',{
		parameters: {
			userid: readcookie('uid'),
			key: readcookie('sid'),
			profile: gProfile,
			field: field,
			value: content
		},
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				$('fieldlist').update(generatefields(true,response.response.fields));
				makesortable('fieldlist');
			}
			else
			{
				elem.update("Save failed: "+response.error.escapeHTML()).setStyle({color: '#f00', fontWeight: 'bold'});
			}
		}
	});
}

function deletefield(a,field)
{
	if(confirm("Do you really want to delete '"+field+"'?\nDeletion cannot be undone."))
	{
		$(a).up().update("Deleting...").setStyle({color: '#aaa'});
		new Ajax.Request('/api/JSON/DeleteProfileField',{
			parameters: {
				userid: readcookie('uid'),
				key: readcookie('sid'),
				field: field,
				profile: gProfile
			},
			onSuccess: function(transport) {
				var response = eval('('+transport.responseText+')');
				if(response.success)
				{
					$('fieldlist').update(generatefields(true,response.response.fields));
					makesortable('fieldlist');
				}
				else
				{
					$(a).up().update("Delete failed: "+response.error.escapeHTML()).setStyle({color: '#f00'});
				}
			}
		});
	}
}

//- mark Ratings

function loadratings()
{
	var profileid = gProfile;
	var loggedin = gLoggedIn && gUser != gProfile;
	if(gTab == 'chat-on')
	{
		return;
	}
	gTab = 'ratings';
	$('profilecontent').update("Loading ratings...");
	var params = {id: profileid};
	if(loggedin)
	{
		params = {
			id: profileid,
			key: readcookie('sid'),
			userid: readcookie('uid')
		};
	}
	new Ajax.Request('/api/JSON/GetRatings',{
		parameters: params,
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				var text = '<h1>Ratings</h1>';
				text += showratings(profileid, loggedin, response.response.ratings);
				//text += '<em>Please note that negative ratings may not be visible unless multiple people have already rated this person negatively.</em>';
				$('profilecontent').update(text);
			}
			else
			{
				$('profilecontent').update("Error fetching ratings: "+response.error.escapeHTML()).setStyle({color: '#f00', textWeight: 'bold'});
			}
		}
	});
}

function showratings(profileid, loggedin, data)
{
	var text = '<table border="1" class="ratingtable">'+
		'<tr>'+
			'<th>&nbsp;</th>'+
			'<th>Positive</th>'+
			'<th>Negative</th>'+
			'<th>Total</th>';
	if(loggedin)
	{
		text += '<th>Rate</th>';
	}
	text += '</tr>';
	var totalp = 0;
	var totaln = 0;
	for(i in data)
	{
		text += '<tr>'+
			'<th>'+i.escapeHTML()+'</th>'+
			'<td>+'+data[i].positive+'</td>'+
			'<td>'+((data[i].negative==0)?'-':'')+data[i].negative+'</td>'+
			'<td>'+(((data[i].positive+data[i].negative)>0)?'+':'')+(data[i].positive+data[i].negative)+'</td>';
		if(loggedin)
		{
			text += '<td>'+
				((data[i].rated==1)?'<b>+1</b>':'<a onclick="rate(this,\''+profileid+'\',\''+addslashes(i).escapeHTML()+'\',1)">+1</a>')+' <b>/</b> '+
				((data[i].rated==0)?'<b>0</b>':'<a onclick="rate(this,\''+profileid+'\',\''+addslashes(i).escapeHTML()+'\',0)">0</a>')+' <b>/</b> '+
				((data[i].rated==-1)?'<b>-1</b>':'<a onclick="rate(this,\''+profileid+'\',\''+addslashes(i).escapeHTML()+'\',-1)">-1</a>')+
			'</td>';
		}
		text += '</tr>';
		totalp += data[i].positive;
		totaln += data[i].negative;
	}
	text += '<tr>'+
		'<th>Total</th>'+
		'<td>+'+totalp+'</td>'+
		'<td>'+((totaln==0)?'-':'')+totaln+'</td>'+
		'<td>'+(((totalp+totaln)>0)?'+':'')+(totalp+totaln)+'</td>'+
	(loggedin?'<td>&nbsp;</td>':'')+'</tr></table>';
	return text;
}

function rate(link, profileid, category, rating)
{
	var elem = $(link).up();
	elem.update("Rating...").setStyle({color: '#aaa'});
	new Ajax.Request('/api/JSON/SetRating',{
		parameters: {
			target: profileid,
			category: category,
			rating: rating,
			key: readcookie('sid'),
			userid: readcookie('uid')
		},
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				$('profilecontent').down('table').replace(showratings(profileid,true,response.response));
			}
			else
			{
				elem.update("Rate failed").setStyle({color: '#f00', fontWeight: 'bold'});
			}
		}
	});
}

//- mark Chat

function addchatline(line)
{
	$('chatlog').update($('chatlog').innerHTML+"<br /><span>"+parseurls(line.escapeHTML())+"</span>");
}

var ended = false;

function pollchat(session,lines)
{
	new Ajax.Request('/api/JSON/PollChat',{
		parameters: {
			sessionid: session,
			lines: lines,
			origin: "out"
		},
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				response.response.lines.each(function(item) {
					if(item.substr(0,3) == "/me")
					{
						addchatline(response.response.name+item.substr(3));
					}
					else
					{
						addchatline(response.response.name+": "+item);
					}
				});
				if(!response.response.terminated)
				{
					if(!lines)
						ajaxtimer = setTimeout('pollchat("'+addslashes(session)+'");',1000);
				}
				else if(!ended)
				{
					ended = true;
					addchatline('The chat session has been ended.');
					$('chatlog').next('input').clear().disable().next('input').disable().next('input').disable();
				}
			}
			else
			{
				addchatline('The chat session has ended due to an internal error: '+response.error);
			}
			$('chatlog').scrollTop = $('chatlog').scrollHeight;
		}
	});
}

function sendchat(session, username)
{
	var box = $('chatlog').next('input');
	var text = box.getValue();
	if(text.substr(0,3) == "/me")
	{
		addchatline(username+text.substr(3));
	}
	else
	{
		addchatline(username+": "+text);
	}
	pollchat(session,text);
	box.clear().focus();
}

function hangup(session)
{
	$('chatlog').next('input').clear().disable().next('input').disable().next('input').disable()
	new Ajax.Request('/api/JSON/TerminateChatSession', {
		parameters: {session: session}
	});
	gTab = 'chat';
}


function showchat(session,username)
{
	$('profilecontent').update('<div style="width: 90%; height: 300px; border: solid 1px #eee; overflow: auto; vertical-align: text-bottom;" id="chatlog">Connected.</div><input type="text" style="width:80%" onkeypress="javascript:return checkenter(event,\''+addslashes(session).escapeHTML()+'\', \''+addslashes(username).escapeHTML()+'\')" /><input type="button" value="Send" onclick="sendchat(\''+addslashes(session).escapeHTML()+'\', \''+addslashes(username).escapeHTML()+'\')" /> <input type="button" value="Hang up" onclick="hangup(\''+addslashes(session).escapeHTML()+'\')" />');
	pollchat(session,'');
}

function pollringingchat(session, polls, username)
{
	$('profilecontent').down('div').update("Ring number "+(polls++)+"...");
	new Ajax.Request('/api/JSON/RingChatSession', {
		parameters: {sessionid: session},
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				if(response.response.completed)
				{
					if(response.response.connected)
					{
						showchat(session,username);
					}
					else
					{
						gTab = 'chat';
						$('profilecontent').down('div').update("Connection refused: "+response.response.reason.escapeHTML());
					}
				}
				else
				{
					setTimeout('pollringingchat("'+addslashes(session)+'", '+polls+', "'+addslashes(username)+'");',1000);
				}
			}
			else
			{
				gTab = 'chat';
				$('profilecontent').down('div').update("The connection has been lost.").setStyle({color: 'red', fontWeight: 'bold'});
			}
		}
	});
}

function loadchat()
{
	var loggedin = gLoggedIn;
	var username = gUserName;
	var profileid = gProfile;
	if(gTab == 'chat' || gTab == 'chat-on')
	{
		return;
	}
	gTab = 'chat';
	$('profilecontent').update('<div></div>');
	var div = $('profilecontent').down();
	if(!loggedin)
	{
		div.update('You must be logged in to use live chat.').setStyle({color: 'red', fontWeight: 'bold'});
	}
	else
	{
		div.update('Checking online status; please wait...');
		new Ajax.Request('/api/JSON/UserOnline', {
			parameters: {id: profileid},
			onSuccess: function(transport) {
				var response = eval('('+transport.responseText+')');
				if(response.success)
				{
					if(response.response.online)
					{
						div.update('Checking target status...<br />Please continue waiting.');
						new Ajax.Request('/api/JSON/GetStatus', {
							parameters: {id: profileid},
							onSuccess: function(transport) {
								var response = eval('('+transport.responseText+')');
								var message = "Do you wish to initiate a chat session?";
								if(response.success)
								{
									if(response.response.afk)
									{
										message += "\nPlease bear in mind that they are currently AFK";
										if(!response.response.busy)
										{
											message += ", so may not respond immediately.";
										}
									}
									if(response.response.busy)
									{
										if(!response.response.afk)
										{
											message += "\nPlease bear in mind that they are currently busy, and as a result may not respond immediately.";
										}
										else
										{
											message += " and busy, so may not respond immediately.";
										}
									}
								}
								if(confirm(message))
								{
									div.update("Dialling...");
									new Ajax.Request('/api/JSON/InitiateChatSession', {
										parameters: {
											userid: readcookie('uid'),
											key: readcookie('sid'),
											target: profileid
										},
										onSuccess: function (transport) {
											var response = eval('('+transport.responseText+')');
											if(response.success)
											{
												var session = response.response.sessionid;
												gTab = 'chat-on';
												div.update("Ringing...");
												pollringingchat(session,1,username);
											}
											else
											{
												div.update("Connection attempt failed: "+response.error.escapeHTML()).setStyle({color: 'red', fontWeight: 'bold'});
											}
										}
									});
								}
								else
								{
									div.update("Connection cancelled. Pick another tab from above to continue.");
								}
							}
						});
					}
					else
					{
						div.update("You cannot chat with this agent as they are offline.");
					}
				}
			}
		});
	}
}

//- mark Comments

function makecomment(commenter)
{
	$('comment-'+commenter).disable().next('input').disable().value = 'Submitting...';
	var comment = $('comment-'+commenter).getValue();
	new Ajax.Request('/api/JSON/GiveComment', {
		parameters: {
			userid: readcookie('uid'),
			key: readcookie('sid'),
			id: gProfile,
			comment: comment,
			commenter: commenter
		},
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				if(gUser == commenter && $('profilecontent').down('div').next('div'))
				{
					$('profilecontent').down('div').next('div').remove();
				}
				if(gUser == commenter && !$('profilecontent').down('div',1))
				{
					$('profilecontent').down('div').update('');
				}
				if(!$('comment-'+commenter))
				{
					$('profilecontent').down('div').update('<div class="comment">' + 
						'<span class="commentname"><strong>You</strong> said:</span><br />'+
						'<textarea id="comment-'+commenter+'" cols="30" rows="6" style="width:90%; height:100px;">'+
						comment.gsub('<','&lt;').gsub('>','&gt;')+
						'</textarea><br />'+
						'<input type="button" value="Submit" />'+
					'</div>'+$('profilecontent').down('div').innerHTML);
					Event.observe($('comment-'+commenter).next('input'),'click',function() {
						makecomment(commenter)
					});
				}
				else
				{
					$('comment-'+commenter).enable().next('input').enable().value = 'Submit';
					Event.observe($('comment-'+commenter).next('input'),'click',function() {
						makecomment(commenter)
					});
				}
			}
			else
			{
				$('comment').next('input').value = "Submit failed.";
			}
		}
	});
}

function loadcomments(loggedin)
{
	var profile = gProfile;
	var loggedin = gLoggedIn && gUser != gProfile;
	if(gTab == 'chat-on')
	{
		return;
	}
	gTab = 'comments';
	var user = 0;
	if(loggedin)
	{
		user = readcookie('uid');
	}
	gTab = 'comments';
	$('profilecontent').update("<h1>Comments</h1><div>Loading...</div><div></div>");
	new Ajax.Request('/api/JSON/GetComments', {
		parameters: {id: profile},
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				var commented = false;
				var commentcount = response.response.comments.length;
				if(commentcount > 0)
				{
					$('profilecontent').down('div').update("Rendering results...");
					var text = "";
					response.response.comments.each(function(item) {
						var editable = false;
						if(item.commenter.id == user)
						{
							commented = true;
							editable = true;
						}
						if(gIsModerator)
						{
							editable = true;
						}
						if(!editable)
						{
							text += '<div class="comment">' + 
								'<span class="commentname"><strong><a href="/profiles/'+urlclean(item.commenter.name)+'/">'+item.commenter.name.escapeHTML()+'</a></strong> says:</span><br />'+
								item.comment.gsub('<','&lt;').gsub('>','&gt;').gsub('\n','<br />')+
							'</div>\n';
						}
						else
						{
							text += '<div class="comment"><span class="commentname">'+
								((gUser == item.commenter.id)?'<strong>You</strong> said':'<strong><a href="/profiles/'+urlclean(item.commenter.name)+'/">'+item.commenter.name.escapeHTML()+'</a></strong> says:')+
								'</span><br />' +
								'<textarea id="comment-'+item.commenter.id+'" cols="30" rows="6" style="width:90%; height:100px;">'+item.comment.gsub('<','&lt;').gsub('>','&gt;')+'</textarea><br />'+
								'<input type="button" value="Submit" /><span></span>' +
							'</div>\n';
						}
					});
					$('profilecontent').down('div').update(text).addClassName('commentlist');
					if(gIsModerator)
					{
						response.response.comments.each(function(item) {
							Event.observe($('comment-'+item.commenter.id).next('input'),'click',function() {
								makecomment(item.commenter.id);
							});
						});
					}
					else if(commented)
					{
						Event.observe($('comment-'+gUser).next('input'),'click',function() {
							makecomment(gUser);
						});
					}
				}
				else
				{
					$('profilecontent').down('div').update("Nobody has left any comments.");
				}
				if(!commented && loggedin)
				{
					$('profilecontent').down('div').next('div').update('<p>Leave a comment:<br />'+
						'<textarea id="comment-'+gUser+'" cols="30" rows="6" style="width:90%; height:100px;"></textarea><br />'+
						'<input type="button" value="Submit" /><span></span>'+
					'</p>');
					Event.observe($('comment-'+gUser).next('input'),'click',function() {
						makecomment(gUser);
					});
				}
			}
			else
			{
				$('profilecontent').down('div').update("Error fetching comments: "+response.error.escapeHTML()).setStyle({color: '#f00', fontWeight: 'bold'});
			}
		}
	});
}

//- mark Notes

function loadnotes()
{
	if(gTab == 'chat-on')
	{
		return;
	}
	gTab = 'notes';
	var loggedin = gLoggedIn;
	if(!loggedin)
	{
		$('profilecontent').update("You must be logged in to view your notes.");
		return;
	}
	$('profilecontent').update('<textarea cols="30" rows="20" style="width: 90%; height: 600" disabled="disabled">Loading...</textarea>'+
		'<br /><input type="button" value="Save" disabled="disabled" /><br /><br />Only you can see these notes.'
	);
	Event.observe($('profilecontent').down('input'),'click',function() {
		$('profilecontent').down('textarea').disable().next('input').disable().value = 'Saving...';
		new Ajax.Request('/api/JSON/SetNotes', {
			parameters: {
				userid: readcookie('uid'),
				key: readcookie('sid'),
				id: gProfile,
				note: $('profilecontent').down('textarea').getValue()
			},
			onSuccess: function(transport) {
				var response = eval('('+transport.responseText+')');
				if(response.success)
				{
					$('profilecontent').down('textarea').enable().next('input').enable().value = 'Save';
				}
				else
				{
					$('profilecontent').down('input').value = 'Save failed.';
				}
			}
		});
	});
	new Ajax.Request('/api/JSON/GetNotes', {
		parameters: {
			userid: readcookie('uid'),
			key: readcookie('sid'),
			id: gProfile
		},
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				//$('profilecontent').down('textarea').update(response.response.note.escapeHTML()).enable().next('input').enable();
				$('profilecontent').down('input').enable().previous('textarea').enable().value = response.response.note;
				$('profilecontent').down('textarea').focus();
			}
			else
			{
				$('profilecontent').down('textarea').update("Unable to load notes: "+response.error.escapeHTML());
			}
		}
	});
}

function checkenter(event,session,username)
{
	if ((event.keyCode == 13 || event.which == 13)) 
    {
        sendchat(session,username);
        return false;
    }
    return true;
}

//- mark Blog functions

function friendlytime(then)
{
	var now = Math.round(new Date().getTime()/1000);
	if(now - then < 2)
	{
		return "just now";
	}
	else if(now - then < 5)
	{
		return "less than 5 seconds ago";
	}
	else if(now - then < 15)
	{
		return "about 10 seconds ago";
	}
	else if(now - then < 25)
	{
		return "about 20 seconds ago";
	}
	else if(now - then < 35)
	{
		return "about 30 seconds ago";
	}
	else if(now - then < 45)
	{
		return "about 40 seconds ago";
	}
	else if(now - then < 55)
	{
		return "about 50 seconds ago";
	}
	else if(now - then < 120)
	{
		return "a moment ago";
	}
	else if(now - then < 180)
	{
		return "a couple of minutes ago";
	}
	else if(now - then < 300)
	{
		return "a few minutes ago";
	}
	else if(now - then < 600)
	{
		return "under ten minutes ago";
	}
	else if(now - then < 1200)
	{
		return "about a quarter of an hour ago";
	}
	else if(now - then < 1500)
	{
		return "20 minutes ago";
	}
	else if(now - then < 2100)
	{
		return "half an hour ago";
	}
	else if(now - then < 2700)
	{
		return "45 minutes ago";
	}
	else if(now - then < 86400)
	{
		var hours = Math.round((now - then)/3600);
		return hours+" hour"+((hours==1)?'':'s')+" ago";
	}
	else
	{
		return "on the "+formattime(then);
	}
}

function deleteblog(type, id)
{
	if(!gIsModerator) return;
	new Ajax.Request('/api/JSON/Delete'+type+'BlogEntry', {
		parameters: {
			post: id,
			blog: gProfile,
			userid: readcookie('uid'),
			key: readcookie('sid')
		},
		onSuccess: function(transport) {
			var response = transport.responseText.evalJSON(true);
			if(response.success)
			{
				$('blogentry-'+type+'-'+id).remove();
			}
			else
			{
				alert("Error deleting blog entry: "+response.error);
			}
		}
	});
}

function loadblog(mode, focusimage)
{
	if(gTab == 'chat-on')
	{
		return;
	}
	var loggedin = gLoggedIn && gProfile == gUser;
	var name = gProfileName;
	gTab = 'blog';
	$('profilecontent').update("Loading microblog...");
	new Ajax.Request('/api/JSON/Get'+mode+'BlogEntries', {
		parameters: {
			id: gProfile,
			limit: 30
		},
		onSuccess: function(transport) {
			var response = transport.responseText.evalJSON(true);
			$('profilecontent').update('<!-- <div class="tabset">'+
			'<span class="tab"><a onclick="loadblog(\'Friend\','+loggedin+',\''+name+'\')">Friends</a></span>'+
			'<span class="tab"><a onclick="loadblog(\'User\','+loggedin+',\''+name+'\')">'+name+'</a></span>'+
			'</div> --><div id="makeblogpost"></div><div id="microblog"></div>');
			if(response.success)
			{
				response = response.response;
				if(response.posts.length > 0)
				{
					var text = '';
					response.posts.each(function(post) {
						if(!post.content)
						{
							post.content = "(no description)";
						}
						if(!post.image)
						{
							text += '<div class="blogentry" id="blogentry-Text-'+post.id+'">'+
								'<!-- <span class="blogname">'+post.poster.name.escapeHTML()+'</span> --><span class="blogtext">'+parseurls(post.content.escapeHTML())+'</span>'+
								' <span class="blogmeta">- Posted '+friendlytime(post.time)+' from '+post.from.escapeHTML()+
								(gIsModerator?' [<a '+(Prototype.Browser.IE?'href="#" ':'')+' onclick="deleteblog(\'Text\','+post.id+')">Delete</a>]':'')+
								'</span>'+
							'</div>';
						}
						else
						{
							text += '<div class="blogentry" id="blogentry-Image-'+post.imageid+'">'+
								'<table border="0"><tr><td>'+
									'<a href="'+post.image+'" rel="lightbox[microblog]" title="'+post.name.escapeHTML().escapeHTML()+'" id="link-'+post.imageid+'">'+
										'<img src="'+post.image+'.thumb" alt="'+post.name.escapeHTML()+'" title="'+post.name.escapeHTML()+'" />'+
									'</a>'+
								'</td>'+
								'<td valign="middle">'+
									'<span class="blogtext">'+parseurls(post.content.escapeHTML())+'</span><br />'+
									'<span class="blogmeta">Posted from '+
									'<a href="http://tslurl.com/secondlife/'+post.sim.escapeHTML()+'/'+post.x+'/'+post.y+'/'+post.z+'">'+post.sim.escapeHTML()+' ('+post.x+', '+post.y+', '+post.z+')</a>'+
									' '+friendlytime(post.time)+
									(gIsModerator?' [<a '+(Prototype.Browser.IE?'href="#" ':'')+' onclick="deleteblog(\'Image\','+post.imageid+')">Delete</a>]':'')+
									'</span>'+
								'</td></tr></table>'+
							'</div>';
						}
					});
					$('microblog').update(text);
					myLightbox.updateImageList();
					if(focusimage != '' && focusimage >= 1)
					{
						var focusnode = $('link-'+focusimage);
						if(focusimage != '' && !focusnode)
						{
							focusnode = $(document.createElement('a'));
							focusnode.setStyle({
								display: 'none'
							});
							focusnode.setAttribute('href', '/images/ublog/img'+focusimage+'.png');
							focusnode.setAttribute('rel','lightbox');
						}
						window.myLightbox.start(focusnode);
					}
				}
				else
				{
					$('microblog').update("Nothing has been posted here yet!");
				}
				if(loggedin)
				{
					$('makeblogpost').update('<p>Add an update (255 character max):<br />'+
						'<input type="text" maxlength="255" size="40" style="width: 90%;">'+
						'<input type="button" value="Submit">'+
					'</p>');
					Event.observe($('makeblogpost').down('input[type=button]'),'click',function(event) {
						var elem = $('makeblogpost').down('input[type=text]');
						if(elem.present())
						{
							var text = elem.getValue();
							elem.disable().value = 'Transmitting...';
							elem.next('input[type=button]').disable();
							new Ajax.Request('/api/JSON/MakeBlogEntry', {
								parameters: {
									userid: readcookie('uid'),
									key: readcookie('sid'),
									from: 'the web',
									post: text 
								},
								onSuccess: function(transport) {
									var response = transport.responseText.evalJSON(true);
									if(response.success)
									{
										var div = $(document.createElement('div'));
										div.addClassName('blogentry');
										div.update('<!-- <span class="blogname">'+name.escapeHTML()+'</span> --><span class="blogtext">'+parseurls(text.escapeHTML())+'</span>'+
											' <span class="blogmeta">- Posted '+friendlytime((new Date().getTime()) / 1000)+' from the web</span>'
										);
										if($('microblog').down('div.blogentry'))
										{
											$('microblog').insertBefore(div,$('microblog').down('div.blogentry'));
										}
										else
										{
											$('microblog').update('').appendChild(div);
										}
										elem.enable().clear().next('input[type=button]').enable();
									}
									else
									{
										elem.enable().activate().value = "Error: "+response.error;
										elem.next('input[type=button]').enable();
									}
								}
							});
						}
					});
				}
			}
			else
			{
				$('profilecontent').update("Error loading blog entries: "+response.error.escapeHTML());
			}
		}
	});
}

Event.observe(window,'load',function() {
	if(gFirstTab == 'basic')
	{
		loadbasic();
	}
	else if(gFirstTab.startsWith('microblog'))
	{
		loadblog('User',gFirstTab.substr(10));
	}
	else if(gFirstTab == 'comments')
	{
		loadcomments();
	}
	else if(gFirstTab == 'ratings')
	{
		loadratings();
	}
	else if(gFirstTab == 'notes')
	{
		loadnotes();
	}
	window.myLightbox = new Lightbox();
});