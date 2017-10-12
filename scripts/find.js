var gSearching = false;

function search()
{
	if(!gSearching)
	{
		gSearching = true;
		var search = $('searchbox').getValue();
		if(search == '')
		{
			$('results').update("You have to search for someone!");
			gSearching = false;
			return;
		}
		new Ajax.Request('/api/JSON/FindPeople', {
			parameters: {search: search, limit:30},
			onSuccess: function(transport) {
				var response = eval('('+transport.responseText+')');
				if(response.success)
				{
					if(response.response.names.length > 0)
					{
						var text = '';
						response.response.names.each(function(item) {
							text += '<div class="person">'+
								'<span class="browsename'+((item.account>0)?' hasaccount':'')+'"><a href="/profiles/'+urlclean(item.name)+'/">'+item.name.escapeHTML()+'</a></span><br />'+
							'</div>';
						});
						$('results').update(text);
					}
					else
					{
						$('results').update("No results found.");
					}
				}
				else
				{
					$('results').update("Error performing search: "+response.error.escapeHTML());
				}
				gSearching = false;
			}
		});
	}
}

Event.observe(window,'load', function() {
	$('searchbox').activate();
	Event.observe($('searchbox'),'keydown',search);
	Event.observe($('searchbutton'),'click',search);
});