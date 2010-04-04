/**
 * StarLight - A client side webpage framework
 *
 * @package StarLight
 * @author Icewind <icewind (at) derideal (dot) com>
 * @copyright 2009
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License
 * @url http://blacklight.metalwarp.com/starlight
 * @version 0.1
 */

OCNotification=function(text,time){
	this.text=text;
	this.time=(time)?time:0;
	this.notify();
}

OCNotification.prototype={
	notify:function(){
		this.holder=document.getElementById('OCNotificationHolder');
		if (!this.holder){
			this.holder=document.createElement('div');
			this.holder.className='OCNotificationHolder';
			this.holder.setAttribute('class','OCNotificationHolder');
			this.holder.setAttribute('id','OCNotificationHolder');
			document.getElementsByTagName('body').item(0).appendChild(this.holder);
		}
		this.notification=document.createElement('div');
		this.notification.className='OCNotification';
		this.notification.setAttribute('class','OCNotification');
		if (document.documentElement.innerHTML){
			this.notification.innerHTML=this.text;
		}else{
			var text=document.createTextNode(this.text);
			this.notification.appendChild(text);
		}
		this.holder.insertBefore(this.notification,this.holder.firstChild);
		this.notification.addEvent('onclick',new callBack(this.removeNotification,this));
		if (this.time>0){
			this.timer = new OCTimer(this.removeNotification, this.time,false,this);
		}
	},
	removeNotification:function(){
        if(this.notification){
            this.holder.removeChild(this.notification);
        }
	}
}