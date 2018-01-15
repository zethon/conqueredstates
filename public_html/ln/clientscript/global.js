//-----------------------------------------------------------------------------
// $RCSFile: global.js $ $Revision: 1.2 $ $Author: addy $ 
// $Date: 2006/06/11 03:03:56 $
//-----------------------------------------------------------------------------

var DHTML = (document.getElementById || document.all || document.layers);

function ToogleVisible(objname)
{
	if (!DHTML) return;

	var x = new getObj(objname);
	var flag = (x.style.display == 'none');
	
	x.style.display = (flag) ? '' : 'none';

	if (!flag)
	{
//		expires = new Date();
//		expires.setTime(expires.getTime() + (1000 * 86400 * 365));
//		set_cookie(objname, objname, expires);
		save_collapsed(objname,true);
	}
	else
	{
		save_collapsed(objname,false);
	}
	
	return flag;
}

function save_collapsed(objid, addcollapsed)
{
	var collapsed = fetch_cookie("cs_collapse");
	var tmp = new Array();

	if (collapsed != null)
	{
		collapsed = collapsed.split("\n");

		for (i in collapsed)
		{
			if (collapsed[i] != objid && collapsed[i] != "")
			{
				tmp[tmp.length] = collapsed[i];
			}
		}
	}

	if (addcollapsed)
	{
		tmp[tmp.length] = objid;
	}

	expires = new Date();
	expires.setTime(expires.getTime() + (1000 * 86400 * 365));
	set_cookie("cs_collapse", tmp.join("\n"), expires);
}

function doScroll(x,y,framename)
{
	if (framename == null)
		return;
		
	if( typeof(window.innerWidth) == 'number')
	{
		// Netscape, Mozilla, Firefox
		x -= (frames[framename].window.innerWidth/2);
		y -= (frames[framename].window.innerHeight/2);
	}
	else
	{
		// IE 6.0
		x -= (document.getElementById(framename).clientWidth/2);
		y -= (document.getElementById(framename).clientHeight/2);
	}
	
	window.frames[framename].window.scrollTo(x,y);
}


function scrollWindow(x,y,win)
{
	if (win == null)
		return;
		
	if( typeof(window.innerWidth) == 'number')
	{
		// Netscape, Mozilla, Firefox
		x -= (win.innerWidth/2);
		y -= (win.innerHeight/2);
	}
	else
	{
		// IE 6.0
		x -= (win.document.body.clientWidth/2);
		y -= (win.document.body.clientHeight/2);
	}
	
	win.scrollTo(x,y);
}



function getObj(name)
{
  if (document.getElementById)
  {
  	this.obj = document.getElementById(name);
	this.style = document.getElementById(name).style;
  }
  else if (document.all)
  {
	this.obj = document.all[name];
	this.style = document.all[name].style;
  }
  else if (document.layers)
  {
   	this.obj = document.layers[name];
   	this.style = document.layers[name];
  }
}

// #############################################################################
// ############ BEGIN COOKIE FUNCTIONS

// function to set a cookie
function set_cookie(name, value, expires)
{
	if (!expires)
	{
		expires = new Date();
	}
	
	document.cookie = name + "=" + escape(value) + "; expires=" + expires.toGMTString() +  "; path=/";
}

// #############################################################################
// function to retrieve a cookie
function fetch_cookie(name)
{
	cookie_name = name + "=";
	cookie_length = document.cookie.length;
	cookie_begin = 0;
	while (cookie_begin < cookie_length)
	{
		value_begin = cookie_begin + cookie_name.length;
		if (document.cookie.substring(cookie_begin, value_begin) == cookie_name)
		{
			var value_end = document.cookie.indexOf (";", value_begin);
			if (value_end == -1)
			{
				value_end = cookie_length;
			}
			return unescape(document.cookie.substring(value_begin, value_end));
		}
		cookie_begin = document.cookie.indexOf(" ", cookie_begin) + 1;
		if (cookie_begin == 0)
		{
			break;
		}
	}
	return null;
}

// #############################################################################
// function to delete a cookie
function delete_cookie(name)
{
	var expireNow = new Date();
	document.cookie = name + "=" + "; expires=Thu, 01-Jan-70 00:00:01 GMT" +  "; path=/";
}

// ############ END COOKIE FUNCTIONS
// #############################################################################



