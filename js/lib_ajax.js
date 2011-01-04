/**
* ownCloud - ajax frontend
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
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

//The callBack object provides an easy way to pass a member of an object as callback parameter and makes sure that the 'this' is always set correctly when called.
//bindScope provides a much cleaner sollution but we keep this one for compatibility and instead implement is with bindScope
callBack=function(func,obj){
	var newFunction=func.bindScope(obj);
	callBack.callBacks[this.id]=newFunction;
}

callBack.callBacks=Array();

callBack.call=function(id,arg1,arg2,arg3,arg4,arg5,arg6,arg7,arg8,arg9,arg10){
   callback=callBack.callBacks[id];
   if(callback){
       return callback.func.call(callback.obj,arg1,arg2,arg3,arg4,arg5,arg6,arg7,arg8,arg9,arg10);
   }
}

//provide a simple way to add things to the onload
OC_onload=new Object();

OC_onload.items=new Array();
OC_onload.itemsPriority=new Array();
OC_onload.add=function(callback,priority){
    if(priority){
        OC_onload.itemsPriority[OC_onload.items.length]=callback;
    }else{
        OC_onload.items[OC_onload.items.length]=callback;
    }
}
OC_onload.run=function(){
    for(index in OC_onload.itemsPriority){
        if(OC_onload.itemsPriority[index].call){
           OC_onload.itemsPriority[index].call();
        }
    }
    for(index in OC_onload.items){
       if(OC_onload.items[index]&&OC_onload.items[index].call){
           OC_onload.items[index].call();
       }
    }
}

//implement Node.prototype under IE
if(typeof Node=='undefined'){
    Node=function(){};
    Node.prototype=new Object();
    
    tmpObj=new Object();
    tmpObj.prototype=document.createElement;
    document.createElementNative=document.createElement;
    tmpObj=null;
    
    document.createElement=function(tagName){
//         alert(tagName);
        node=document.createElementNative(tagName);
        var proto=new Node()
        var name;
        for(name in proto){
            node[name]=proto[name];
        }
        return node;
    }
    
    addNodePrototype=function(node){
        if(!node){
            node=document.getElementsByTagName('body');
            node=node.item(0)
        }
        if(node.nodeType==1){
            var proto=new Node()
			for(name in proto){
				node[name]=proto[name];
			}
            if(node.hasChildNodes){
                var childs=node.childNodes;
                for(var i=0;i<childs.length;i++){
                    addNodePrototype(childs[i]);
                }
            }
        }
    }
    OC_onload.add(new function(){addNodePrototype(document.documentElement);});
    OC_onload.add(addNodePrototype,true);
}

function getStyle(x,styleProp)
{
	if (x.currentStyle){
		var y = x.currentStyle[styleProp];
	}else if (window.getComputedStyle){
		var y = document.defaultView.getComputedStyle(x,null).getPropertyValue(styleProp);
	}
	return y;
}

Node.prototype.getStyle=function(styleProp){
	return getStyle(this,styleProp)
}

Node.prototype.clearNode=function(){
	if (this.hasChildNodes() ){
		while(this.childNodes.length>= 1){
			this.removeChild(this.firstChild);       
		} 
	}
}

setDebug=function(text){
	node=document.getElementById('debug');
	if(node){
		node.clearNode();
		node.appendChild(document.createTextNode(text));
	}
}

arrayMerge=function(array1,array2){
	var array=Array();
	for(i in array1){
		array[i]=array1[i];
	}
	for(i in array2){
		array[i]=array2[i];
	}
	return array;
}

if(!Math.sign){
	Math.sign=function(x){
		return x/Math.abs(x);
	}
}

if(!Node.prototype.clearNode){
	Node.prototype.clearNode=function(){
		if(this.hasChildNodes()){
			while(this.childNodes.length >=1){
				this.removeChild(this.firstChild);
			}
		}
	}
}

getTimeString=function(){
	var date=new Date();
	var months=new Array(12);
	months[0]="Jan";
	months[1]="Feb";
	months[2]="Mar";
	months[3]="Apr";
	months[4]="May";
	months[5]="Jun";
	months[6]="Jul";
	months[7]="Aug";
	months[8]="Sep";
	months[9]="Oct";
	months[10]="Nov";
	months[11]="Dec";
	return date.getDate()+' '+months[date.getMonth()]+' '+date.getFullYear()+' '+date.getHours()+':'+date.getMinutes();
}

loadScript=function(url){//dynamicly load javascript files
	url=WEBROOT+'/'+url;
	var script=document.createElement('script');
	script.setAttribute('type','text/javascript');
	script.setAttribute('src',url);
	body=document.getElementsByTagName('body').item(0);
	body.appendChild(script);
}

Function.prototype.bindScope=function(obj){
	var o=obj;
	var fn=this;
	return function(){
		return fn.apply(o,arguments);
	}
}

Function.prototype.bind=function(){
	var args = [];
	var fn=this;
	for (var n = 0; n < arguments.length; n++){
		args.push(arguments[n]);
	}
	return function (){
		var myargs = [];
		for (var m = 0; m < arguments.length; m++){
			myargs.push(arguments[m]);
		}
		return fn.apply(this, args.concat(myargs));
	};
}

Array.prototype.foreach=function(func,that){
	if (!func) return;
	that=that||this;
	var returns=[];
	for(var i=0;i<this.length;i++){
		returns.push(func.call(that,this[i]));
	}
	return returns;
}

Array.prototype.where = function(func,that) {
	var found = [];
	that=that||this;
	for(var i = 0, l = this.length; i < l; ++i) {
		var item = this[i];
		if(func.call(that,item)){
			found.push(item);
		}
	}
	return found;
};