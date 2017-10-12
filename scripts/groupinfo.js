function showtab(e)
{
	var elem = Event.element(e);
	var tab = elem.id.substr(0,-3);
	alert(tab);
}

Event.observe($('chartertab').down('a'),'click',showtab);
Event.observe($('membertab').down('a'),'click',showtab);
if($('jointab'))
{
	Event.observe($('jointab').down('a'),'click',showtab);
}