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

var READY_STATE_UNINITIALIZED=0;
var READY_STATE_LOADING=1;
var READY_STATE_LOADED=2;
var READY_STATE_INTERACTIVE=3;
var READY_STATE_COMPLETE=4;

/**
* Class for loaded browser independant xml loading
*/
OCXMLLoader=function(){
   this.errorCallBack=this.defaultError;
}

OCXMLLoader.prototype={
   contentType:'',
   method:'GET',
   request:'',
   callBack:null,
   async:true,
   
   /**
    * Loads an XML document
    * @param string url
    * @param string request
    * @none
    */
   load:function(url,request){
      request=(request)?request:"";
      method=this.method;
      contentType=(!this.contentType && method=="POST")?"application/x-www-form-urlencoded":this.contentType;
      if(window.XMLHttpRequest){
         req=new XMLHttpRequest();
      }else if(window.XDomainRequest){
         req=new XDomainRequest();
      }else if(window.ActiveXObject){
         req=new ActiveXObject('Microsoft.XMLHTTP')
      }
      if (req){
         this.req=req;
         try{
//             var loader=this;
//             req.onreadystatechange=function(){
//                loader.onReadyState.call(loader,req)
//             }
            var callback=new callBack(this.onReadyState,this);
            req.onreadystatechange=function(){eval('callBack.call('+callback.id+')');};
            req.open(method,url,this.async);
            if (contentType){
               req.setRequestHeader("Content-Type",contentType);
            }
            if(method=="POST"){
               req.setRequestHeader("Content-length", request.length);
               req.setRequestHeader("Connection", "close");
            }
            req.send(request);
         }catch (err){
            this.errorCallBack(req);
         }
      }
   },
   onReadyState:function(){
      var ready=this.req.readyState;
      if (ready==READY_STATE_COMPLETE){
         var HttpStatus=req.status;
         if (HttpStatus==200 || HttpStatus==0){
            //alert("response: "+this.req.responseText);
            this.callBack(this.req);
         }else{
            this.errorCallBack(this.req);
         }
      }
   },
   defaultError:function(req){
      alert("Error fetching data!"
      +"\n\n<br/><br/>ReadyState: "+req.readyState
      +"\n<br/>Status: "+req.status
      +"\n<br/>Headers: "+req.getAllResponseHeaders()
      +"\n<br/>File: "+req.url
      +"\n<br/>Response:  "+req.responseText);
   },
   /**
    * Sets the request method
    * @param string method
    * @none
    */
   setMethod:function(method){
      this.method=method;
   },
   /**
    * Sets the content type
    * @param string type
    * @none
    */
   setType:function(type){
      this.type=type;
   },
   /**
    * Sets the callback function
    * @param function callBack
    * @none
    */
   setCallBack:function(callBack){
      this.callBack=callBack;
   },
   /**
    * Sets the error callback function
    * @param function errorCallBack
    * @none
    */
   setErrorCallBack:function(errorCallBack){
      this.errorCallBack=errorCallBack;
   }
}

testClass=function(){
}

testClass.prototype={
   testFunc:function(){
      this.test="test";
      test=new OCXMLLoader(this);
      test.setCallBack(this.callBack);
      test.load(parseUri('%root%/data/sites/index.xml'));
   },
   callBack:function(req){
      alert(this.test);
      alert(req.responseText);
   }
}
test=new testClass()
test.testFunc
// mainLoadStack.append(test.testFunc,test);
