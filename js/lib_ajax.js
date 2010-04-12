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
callBack=function(func,obj){
   this.id=callBack.callBacks.length;
   callBack.callBacks[this.id]=this;
   this.func=func;
   this.obj=obj;
}

callBack.callBacks=Array();

callBack.call=function(id,arg1,arg2,arg3,arg4,arg5,arg6,arg7,arg8,arg9,arg10){
   callback=callBack.callBacks[id];
   if(callback){
       return callback.func.call(callback.obj,arg1,arg2,arg3,arg4,arg5,arg6,arg7,arg8,arg9,arg10);
   }
}

callBack.prototype=function(arg1,arg2,arg3,arg4,arg5,arg6,arg7,arg8,arg9,arg10){
   return this.call(false,arg1,arg2,arg3,arg4,arg5,arg6,arg7,arg8,arg9,arg10);
}
callBack.prototype.func=false;
callBack.prototype.obj=false;
callBack.prototype.call=function(dummy,arg1,arg2,arg3,arg4,arg5,arg6,arg7,arg8,arg9,arg10){
   return this.func.call(this.obj,arg1,arg2,arg3,arg4,arg5,arg6,arg7,arg8,arg9,arg10);
}
callBack.prototype.apply=function(dummy,arguments){
   return this.func.apply(this.obj,arguments);
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
       if(OC_onload.items[index].call){
           OC_onload.items[index].call();
       }
    }
}

//implement Node.prototype under IE
if(typeof Node=='undefined'){
    Node=new Object();
    Node.prototype=new Object();
    
    tmpObj=new Object();
    tmpObj.prototype=document.createElement;
    document.createElementNative=document.createElement;
    tmpObj=null;
    
    document.createElement=function(tagName){
//         alert(tagName);
        node=document.createElementNative(tagName);
        for(name in Node.prototype){
            node[name]=Node.prototype[name];
        }
        return node;
    }
    
    addNodePrototype=function(node){
        if(!node){
            node=document.getElementsByTagName('body');
            node=node.item(0)
        }
        if(node.nodeType==1){
            for(name in Node.prototype){
//                 node[name]=Node.prototype[name];
                eval('node.'+name+'=Node.prototype.'+name+';');
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