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
   this.func=func;
   this.obj=obj;
}

callBack.prototype=function(arg1,arg2,arg3,arg4,arg5,arg6,arg7,arg8,arg9,arg10){
   this.call(false,arg1,arg2,arg3,arg4,arg5,arg6,arg7,arg8,arg9,arg10);
}
callBack.prototype.func=false;
callBack.prototype.obj=false;
callBack.prototype.call=function(dummy,arg1,arg2,arg3,arg4,arg5,arg6,arg7,arg8,arg9,arg10){
   //the dummy is just to provide compatibility with the normal call function and isn't used
   this.func.call(this.obj,arg1,arg2,arg3,arg4,arg5,arg6,arg7,arg8,arg9,arg10);
}
callBack.prototype.apply=function(dummy,arguments){
   //the dummy is just to provide compatibility with the normal call function and isn't used
   this.apply(this.obj,arguments);
}

//provide a simple way to add things to the onload
OC_onload=new Object();

OC_onload.items=new Array();
OC_onload.add=function(callback){
   OC_onload.items[OC_onload.items.length]=callback;
}
OC_onload.run=function(){
   for(index in OC_onload.items){
      if(OC_onload.items[index].call){
         OC_onload.items[index].call();
      }
   }
}