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

eventHandler=function(){
	this.holders=Array();
}

eventHandler.prototype={
	add:function(element,type,func,arguments){
		var holder=this.getHolderByElement(element);
		holder.addListner(type,func,arguments);
	},
	getHolderByElement:function(element){
		var holder=false;
		for (var i=0;i<this.holders.length;i++){
			if (this.holders[i].getElement()==element){
				var holder=this.holders[i];
			}
		}
		if (!holder){
			var holder=new eventHolder(element);
			this.holders[this.holders.length]=holder;
		}
		return holder;
	},
	trigger:function(element,type,event){
		var holder=eventHandler.getHolderByElement(element);
		return holder.trigerEvent.call(holder,type,event);
	}
}

eventHolder=function(element){
	this.element=element;
	this.listners=Array();
}

eventHolder.prototype={
	addListner:function(type,func,arguments){
		if (type && this.element){
			if (!this.listners[type]){
				this.listners[type]=Array();
				eval("callback=function(event){return holder.trigerEvent.call(holder,'"+type+"',event)}");
				if (this.element.tagName){//do we have a element (not an named event)
					var holder=this;
					//IE doesn't let you set the onload event the regulair way
					if (type=="onload" && this.element.addEventListener && window.ActiveXObject){
						this.element.addEventListener(type, callback, false);
					}else if (type=="onload" && this.element.attachEvent && window.ActiveXObject){
						this.element.attachEvent(type, callback);
					}else{
						eval("this.element."+type+"=function(event){return holder.trigerEvent.call(holder,'"+type+"',event)}");
					}
				}else{
					eval("this.element."+type+"=function(event){return holder.trigerEvent.call(holder,'"+type+"',event)}");
				}
			}
			var i=this.listners[type].length
			this.listners[type][i]=func;
			this.listners[type][i].applyArguments=arguments;
		}else{
			var i=this.listners.length
			this.listners[i]=func;
			this.listners[type][i].applyArguments=arguments;
		}
	},
	trigerEvent:function(type,event){
		if (type && this.element && this.listners[type]){
			for (var i=0;i<this.listners[type].length;i++){
                   if(this.listners[type][i].applyArguments){
                       return this.listners[type][i].apply(this,this.listners[type][i].applyArguments)
                   }else{
                       return this.listners[type][i].call();
                   }
			}
		}else{
			for (var i=0;i<this.listners.length;i++){
                return this.listners[i](event);
			}
		}
	},
	getElement:function(){
		return this.element;
	}
}

document.events=new eventHandler();

Node.prototype.addEvent=function(type,func,arguments){
    document.events.add(this,type,func,arguments);
}