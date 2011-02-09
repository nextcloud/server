/**
* Javascript Drag&Drop - Modified for ownCloud
*
* @author Robin Appelman
* @copyright 2010 Robin Appelman icewind1991@gmail.com
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Affero General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

position=function(x,y){
	if(x)this.x=x;
	if(y)this.y=y;
	return this;
}
position.prototype={
	x:0,
	y:0,
	add:function(pos2){
		return new position(this.x+pos2.x,this.y+pos2.y);
	},
	substract:function(pos2){
		return new position(this.x-pos2.x,this.y-pos2.y);
	},toString:function(){
		return 'x:'+this.x+',y:'+this.y;
	},inside:function(pos2){
		return Math.abs(this.x)<Math.abs(pos2.x) && Math.abs(this.y)<Math.abs(pos2.y) && Math.sign(this.x)==Math.sign(pos2.x) && Math.sign(this.y)==Math.sign(pos2.y);
	},outside:function(pos2){
		return !this.inside(pos2);
	}
}

Node.prototype.drag=new Object
/**
 * is the node dragable
 */
Node.prototype.drag.dragable=false;
/**
 * Are we currently dragging the node
 */
Node.prototype.drag.active=false;
/**
 * Create a clone to drag around
 */
Node.prototype.drag.clone=true;
/**
 * The node we (visually drag around)
 */
Node.prototype.drag.node=false;
/**
 * can we drop nodes on this
 */
Node.prototype.drag.isDropTarget=false;
/**
 * our current drop target
 */
Node.prototype.drag.dropTarget=null;
/**
 * can we drop this node now
 */
Node.prototype.drag.dropable=false;
/**
 * function called when we are being dropped on a node
 * @return bool
 */
Node.prototype.drag.onDrop=function(node){};
/**
 * function called when an node is dropped on us
 * @param Node node
 * @return bool
 */
Node.prototype.drag.onDropOn=function(node){};
/**
 * where did we start the drag
 */
Node.prototype.drag.startPosition=new position();
/**
 * where are we now
 */
Node.prototype.drag.position=new position();
/**
 * how big are we
 */
Node.prototype.drag.size=new position();
/**
 * where is the mouse
 */
Node.prototype.drag.mousePosition=new position();
/**
 * where is the mouse relative to our node
 */
Node.prototype.drag.mouseOffset=new position();

document.drag=new Object();
/**
 * is there currently something dragged
 */
document.drag.active=false;
/**
 * what is currently being dragged
 */
document.drag.node=null;
document.drag.dropTargets=Array();
/**
 * start the dragging. (onmousedown)
 * @param Event event
 */
Node.prototype.drag.start=function(event){
	if(!event)var event=window.event;
	if(!this.drag.active && this.drag.dragable){
		document.drag.active=true;
		document.drag.node=this;
		this.drag.active=true;
		this.drag.position=this.getPosition();
		this.drag.startPosition=this.getPosition();
		this.drag.mousePosition=getMousePosition(event);
		this.drag.mouseOffset=this.drag.mousePosition.substract(this.drag.position);
	}
}

/**
 * update the dragging. (onmousemove)
 * @param Event event
 */
Node.prototype.drag.update=function(event){
	if(!event)var event=window.event;
	if(this.drag.active && this.drag.dragable){
		this.drag.mousePosition=getMousePosition(event);
		this.drag.position=this.drag.mousePosition.substract(this.drag.mouseOffset);
		if(this.drag.clone && !this.drag.node){
			this.drag.node=this.cloneNode(true);
			this.drag.node.className='dragClone';
			if(this.drag.node.hasAttribute('id')){
				this.drag.node.setAttribute('id',this.drag.node.getAttribute('id')+'_dragClone');
			}
			document.getElementsByTagName('body').item(0).appendChild(this.drag.node);
		}else if(!this.drag.node){
			this.drag.node=this;
			this.drag.node.style.position='absolute';
		}
		this.drag.node.style.left=this.drag.position.x+'px';
		this.drag.node.style.top=this.drag.position.y+'px';
	}
	return true;
}

/**
 * stop the dragging/drop. (onmouseup)
 * @param Event event
 * @return bool
 */
Node.prototype.drag.stop=function(event){
	if(!event)var event=window.event;
	if(this.drag.active && this.drag.dragable){
		this.drag.active=false;
		this.drag.mousePosition=getMousePosition(event);
		this.drag.position=this.drag.mousePosition.substract(this.drag.mouseOffset);
		if(this.drag.node){
			this.drag.node.style.left=this.drag.position.x;
			this.drag.node.style.top=this.drag.position.y;
		}
		var target;
		this.drag.dropTarget=null;
		this.drag.dropable=false;
		for(var i=0;i<document.drag.dropTargets.length;i++){
			target=document.drag.dropTargets[i];
			target.drag.checkDropTarget.call(target,event);
		}
		if(this.drag.dropable && this.drag.dropTarget){
			if(this.drag.onDrop){
				this.drag.onDrop.call(this,event,this.drag.dropTarget);
				this.triggerEvent.call(this,'ondrop',event,this.drag.dropTarget);
			}
			if(this.drag.dropTarget.drag.onDropOn){
				this.drag.dropTarget.drag.onDropOn.call(this.drag.dropTarget,event,this);
				this.drag.dropTarget.triggerEvent.call(this.drag.dropTarget,'ondropon',event,this);
			}
		}
		if(this.drag.clone && this.drag.node){
			this.drag.node.parentNode.removeChild(this.drag.node);
			this.drag.node=null;
		}
		document.drag.active=false;
		document.drag.node=null;
	}
}

/**
 * is there currently something being dragged over us
 * @param Event event
 */
Node.prototype.drag.checkDropTarget=function(event){
	if(this.drag.isDropTarget & document.drag.active){
		mousePos=getMousePosition(event);
		this.drag.position=this.getPosition();
		this.drag.size=this.getSize(true);
		var offSet=mousePos.substract(this.drag.position);
		if(offSet.inside(this.drag.size)){
			document.drag.node.drag.dropTarget=this;
			document.drag.node.drag.dropable=true;
			setDebug('ontarget');
		}
	}
}

/**
 * called when the mouse is leaving a drop target
 * @param Event event
 */
Node.prototype.drag.leaveDropTarget=function(event){
	if(this.drag.isDropTarget & document.drag.active){
		document.drag.node.drag.dropTarget=null;
		document.drag.node.drag.dropable=false;
		setDebug('offtarget');
	}
}
/**
 * initiate the node as drop target
 */
Node.prototype.drag.initDropTarget=function(){
	this.drag.isDropTarget=true;
	document.drag.dropTargets.push(this);
}
Node.prototype.makeDropTarget=function(){
	this.drag.initDropTarget.call(this);
}

/**
 * initiate the node as draggable
 */
Node.prototype.drag.init=function(){
	this.drag.dragable=true;
	this.drag.size.x=this.getStyle('width');
	this.drag.size.y=this.getStyle('height');
	this.addEvent('onmousedown',new callBack(this.drag.start,this));
}
Node.prototype.makeDraggable=function(){
	this.drag.init.call(this);
}

/**
 * update the dragging. (onmousemove)
 * @param Event event
 */
document.drag.update=function(event){
	var target;
	if(document.drag.active && document.drag.node){
		document.drag.node.drag.update.call(document.drag.node,event);
	}
}

/**
 * update the dragging. (onmousemove)
 * @param Event event
 */
document.drag.stop=function(event){
	if(document.drag.active && document.drag.node){
		document.drag.node.drag.stop.call(document.drag.node,event);
	}
}
document.events.add(document,'onmousemove',document.drag.update);
document.events.add(document,'onmouseup',document.drag.stop);

function getMousePosition(event){
	var pos=new position();
	if(!event)var event = window.event;
	if(event.pageX||event.pageY){
		pos.x=event.pageX;
		pos.y=event.pageY;
	}
	else if(event.clientX||event.clientY){
		pos.x=event.clientX+document.body.scrollLeft+document.documentElement.scrollLeft;
		pos.y=event.clientY+document.body.scrollTop+document.documentElement.scrollTop;
	}
	return pos;
}

/**
 * get our position
 **/
Node.prototype.getPosition=function(){
	var pos=new position();
	element=this;
	do{
		pos.y+=element.offsetTop;
		pos.x+=element.offsetLeft;
	}while(element=element.offsetParent);
	return pos;
}

/**
 * get our size
* @param bool full      (also include padding and border)
 **/
Node.prototype.getSize=function(full){
	var pos=new position();
	pos.y= parseInt(this.getStyle('height'));
	pos.x= parseInt(this.getStyle('width'));
	if(full){
		var extraY=['border-size','padding-top','padding-bottom','border-size'];
		var extraX=['border-size','padding-left','padding-right','border-size'];
		var tmp;
		for(var i=0;i<extraY.length;i++){
			tmp=parseInt(this.getStyle(extraY[i]));
			if(tmp){
				pos.y+=tmp;
			}
		}
		for(var i=0;i<extraX.length;i++){
			tmp=parseInt(this.getStyle(extraX[i]));
			if(tmp){
				pos.x+=tmp;
			}
		}
	}
	return pos;
}

function mouseTest(event){
	var pos=getMousePosition(event);
	setDebug(pos.toString());
}

function testDrag(){
	var node=document.getElementById('debug');
// 	document.addEvent('onclick',getOffSet,[node]);
	node.makeDropTarget();
}

function getOffSet(node,event){
	var nodePos=node.getPosition();
	var mousePos=getMousePosition(event);
	return mousePos.substract(nodePos);
}


// OC_onload.add(testDrag);