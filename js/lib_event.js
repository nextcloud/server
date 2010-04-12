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
	if(args){
		if(typeof args!='object' && typeof args!='Object'){
			args=[args];
		}
	}
	if(!args){
		args=Array();
	}
	if (type && element){
		
		//wrap the function in a function, otherwise it won't work if func is actually a callBack
		var funcId=document.events.functions.length;
		document.events.functions[funcId]=func;
		document.events.args[funcId]=args;
		eval('callback=function(event){result=document.events.functions['+funcId+'].apply(this,document.events.args['+funcId+']);if(result===false){if(event.preventDefault){event.preventDefault();}}};');
		if(element.addEventListener){
			var eventType=type;
			if(eventType.substr(0,2)=='on'){
				eventType=eventType.substr(2);
			}
			element.addEventListener(eventType,callback,false);
		}else{
			element.attachEvent(type,callback);
		}
	}
}

Node.prototype.addEvent=function(type,func,arguments){
    document.events.add(this,type,func,arguments);
}