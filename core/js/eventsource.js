/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2012 Robin Appelman icewind1991@gmail.com
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

/**
 * wrapper for server side events (http://en.wikipedia.org/wiki/Server-sent_events)
 * includes a fallback for older browsers and IE
 *
 * use server side events with causion, to many open requests can hang the server
 */

/**
 * create a new event source
 * @param string src
 * @param object data to be send as GET
 */
OC.EventSource=function(src,data){
	var dataStr='';
	this.typelessListeners=[];
	this.listeners={};
	if(data){
		for(name in data){
			dataStr+=name+'='+encodeURIComponent(data[name])+'&';
		}
	}
	if(!this.useFallBack && typeof EventSource !='undefined'){
		this.source=new EventSource(src+'?'+dataStr);
		this.source.onmessage=function(e){
			for(var i=0;i<this.typelessListeners.length;i++){
				this.typelessListeners[i](JSON.parse(e.data));
			}
		}.bind(this);
	}else{
		iframeId='oc_eventsource_iframe_'+OC.EventSource.iframeCount;
		OC.EventSource.fallBackSources[OC.EventSource.iframeCount]=this;
		this.iframe=$('<iframe/>');
		this.iframe.attr('id',iframeId);
		this.iframe.hide();
		this.iframe.attr('src',src+'?fallback=true&fallback_id='+OC.EventSource.iframeCount+'&'+dataStr);
		$('body').append(this.iframe);
		this.useFallBack=true;
		OC.EventSource.iframeCount++
	}
	//add close listener
	this.listen('__internal__',function(data){
		if(data=='close'){
			this.close();
		}
	}.bind(this));
}
OC.EventSource.fallBackSources=[];
OC.EventSource.iframeCount=0;//number of fallback iframes
OC.EventSource.fallBackCallBack=function(id,type,data){
	OC.EventSource.fallBackSources[id].fallBackCallBack(type,data);
}
OC.EventSource.prototype={
	typelessListeners:[],
	iframe:null,
	listeners:{},//only for fallback
	useFallBack:false,
	fallBackCallBack:function(type,data){
		if(type){
			for(var i=0;i<this.listeners[type].length;i++){
				this.listeners[type][i](data);
			}
		}else{
			for(var i=0;i<this.typelessListeners.length;i++){
				this.typelessListeners[i](data);
			}
		}
	},
	lastLength:0,//for fallback
	listen:function(type,callback){
		if(callback && callback.call){
			
			if(type){
				if(this.useFallBack){
					if(!this.listeners[type]){
						this.listeners[type]=[];
					}
					this.listeners[type].push(callback);
				}else{
					this.source.addEventListener(type,function(e){
						callback(JSON.parse(e.data));
					},false);
				}
			}else{
				typelessListeners.push(callback);
			}
		}
	},
	close:function(){
		this.source.close();
	}
}
