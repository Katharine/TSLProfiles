function ordinal(day)
{
	if(day%10==1&&day!=11)
	{
		return day+"st";
	}
	else
	{
		if(day%10==2&&day!=12)
		{
			return day+"nd";
		}
		else
		{
			if(day%10==3&&day!=13)
			{
				return day+"rd";
			}
			else
			{
				return day+"th";
			}
		}
	}
}

function formattime(time_t)
{
	var then=new Date(time_t*1000);
	var months=new Array();
	months[0]="January";
	months[1]="Febuary";
	months[2]="March";
	months[3]="April";
	months[4]="May";
	months[5]="June";
	months[6]="July";
	months[7]="August";
	months[8]="September";
	months[9]="October";
	months[10]="November";
	months[11]="December";
	return ordinal(then.getUTCDate())+" of "+months[then.getUTCMonth()]+", "+then.getUTCFullYear();
}

function readcookie(name)
{
	var temp=name+"=";
	var ca=document.cookie.split(";");
	for(var i=0;i<ca.length;i++){
		var c=ca[i];
		while(c.charAt(0)==" ")
		{
			c=c.substring(1,c.length);
		}
		if(c.indexOf(temp)==0)
		{
			return c.substring(temp.length,c.length);
		}
	}
	return null;
}

function addslashes(str)
{
	return str.replace(/\\/g,"\\\\").replace(/"/g,"\\\"").replace(/'/,"\\'");
}

function setcookie(name,value,expires)
{
	if(expires)
	{
		var _e = new Date();
		_e.setTime(_e.getTime()+(expires*24*60*60*1000));
		var dat="; expires="+_e.toGMTString();
	}
	else 
	{
		var dat="";
	}
	document.cookie=name+"="+value+dat+"; path=/";
}

function parseurls(str)
{
	return str.gsub(/(http:\/\/[a-zA-Z0-0-.]+\/[^ ;)]+)/,'<a href="#{1}" target="namelank">#{1}</a>'); 
}

function urlclean(url)
{
	return url.gsub(/[^a-zA-Z0-9_-]/,'_');
}