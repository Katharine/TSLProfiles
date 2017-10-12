function displaylist(muted)
{
	if(muted.length > 0)
	{
		var html = '<table>';
		muted.each(function(s) {
			html += '<tr>'+
				'<td>'+s.name.escapeHTML()+'</td>'+
				'<td><a href="#" onclick="unmute(this,'+s.id+');">[<span>Unmute</span>]</a></td>'+
			'</tr>';
		});
		html += '</table>';
		$('muted').update(html);
	}
	else
	{
		$('muted').update("You haven't muted anyone!");
	}
}

function unmute(e,id)
{
	e.replace('<span>[<span style="color: #aaa;">Unmuting...</span>]</span>');
	new Ajax.Request('/api/JSON/Unmute', {
		parameters: {
			userid: readcookie('uid'),
			key: readcookie('sid'),
			id: id
		},
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				displaylist(response.response.muted);
			}
		}
	});
}

function loadlist()
{
	new Ajax.Request('/api/JSON/GetMuteList', {
		parameters: {
			userid: readcookie('uid'),
			key: readcookie('sid')
		},
		onSuccess: function(transport) {
			var response = eval('('+transport.responseText+')');
			if(response.success)
			{
				displaylist(response.response.muted);
			}
			else
			{
				$('muted').update("Failed to request mute list: "+response.error.escapeHTML());
			}
		}
	});
}

Event.observe(window,'load', function() {
	loadlist();
});