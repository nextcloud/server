/*
 * jPlayer Plugin for jQuery JavaScript Library
 * http://www.jplayer.org
 *
 * Copyright (c) 2009 - 2013 Happyworm Ltd
 * Dual licensed under the MIT and GPL licenses.
 *  - http://www.opensource.org/licenses/mit-license.php
 *  - http://www.gnu.org/copyleft/gpl.html
 *
 * Author: Mark J Panaghiston
 * Version: 2.2.20
 * Date: 21st March 2013
 *
 * FlashVars expected: (AS3 property of: loaderInfo.parameters)
 *	id: 	(URL Encoded: String) Id of jPlayer instance
 *	vol:	(Number) Sets the initial volume
 *	muted:	(Boolean in a String) Sets the initial muted state
 *	jQuery:	(URL Encoded: String) Sets the jQuery var name. Used with: someVar = jQuery.noConflict(true);
 *
 * Compiled using: Adobe Flex Compiler (mxmlc) Version 4.5.1 build 21328
 */

package {
	import flash.system.Security;
	import flash.external.ExternalInterface;

	import flash.utils.Timer;
	import flash.events.TimerEvent;
	
	import flash.text.TextField;
	import flash.text.TextFormat;

	import flash.events.KeyboardEvent;

	import flash.display.Sprite;
	import happyworm.jPlayer.*;

	import flash.display.StageAlign;
	import flash.display.StageScaleMode;
	import flash.events.Event;
	import flash.net.LocalConnection;
	import flash.events.StatusEvent;

	import flash.events.MouseEvent;

	import flash.ui.ContextMenu;
	import flash.ui.ContextMenuItem;
	import flash.events.ContextMenuEvent;
	import flash.net.URLRequest;
	import flash.net.navigateToURL;
	import flash.media.Video;

	public class Jplayer extends Sprite {
		private var jQuery:String;
		private var sentNumberFractionDigits:uint = 2;

		public var commonStatus:JplayerStatus = new JplayerStatus(); // Used for inital ready event so volume is correct.

		private var myInitTimer:Timer = new Timer(100, 0);

		private var myMp3Player:JplayerMp3;
		private var myMp4Player:JplayerMp4;

		private var myRtmpPlayer:JplayerRtmp;
		
		private var isRtmp:Boolean = false;
		private var isMp4:Boolean = false;

		private var isMp3:Boolean = false;
		private var isVideo:Boolean = false;

		private var securityIssue:Boolean = false; // When SWF parameters contain illegal characters
		private var directAccess:Boolean = false; // When SWF visited directly with no parameters (or when security issue detected)

		private var txLog:TextField;
		private var debug:Boolean = false; // Set debug to false for release compile!
		private var localAIRDebug:Boolean = false; // This is autodetermined by AIR app - leave false!

		private var traceOut:TraceOut;
		//private var outgoing_lc = new LocalConnection ();
		public function Jplayer() {

			flash.system.Security.allowDomain("*");
			traceOut = new TraceOut();

			// Fix to the security exploit reported by Jason Calvert http://appsec.ws/
			checkFlashVars(loaderInfo.parameters);

			if(!securityIssue) {
				jQuery = loaderInfo.parameters.jQuery + "('#" + loaderInfo.parameters.id + "').jPlayer";
				commonStatus.volume = Number(loaderInfo.parameters.vol);
				commonStatus.muted = loaderInfo.parameters.muted == "true";

				stage.scaleMode = StageScaleMode.NO_SCALE;
				stage.align = StageAlign.TOP_LEFT;
				stage.addEventListener(Event.RESIZE, resizeHandler);
				stage.addEventListener(MouseEvent.CLICK, clickHandler);

				var initialVolume:Number = commonStatus.volume;
				if(commonStatus.muted) {
					initialVolume = 0;
				}

				myMp3Player = new JplayerMp3(initialVolume);
				addChild(myMp3Player);

				myMp4Player = new JplayerMp4(initialVolume);
				addChild(myMp4Player);

				myRtmpPlayer = new JplayerRtmp(initialVolume);
				addChild(myRtmpPlayer);

				switchType("mp3"); // set default state to mp3
			}

			// The ContextMenu only partially works. The menu select events never occur.
			// Investigated and it is something to do with the way jPlayer inserts the Flash on the page.
			// A simple test inserting the Jplayer.swf on a page using: 1) SWFObject 2.2 works. 2) AC_FL_RunContent() works.
			// jPlayer Flash insertion is based on SWFObject 2.2 and the resaon behind this failure is not clear. The Flash insertion HTML on the page looks similar.
			var myContextMenu:ContextMenu = new ContextMenu();
			myContextMenu.hideBuiltInItems();
			var menuItem_jPlayer:ContextMenuItem = new ContextMenuItem("jPlayer " + JplayerStatus.VERSION);
			var menuItem_happyworm:ContextMenuItem = new ContextMenuItem("© 2009-2013 Happyworm Ltd", true);
			menuItem_jPlayer.addEventListener(ContextMenuEvent.MENU_ITEM_SELECT, menuSelectHandler_jPlayer);
			menuItem_happyworm.addEventListener(ContextMenuEvent.MENU_ITEM_SELECT, menuSelectHandler_happyworm);
			myContextMenu.customItems.push(menuItem_jPlayer, menuItem_happyworm);
			contextMenu = myContextMenu;

			// Log console for dev compile option: debug
			if(debug || directAccess) {
				txLog = new TextField();
				txLog.x = 5;
				txLog.y = 5;
				txLog.width = stage.stageWidth - 10;
				txLog.height = stage.stageHeight - 10;
				txLog.backgroundColor = 0xEEEEFF;
				txLog.border = true;
				txLog.background = true;
				txLog.text = "jPlayer " + JplayerStatus.VERSION;

				if(debug) {
					txLog.multiline = true;
					txLog.visible = false;
				} else if(directAccess) {
					txLog.visible = true;
				}
				if(debug && directAccess) {
					txLog.visible = true;
					log("Direct Access");
				}

				this.addChild(txLog);

				if(debug && !securityIssue) {
					this.stage.addEventListener(KeyboardEvent.KEY_UP, keyboardHandler);

					myMp3Player.addEventListener(JplayerEvent.DEBUG_MSG, debugMsgHandler);
					myMp4Player.addEventListener(JplayerEvent.DEBUG_MSG, debugMsgHandler);
					myRtmpPlayer.addEventListener(JplayerEvent.DEBUG_MSG, debugMsgHandler);
				}
			}

			if(!securityIssue) {
				// Delay init() because Firefox 3.5.7+ developed a bug with local testing in Firebug.
				myInitTimer.addEventListener(TimerEvent.TIMER, init);
				myInitTimer.start();
			}
		}

		private function switchType(playType:String):void {
			switch(playType) {
				case "rtmpa":
				isRtmp=true;
				isMp3=false;
				isMp4=false;
				isVideo=false;
				break;
			case "rtmpv":
				isRtmp=true;
				isMp3=false;
				isMp4=false;
				isVideo=true;
				break;
			case "mp3":
				isRtmp=false;
				isMp3=true;
				isMp4=false;
				isVideo=false;
				break;
			case "mp4":
				isRtmp=false;
				isMp3=false;
				isMp4=true;
				isVideo=false;
				break;
			case "m4v":
				isRtmp=false;
				isMp3=false;
				isMp4=true;
				isVideo=true;
				break;
			}
			
			listenToMp3(isMp3);
			listenToMp4(isMp4);
			listenToRtmp(isRtmp);
		}
		
		private function init(e:TimerEvent):void {
			myInitTimer.stop();
			if(ExternalInterface.available && !securityIssue) {
				ExternalInterface.addCallback("fl_setAudio_mp3", fl_setAudio_mp3);
				ExternalInterface.addCallback("fl_setAudio_m4a", fl_setAudio_m4a);
				ExternalInterface.addCallback("fl_setVideo_m4v", fl_setVideo_m4v);
				ExternalInterface.addCallback("fl_setAudio_rtmp", fl_setAudio_rtmp);
				ExternalInterface.addCallback("fl_setVideo_rtmp", fl_setVideo_rtmp);
				ExternalInterface.addCallback("fl_clearMedia", fl_clearMedia);
				ExternalInterface.addCallback("fl_load", fl_load);
				ExternalInterface.addCallback("fl_play", fl_play);
				ExternalInterface.addCallback("fl_pause", fl_pause);
				ExternalInterface.addCallback("fl_play_head", fl_play_head);
				ExternalInterface.addCallback("fl_volume", fl_volume);
				ExternalInterface.addCallback("fl_mute", fl_mute);

				ExternalInterface.call(jQuery, "jPlayerFlashEvent", JplayerEvent.JPLAYER_READY, extractStatusData(commonStatus)); // See JplayerStatus() class for version number.
			}
		}
		private function checkFlashVars(p:Object):void {
			var i:Number = 0;
			for each (var s:String in p) {
				if(illegalChar(s)) {
					securityIssue = true; // Illegal char found
				}
				i++;
			}
			if(i === 0 || securityIssue) {
				directAccess = true;
			}
		}
		private function illegalChar(s:String):Boolean {
			var illegals:String = "' \" ( ) { } * + / \\ < > = document";
			if(Boolean(s)) { // Otherwise exception if parameter null.
				for each (var illegal:String in illegals.split(' ')) {
					if(s.indexOf(illegal) >= 0) {
						return true; // Illegal char found
					}
				}
			}
			return false;
		}
		// switchType() here
		private function listenToMp3(active:Boolean):void {
			if(active) {
				myMp3Player.addEventListener(JplayerEvent.JPLAYER_ERROR, jPlayerFlashEvent);
				myMp3Player.addEventListener(JplayerEvent.JPLAYER_PROGRESS, jPlayerFlashEvent);
				myMp3Player.addEventListener(JplayerEvent.JPLAYER_TIMEUPDATE, jPlayerFlashEvent);
				myMp3Player.addEventListener(JplayerEvent.JPLAYER_ENDED, jPlayerFlashEvent);
				
				myMp3Player.addEventListener(JplayerEvent.JPLAYER_PLAY, jPlayerFlashEvent);
				myMp3Player.addEventListener(JplayerEvent.JPLAYER_PAUSE, jPlayerFlashEvent);
				myMp3Player.addEventListener(JplayerEvent.JPLAYER_LOADSTART, jPlayerFlashEvent);

				myMp3Player.addEventListener(JplayerEvent.JPLAYER_SEEKING, jPlayerFlashEvent);
				myMp3Player.addEventListener(JplayerEvent.JPLAYER_SEEKED, jPlayerFlashEvent);
			} else {
				myMp3Player.removeEventListener(JplayerEvent.JPLAYER_ERROR, jPlayerFlashEvent);
				myMp3Player.removeEventListener(JplayerEvent.JPLAYER_PROGRESS, jPlayerFlashEvent);
				myMp3Player.removeEventListener(JplayerEvent.JPLAYER_TIMEUPDATE, jPlayerFlashEvent);
				myMp3Player.removeEventListener(JplayerEvent.JPLAYER_ENDED, jPlayerFlashEvent);
				
				myMp3Player.removeEventListener(JplayerEvent.JPLAYER_PLAY, jPlayerFlashEvent);
				myMp3Player.removeEventListener(JplayerEvent.JPLAYER_PAUSE, jPlayerFlashEvent);
				myMp3Player.removeEventListener(JplayerEvent.JPLAYER_LOADSTART, jPlayerFlashEvent);

				myMp3Player.removeEventListener(JplayerEvent.JPLAYER_SEEKING, jPlayerFlashEvent);
				myMp3Player.removeEventListener(JplayerEvent.JPLAYER_SEEKED, jPlayerFlashEvent);
			}
		}
		private function listenToMp4(active:Boolean):void {
			if(active) {
				myMp4Player.addEventListener(JplayerEvent.JPLAYER_ERROR, jPlayerFlashEvent);
				myMp4Player.addEventListener(JplayerEvent.JPLAYER_PROGRESS, jPlayerFlashEvent);
				myMp4Player.addEventListener(JplayerEvent.JPLAYER_TIMEUPDATE, jPlayerFlashEvent);
				myMp4Player.addEventListener(JplayerEvent.JPLAYER_ENDED, jPlayerFlashEvent);

				myMp4Player.addEventListener(JplayerEvent.JPLAYER_PLAY, jPlayerFlashEvent);
				myMp4Player.addEventListener(JplayerEvent.JPLAYER_PAUSE, jPlayerFlashEvent);
				myMp4Player.addEventListener(JplayerEvent.JPLAYER_LOADSTART, jPlayerFlashEvent);

				myMp4Player.addEventListener(JplayerEvent.JPLAYER_SEEKING, jPlayerFlashEvent);
				myMp4Player.addEventListener(JplayerEvent.JPLAYER_SEEKED, jPlayerFlashEvent);

				myMp4Player.addEventListener(JplayerEvent.JPLAYER_LOADEDMETADATA, jPlayerMetaDataHandler); // Note the unique handler
			} else {
				myMp4Player.removeEventListener(JplayerEvent.JPLAYER_ERROR, jPlayerFlashEvent);
				myMp4Player.removeEventListener(JplayerEvent.JPLAYER_PROGRESS, jPlayerFlashEvent);
				myMp4Player.removeEventListener(JplayerEvent.JPLAYER_TIMEUPDATE, jPlayerFlashEvent);
				myMp4Player.removeEventListener(JplayerEvent.JPLAYER_ENDED, jPlayerFlashEvent);

				myMp4Player.removeEventListener(JplayerEvent.JPLAYER_PLAY, jPlayerFlashEvent);
				myMp4Player.removeEventListener(JplayerEvent.JPLAYER_PAUSE, jPlayerFlashEvent);
				myMp4Player.removeEventListener(JplayerEvent.JPLAYER_LOADSTART, jPlayerFlashEvent);

				myMp4Player.removeEventListener(JplayerEvent.JPLAYER_SEEKING, jPlayerFlashEvent);
				myMp4Player.removeEventListener(JplayerEvent.JPLAYER_SEEKED, jPlayerFlashEvent);

				myMp4Player.removeEventListener(JplayerEvent.JPLAYER_LOADEDMETADATA, jPlayerMetaDataHandler); // Note the unique handler
			}
		}

		private function listenToRtmp(active:Boolean):void {
			if(active) {
				myRtmpPlayer.addEventListener(JplayerEvent.JPLAYER_ERROR, jPlayerFlashEvent);
				myRtmpPlayer.addEventListener(JplayerEvent.JPLAYER_PROGRESS, jPlayerFlashEvent);
				myRtmpPlayer.addEventListener(JplayerEvent.JPLAYER_TIMEUPDATE, jPlayerFlashEvent);
				myRtmpPlayer.addEventListener(JplayerEvent.JPLAYER_ENDED, jPlayerFlashEvent);

				myRtmpPlayer.addEventListener(JplayerEvent.JPLAYER_PLAY, jPlayerFlashEvent);
				myRtmpPlayer.addEventListener(JplayerEvent.JPLAYER_PAUSE, jPlayerFlashEvent);
				myRtmpPlayer.addEventListener(JplayerEvent.JPLAYER_LOADSTART, jPlayerFlashEvent);
				
				myRtmpPlayer.addEventListener(JplayerEvent.JPLAYER_CANPLAY, jPlayerFlashEvent);

				myRtmpPlayer.addEventListener(JplayerEvent.JPLAYER_SEEKING, jPlayerFlashEvent);
				myRtmpPlayer.addEventListener(JplayerEvent.JPLAYER_SEEKED, jPlayerFlashEvent);

				myRtmpPlayer.addEventListener(JplayerEvent.JPLAYER_LOADEDMETADATA, jPlayerMetaDataHandler); // Note the unique handler
			} else {
				myRtmpPlayer.removeEventListener(JplayerEvent.JPLAYER_ERROR, jPlayerFlashEvent);
				myRtmpPlayer.removeEventListener(JplayerEvent.JPLAYER_PROGRESS, jPlayerFlashEvent);
				myRtmpPlayer.removeEventListener(JplayerEvent.JPLAYER_TIMEUPDATE, jPlayerFlashEvent);
				myRtmpPlayer.removeEventListener(JplayerEvent.JPLAYER_ENDED, jPlayerFlashEvent);

				myRtmpPlayer.removeEventListener(JplayerEvent.JPLAYER_PLAY, jPlayerFlashEvent);
				myRtmpPlayer.removeEventListener(JplayerEvent.JPLAYER_PAUSE, jPlayerFlashEvent);
				myRtmpPlayer.removeEventListener(JplayerEvent.JPLAYER_LOADSTART, jPlayerFlashEvent);
				
				myRtmpPlayer.addEventListener(JplayerEvent.JPLAYER_CANPLAY, jPlayerFlashEvent);

				myRtmpPlayer.removeEventListener(JplayerEvent.JPLAYER_SEEKING, jPlayerFlashEvent);
				myRtmpPlayer.removeEventListener(JplayerEvent.JPLAYER_SEEKED, jPlayerFlashEvent);

				myRtmpPlayer.removeEventListener(JplayerEvent.JPLAYER_LOADEDMETADATA, jPlayerMetaDataHandler); // Note the unique handler
			}
		}

		private function fl_setAudio_mp3(src:String):Boolean {
			if (src != null) {
				log("fl_setAudio_mp3: "+src);
				switchType("mp3");
				myMp4Player.clearFile();
				myRtmpPlayer.clearFile();
				myMp3Player.setFile(src);
				return true;
			} else {
				log("fl_setAudio_mp3: null");
				return false;
			}
		}
		private function fl_setAudio_rtmp(src:String):Boolean {
			tracer("SET RTMP: "+src);
			if (src != null) {
				log("fl_setAudio_rtmp: "+src);
				switchType("rtmpa");
				myMp4Player.clearFile();
				myMp3Player.clearFile();
				myRtmpPlayer.setFile(src,false);
				return true;
			} else {
				log("fl_setAudio_rtmp: null");
				return false;
			}
		}
		
		private function fl_setVideo_rtmp(src:String):Boolean {
			tracer("SET RTMP: "+src);
			if (src != null) {
				log("fl_setVideo_rtmp: "+src);
				switchType("rtmpv");
				myMp4Player.clearFile();
				myMp3Player.clearFile();
				myRtmpPlayer.setFile(src,true);
				return true;
			} else {
				log("fl_setVideo_rtmp: null");
				return false;
			}
		}
		private function fl_setAudio_m4a(src:String):Boolean {
			if (src != null) {
				log("fl_setAudio_m4a: "+src);
				switchType("mp4")
				myMp3Player.clearFile();
				myRtmpPlayer.clearFile();
				myMp4Player.setFile(src);
				return true;
			} else {
				log("fl_setAudio_m4a: null");
				return false;
			}
		}
		private function fl_setVideo_m4v(src:String):Boolean {
			if (src != null) {
				log("fl_setVideo_m4v: "+src);
				switchType("m4v");
				myMp3Player.clearFile();
				myRtmpPlayer.clearFile();
				myMp4Player.setFile(src);
				return true;
			} else {
				log("fl_setVideo_m4v: null");
				return false;
			}
		}
		private function fl_clearMedia():void {
			log("clearMedia.");
			myMp3Player.clearFile();
			myMp4Player.clearFile();
			myRtmpPlayer.clearFile();
		}

		private function getType():Object {
			var returnType:Object;
			if(isMp3) {
				returnType=myMp3Player;
			} 
			if(isRtmp) {
				returnType=myRtmpPlayer;
			}
			if(isMp4) {
				returnType=myMp4Player;
			}
			return returnType;
		}

		private function fl_load():Boolean {
			log("load.");
			var returnType:Object = getType();
			return returnType.load();
		}
		private function fl_play(time:Number = NaN):Boolean {
			log("play: time = " + time);
			var returnType:Object = getType();
			return returnType.play(time * 1000); // Flash uses milliseconds
		}
		private function fl_pause(time:Number = NaN):Boolean {
			log("pause: time = " + time);
			var returnType:Object = getType();
			return returnType.pause(time * 1000); // Flash uses milliseconds
		}
		private function fl_play_head(percent:Number):Boolean {
			log("play_head: "+percent+"%");
			var returnType:Object = getType();
			return returnType.playHead(percent);
		}
		private function fl_volume(v:Number):void {
			log("volume: "+v);
			commonStatus.volume = v;
			if(!commonStatus.muted) {
				myMp3Player.setVolume(v);
				myMp4Player.setVolume(v);
				myRtmpPlayer.setVolume(v);
			}
		}
		private function fl_mute(mute:Boolean):void {
			log("mute: "+mute);
			commonStatus.muted = mute;
			if(mute) {
				myMp3Player.setVolume(0);
				myMp4Player.setVolume(0);
				myRtmpPlayer.setVolume(0);
			} else {
				myMp3Player.setVolume(commonStatus.volume);
				myMp4Player.setVolume(commonStatus.volume);
				myRtmpPlayer.setVolume(commonStatus.volume);
			}
		}
		private function jPlayerFlashEvent(e:JplayerEvent):void {
			log("jPlayer Flash Event: " + e.type + ": " + e.target);
			//tracer("jPlayer Flash Event: " + e.type + ": " + e.target);
			if(ExternalInterface.available && !securityIssue) {
				ExternalInterface.call(jQuery, "jPlayerFlashEvent", e.type, extractStatusData(e.data));
			}
		}
		
		private function tracer(msg:String):void {
			traceOut.tracer(msg);
		}
		
		private function extractStatusData(data:JplayerStatus):Object {
			var myStatus:Object = {
				version: JplayerStatus.VERSION,
				src: data.src,
				paused: !data.isPlaying, // Changing this name requires inverting all assignments and conditional statements.
				srcSet: data.srcSet,
				seekPercent: data.seekPercent,
				currentPercentRelative: data.currentPercentRelative,
				currentPercentAbsolute: data.currentPercentAbsolute,
				currentTime: data.currentTime / 1000, // JavaScript uses seconds
				duration: data.duration / 1000, // JavaScript uses seconds
				videoWidth: data.videoWidth,
				videoHeight: data.videoHeight,
				volume: commonStatus.volume,
				muted: commonStatus.muted
			};
			log("extractStatusData: sp="+myStatus.seekPercent+" cpr="+myStatus.currentPercentRelative+" cpa="+myStatus.currentPercentAbsolute+" ct="+myStatus.currentTime+" d="+myStatus.duration);
			return myStatus;
		}
		private function jPlayerMetaDataHandler(e:JplayerEvent):void {
			log("jPlayerMetaDataHandler:" + e.target);
			if(ExternalInterface.available && !securityIssue) {
				resizeHandler(new Event(Event.RESIZE));
				ExternalInterface.call(jQuery, "jPlayerFlashEvent", e.type, extractStatusData(e.data));
			}
		}
		private function resizeHandler(e:Event):void {
			log("resizeHandler: stageWidth = " + stage.stageWidth + " | stageHeight = " + stage.stageHeight);

			var mediaX:Number = 0;
			var mediaY:Number = 0;
			var mediaWidth:Number = 0;
			var mediaHeight:Number = 0;
			
			var aspectRatioStage:Number = 0;
			var aspectRatioVideo:Number = 0;
			
			var videoItem:*;
			
			if(isRtmp) {
				videoItem = myRtmpPlayer;
			}
			if(isMp4) {
				videoItem = myMp4Player;
			}
			
			if(videoItem) {
				if(stage.stageWidth > 0 && stage.stageHeight > 0 && videoItem.myVideo.width > 0 && videoItem.myVideo.height > 0) {
					aspectRatioStage = stage.stageWidth / stage.stageHeight;
					aspectRatioVideo = videoItem.myVideo.width / videoItem.myVideo.height;
					if(aspectRatioStage < aspectRatioVideo) {
						mediaWidth = stage.stageWidth;
						mediaHeight = stage.stageWidth / aspectRatioVideo;
						mediaX = 0;
						mediaY = (stage.stageHeight - mediaHeight) / 2;
					} else {
						mediaWidth = stage.stageHeight * aspectRatioVideo;
						mediaHeight = stage.stageHeight;
						mediaX = (stage.stageWidth - mediaWidth) / 2;
						mediaY = 0;
					}
					resizeEntity(videoItem, mediaX, mediaY, mediaWidth, mediaHeight);
				}
			}
			if((debug || directAccess) && stage.stageWidth > 20 && stage.stageHeight > 20) {
				txLog.width = stage.stageWidth - 10;
				txLog.height = stage.stageHeight - 10;
			}
		}
		private function resizeEntity(entity:Sprite, mediaX:Number, mediaY:Number, mediaWidth:Number, mediaHeight:Number):void {
			entity.x = mediaX;
			entity.y = mediaY;
			entity.width = mediaWidth;
			entity.height = mediaHeight;
		}
		private function clickHandler(e:MouseEvent):void {
			// This needs to work with RTMP format too!
			if(isMp3) {
				jPlayerFlashEvent(new JplayerEvent(JplayerEvent.JPLAYER_CLICK, myMp3Player.myStatus, "click"))
			} else {
				jPlayerFlashEvent(new JplayerEvent(JplayerEvent.JPLAYER_CLICK, myMp4Player.myStatus, "click"))
			}
		}
		// This event is never called. See comments in class constructor.
		private function menuSelectHandler_jPlayer(e:ContextMenuEvent):void {
			navigateToURL(new URLRequest("http://jplayer.org/"), "_blank");
		}
		// This event is never called. See comments in class constructor.
		private function menuSelectHandler_happyworm(e:ContextMenuEvent):void {
			navigateToURL(new URLRequest("http://happyworm.com/"), "_blank");
		}
		private function log(t:String):void {
			if(debug) {
				txLog.text = t + "\n" + txLog.text;
				localAIRDebug = traceOut.localAIRDebug();
				if(localAIRDebug) {
					tracer(t);
				}
			}
		}
		private function debugMsgHandler(e:JplayerEvent):void {
			log(e.msg);
		}
		private function keyboardHandler(e:KeyboardEvent):void {
			log("keyboardHandler: e.keyCode = " + e.keyCode);
			switch(e.keyCode) {
				case 68 : // d
					txLog.visible = !txLog.visible;
					log("Toggled log display: " + txLog.visible);
					break;
				case 76 : // l
					if(e.ctrlKey && e.shiftKey) {
						txLog.text = "Cleared log.";
					}
					break;
			}
		}
	}
}
