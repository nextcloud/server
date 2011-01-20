/*event handling
usage:    document.events.add(node,type,function,arguments);
   or:    node.addEvent(type,function,arguments);
*/

document.events=new Object;
document.events.functions=Array();
document.events.args=Array();

document.events.add=function(element,type,func,args){
	if(args){
		if(!args.push){
			args=[args];
		}
	}
	args=args||[];
	if (type && element){
		args.foreach(function(argument){
			func.bind(argument);
		})
		if(element.addEventListener){
			if(type.substr(0,2)=='on'){
				type=type.substr(2);
			}
			element.addEventListener(type,func,false);
		}else if(element.attachEvent){
			element.attachEvent(type,func);
		}
		return func;
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

Node.prototype.addEvent=function(type,func,args){
    return document.events.add(this,type,func,args);
}
Node.prototype.removeEvent=function(type,func){
    document.events.remove(this,type,func);
}