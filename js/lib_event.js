/**
 * eventHandler
 *
 * @author Icewind <icewind (at) derideal (dot) com>
 * @copyright 2009
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License
 * @version 0.1
 */

/*event handling
usage:    document.events.add(node,type,function,arguments);
   or:    node.addEvent(type,function,arguments);
*/

document.events=new Object;
document.events.functions=Array();
document.events.args=Array();

document.events.add=function(element,type,func,args){
	if(!element.eventCallBacks){
		element.eventCallBacks=Array();
	}
	if(!element.eventCallBacks[type]){
		element.eventCallBacks[type]=Array();
	}
	if(args){
		if(!args.push){
			args=[args];
		}
	}
	if(!args){
		args=Array();
	}
	args.push('eventHolder');
	args.push('argHolder');
	if (type && element){
		
		//wrap the function in a function, otherwise it won't work if func is actually a callBack
		var funcId=document.events.functions.length;
		document.events.functions[funcId]=func;
		document.events.args[funcId]=args;
		eval('var callback=function(event,arg){document.events.callback.call(this,'+funcId+',event,arg)};');
		element.eventCallBacks[type].push(callback);
		if(element.addEventListener){
			var eventType=type;
			if(eventType.substr(0,2)=='on'){
				eventType=eventType.substr(2);
			}
			element.addEventListener(eventType,callback,false);
		}else if(element.attachEvent){
			element.attachEvent(type,callback);
		}
		return callback;
	}
}
document.events.remove=function(element,type,func){
	if(element.removeEventListener){
		if(type.substr(0,2)=='on'){
			type=type.substr(2);
		}
		element.removeEventListener(type,func,false);
	}else if(element.detachEvent){
		element.detachEvent(type,func)
	}
}

document.events.callback=function(funcId,event,arg){
	if(!event)var event=window.event;
	var args=document.events.args[funcId];
	args[args.length-2]=event;
	args[args.length-1]=arg;
	result=document.events.functions[funcId].apply(this,args);
	if(result===false){
		if(event.preventDefault){
			event.preventDefault();
		};
	}
	return result;
}

document.events.trigger=function(element,type,event,args){
	var callbacks=element.eventCallBacks[type];
	for(var i=0;i<callbacks.length;i++){
		callbacks[i].call(element,event,args);
	}
}

Node.prototype.addEvent=function(type,func,arguments){
    return document.events.add(this,type,func,arguments);
}
Node.prototype.removeEvent=function(type,func){
    document.events.remove(this,type,func);
}
Node.prototype.triggerEvent=function(type,event,arg){
    return document.events.trigger(this,type,event,arg);
}