var dw_event = {
  
  add: function(obj, etype, fp, cap) {
    cap = cap || false;
    if (obj.addEventListener) obj.addEventListener(etype, fp, cap);
    else if (obj.attachEvent) obj.attachEvent("on" + etype, fp);
  }, 

  remove: function(obj, etype, fp, cap) {
    cap = cap || false;
    if (obj.removeEventListener) obj.removeEventListener(etype, fp, cap);
    else if (obj.detachEvent) obj.detachEvent("on" + etype, fp);
  }, 

  DOMit: function(e) { 
    e = e? e: window.event;
    e.tgt = e.srcElement? e.srcElement: e.target;
    
    if (!e.preventDefault) e.preventDefault = function () { return false; }
    if (!e.stopPropagation) e.stopPropagation = function () { if (window.event) window.event.cancelBubble = true; }
        
    return e;
  }
  
}

var viewport = {
  getWinWidth: function () {
    this.width = 0;
    if (window.innerWidth) 
    {
    	this.width = window.innerWidth - 18;
    }
    else if (document.documentElement && document.documentElement.clientWidth) 
    {
  		this.width = document.documentElement.clientWidth;
  	}
    else if (document.body && document.body.clientWidth) 
    {
  		this.width = document.body.clientWidth;
  	}
  },
  
  getWinHeight: function () {
    this.height = 0;
    if (window.innerHeight) this.height = window.innerHeight - 18;
  	else if (document.documentElement && document.documentElement.clientHeight) 
  		this.height = document.documentElement.clientHeight;
  	else if (document.body && document.body.clientHeight) 
  		this.height = document.body.clientHeight;
  },
  
  getScrollX: function () {
    this.scrollX = 0;
  	if (typeof window.pageXOffset == "number") this.scrollX = window.pageXOffset;
  	else if (document.documentElement && document.documentElement.scrollLeft)
  		this.scrollX = document.documentElement.scrollLeft;
  	else if (document.body && document.body.scrollLeft) 
  		this.scrollX = document.body.scrollLeft; 
  	else if (window.scrollX) this.scrollX = window.scrollX;
  },
  
  getScrollY: function () {
    this.scrollY = 0;    
    if (typeof window.pageYOffset == "number") this.scrollY = window.pageYOffset;
    else if (document.documentElement && document.documentElement.scrollTop)
  		this.scrollY = document.documentElement.scrollTop;
  	else if (document.body && document.body.scrollTop) 
  		this.scrollY = document.body.scrollTop; 
  	else if (window.scrollY) this.scrollY = window.scrollY;
  },
  
  getAll: function () {
    this.getWinWidth(); this.getWinHeight();
    this.getScrollX();  this.getScrollY();
  }
  
}

var Tooltip = {
    followMouse: true,
    offX: 8,
    offY: 12,
    tipID: "tipDiv",
    showDelay: 100,
    hideDelay: 200,
ready:true,timer:null,tip:null,init:function()
{
	if(document.createElement&&document.body&&typeof document.body.appendChild!="undefined")
	{
		if(!document.getElementById(this.tipID))
		{
			var el=document.createElement("DIV");
			el.id=this.tipID;
			document.body.appendChild(el);
		}
		this.ready=true;
	}
},show:function(e,msg)
{
	if(this.timer)
	{
		clearTimeout(this.timer);
		this.timer=0;
	}
	this.tip=document.getElementById(this.tipID);
	if(this.followMouse)
		dw_event.add(document,"mousemove",this.trackMouse,true);
		this.writeTip("");
		this.writeTip(msg);
		viewport.getAll();
		this.positionTip(e);
		this.timer=setTimeout("Tooltip.toggleVis('"+this.tipID+"', 'visible')",this.showDelay);
},writeTip:function(msg)
{
	if(this.tip&&typeof this.tip.innerHTML!="undefined")
	this.tip.innerHTML=msg;
},positionTip:function(e)
{
	if(this.tip&&this.tip.style)
	{
		var x=e.pageX ? e.pageX : e.clientX+viewport.scrollX;
		var y=e.pageY?e.pageY:e.clientY+viewport.scrollY;
		
		if(x+this.tip.offsetWidth+this.offX>viewport.width+viewport.scrollX)
		{
			x=x-this.tip.offsetWidth-this.offX;
			if(x<0)
				x=0;
		}
		else 
			x = x + this.offX;
			
			if(y+this.tip.offsetHeight+this.offY>viewport.height+viewport.scrollY)
			{
				y=y-this.tip.offsetHeight-this.offY;
				if(y<viewport.scrollY)
					y=viewport.height+viewport.scrollY-this.tip.offsetHeight;
				}
				else y=y+this.offY;
				
				this.tip.style.left=x+"px";
				this.tip.style.top=y+"px";
				//this.tip.style.width = "50";
	}
},hide:function()
{
	if(this.timer)
	{
		clearTimeout(this.timer);
		this.timer=0;
	}
	this.timer=setTimeout("Tooltip.toggleVis('"+this.tipID+"', 'hidden')",this.hideDelay);
	if(this.followMouse)
		dw_event.remove(document,"mousemove",this.trackMouse,true);
		
		this.tip=null;
},toggleVis:function(id,vis)
{
	var el=document.getElementById(id);
	if(el)el.style.visibility=vis;
},trackMouse:function(e)
	{
		e=dw_event.DOMit(e);
		Tooltip.positionTip(e);
	}
};

var dw_Inf={};
dw_Inf.fn=function(v)
{
	return eval(v)
};
	
// *************** ToolTip functionality ************** //
function doTooltip(e, msg) {
  if ( typeof Tooltip == "undefined" || !Tooltip.ready ) return;
  Tooltip.show(e, msg);
}

function hideTip() {
  if ( typeof Tooltip == "undefined" || !Tooltip.ready ) return;
  Tooltip.hide();
}
	
	
// ************** HoverTip functionality *************** //
// adjust horizontal and vertical offsets here
// (distance from mouseover event which activates tooltip)
Tooltip.offX = 4;  
Tooltip.offY = 4;
Tooltip.followMouse = false;  // must be turned off for hover-tip

function doHoverTooltip(e, msg) 
{
  if ( typeof Tooltip == "undefined" || !Tooltip.ready ) 
  	return;
  	
  Tooltip.clearTimer();
  var tip = document.getElementById ? document.getElementById(Tooltip.tipID) : null;
  if ( tip && tip.onmouseout == null ) 
  {
      tip.onmouseout = Tooltip.tipOutCheck;
      tip.onmouseover = Tooltip.clearTimer;
  }
  Tooltip.show(e, msg);
}

function hideHoverTip() {
  if ( typeof Tooltip == "undefined" || !Tooltip.ready ) return;
  Tooltip.timerId = setTimeout("Tooltip.hide()", 300);
}

Tooltip.tipOutCheck = function(e) {
  e = dw_event.DOMit(e);
  // is element moused into contained by tooltip?
  var toEl = e.relatedTarget? e.relatedTarget: e.toElement;
  if ( this != toEl && !contained(toEl, this) ) Tooltip.hide();
}

// returns true of oNode is contained by oCont (container)
function contained(oNode, oCont) {
  if (!oNode) return; // in case alt-tab away while hovering (prevent error)
  while ( oNode = oNode.parentNode ) if ( oNode == oCont ) return true;
  return false;
}

Tooltip.timerId = 0;
Tooltip.clearTimer = function() {
  if (Tooltip.timerId) { clearTimeout(Tooltip.timerId); Tooltip.timerId = 0; }
}

Tooltip.unHookHover = function () {
    var tip = document.getElementById? document.getElementById(Tooltip.tipID): null;
    if (tip) {
        tip.onmouseover = null; 
        tip.onmouseout = null;
        tip = null;
    }
}

dw_event.add(window, "unload", Tooltip.unHookHover, true);