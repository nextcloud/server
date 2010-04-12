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
OCTimer=function(callback,time,repeat,object){
	this.object=(object)?object:false;
	this.repeat=(!(repeat===undefined))?repeat:true;
	this.callback=callback;
	this.time=time;
	this.timer=0;
	this.number=OCTimer.count;
	OCTimer.count++;
	OCTimer.timers[this.number]=this;
	if(this.time){
		this.start();
	}
}

OCTimer.count=0;
OCTimer.timers=Array();

OCTimer.prototype={
	start:function(){
		this.running=true;
		eval('var func=function(){OCTimer.timers['+this.number+'].run();};');
		if(this.repeat){
			this.timer = setInterval(func, this.time);
		}else{
			this.timer = setTimeout(func, this.time);
		}
	},
	run:function(){
		if (!this.repeat){
			this.stop();
		}
		if (this.object){
			this.callback.call(this.object);
		}else{
			this.callback.call();
		}
	},
	stop:function(){
		clearInterval(this.timer);
		this.running=false;
	}
}