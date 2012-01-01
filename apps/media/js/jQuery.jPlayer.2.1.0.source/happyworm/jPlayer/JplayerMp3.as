/*
 * jPlayer Plugin for jQuery JavaScript Library
 * http://www.happyworm.com/jquery/jplayer
 *
 * Copyright (c) 2009 - 2011 Happyworm Ltd
 * Dual licensed under the MIT and GPL licenses.
 *  - http://www.opensource.org/licenses/mit-license.php
 *  - http://www.gnu.org/copyleft/gpl.html
 *
 * Author: Mark J Panaghiston
 * Date: 1st September 2011
 */

package happyworm.jPlayer {
	import flash.display.Sprite;

	import flash.media.Sound;
	import flash.media.SoundChannel;
	import flash.media.SoundLoaderContext;
	import flash.media.SoundTransform;
	import flash.net.URLRequest;
	import flash.utils.Timer;
	import flash.errors.IOError;
	import flash.events.*;

	public class JplayerMp3 extends Sprite {
		private var mySound:Sound = new Sound();
		private var myChannel:SoundChannel = new SoundChannel();
		private var myContext:SoundLoaderContext = new SoundLoaderContext(3000, false);
		private var myTransform:SoundTransform = new SoundTransform();
		private var myRequest:URLRequest = new URLRequest();

		private var timeUpdateTimer:Timer = new Timer(250, 0); // Matched to HTML event freq
		private var progressTimer:Timer = new Timer(250, 0); // Matched to HTML event freq
		private var seekingTimer:Timer = new Timer(100, 0); // Internal: How often seeking is checked to see if it is over.
		
		public var myStatus:JplayerStatus = new JplayerStatus();

		public function JplayerMp3(volume:Number) {
			timeUpdateTimer.addEventListener(TimerEvent.TIMER, timeUpdateHandler);
			progressTimer.addEventListener(TimerEvent.TIMER, progressHandler);
			seekingTimer.addEventListener(TimerEvent.TIMER, seekingHandler);
			setVolume(volume);
		}
		public function setFile(src:String):void {
			this.dispatchEvent(new JplayerEvent(JplayerEvent.DEBUG_MSG, myStatus, "setFile: " + src));
			if(myStatus.isPlaying) {
				myChannel.stop();
				progressUpdates(false);
				timeUpdates(false);
			}
			try {
				mySound.close();
			} catch (err:IOError) {
				// Occurs if the file is either yet to be opened or has finished downloading.
			}
			mySound = null;
			mySound = new Sound();
			mySound.addEventListener(IOErrorEvent.IO_ERROR, errorHandler);
			mySound.addEventListener(Event.OPEN, loadOpen);
			mySound.addEventListener(Event.COMPLETE, loadComplete);
			myRequest = new URLRequest(src);
			myStatus.reset();
			myStatus.src = src;
			myStatus.srcSet = true;
			timeUpdateEvent();
		}
		public function clearFile():void {
			setFile("");
			myStatus.srcSet = false;
		}
		private function errorHandler(err:IOErrorEvent):void {
			// MP3 player needs to stop progress and timeupdate events as they are started before the error occurs.
			// NB: The MP4 player works differently and the error occurs before they are started.
			progressUpdates(false);
			timeUpdates(false);
			myStatus.error(); // Resets status except the src, and it sets srcError property.
			this.dispatchEvent(new JplayerEvent(JplayerEvent.JPLAYER_ERROR, myStatus));
		}
		private function loadOpen(e:Event):void {
			this.dispatchEvent(new JplayerEvent(JplayerEvent.DEBUG_MSG, myStatus, "loadOpen:"));
			myStatus.loading();
			if(myStatus.playOnLoad) {
				myStatus.playOnLoad = false; // Capture the flag
				this.dispatchEvent(new JplayerEvent(JplayerEvent.JPLAYER_LOADSTART, myStatus)); // So loadstart event happens before play event occurs.
				play();
			} else {
				this.dispatchEvent(new JplayerEvent(JplayerEvent.JPLAYER_LOADSTART, myStatus));
				pause();
			}
			progressUpdates(true);
		}
		private function loadComplete(e:Event):void {
			this.dispatchEvent(new JplayerEvent(JplayerEvent.DEBUG_MSG, myStatus, "loadComplete:"));
			myStatus.loaded();
			progressUpdates(false);
			progressEvent();
		}
		private function soundCompleteHandler(e:Event):void {
			myStatus.pausePosition = 0;
			myStatus.isPlaying = false;
			timeUpdates(false);
			timeUpdateEvent();
			this.dispatchEvent(new JplayerEvent(JplayerEvent.JPLAYER_ENDED, myStatus));
		}
		private function progressUpdates(active:Boolean):void {
			// Using a timer rather than Flash's load progress event, because that event gave data at about 200Hz. The 10Hz timer is closer to HTML5 norm.
			if(active) {
				progressTimer.start();
			} else {
				progressTimer.stop();
			}
		}
		private function progressHandler(e:TimerEvent):void {
			progressEvent();
		}
		private function progressEvent():void {
			this.dispatchEvent(new JplayerEvent(JplayerEvent.DEBUG_MSG, myStatus, "progressEvent:"));
			updateStatusValues();
			this.dispatchEvent(new JplayerEvent(JplayerEvent.JPLAYER_PROGRESS, myStatus));
		}
		private function timeUpdates(active:Boolean):void {
			if(active) {
				timeUpdateTimer.start();
			} else {
				timeUpdateTimer.stop();
			}
		}
		private function timeUpdateHandler(e:TimerEvent):void {
			timeUpdateEvent();
		}
		private function timeUpdateEvent():void {
			updateStatusValues();
			this.dispatchEvent(new JplayerEvent(JplayerEvent.JPLAYER_TIMEUPDATE, myStatus));
		}
		private function seeking(active:Boolean):void {
			if(active) {
				if(!myStatus.isSeeking) {
					seekingEvent();
					seekingTimer.start();
				}
			} else {
				seekingTimer.stop();
			}
		}
		private function seekingHandler(e:TimerEvent):void {
			if(myStatus.pausePosition <= getDuration()) {
				seekedEvent();
				seeking(false);
				if(myStatus.playOnSeek) {
					myStatus.playOnSeek = false; // Capture the flag.
					play();
				}
			} else if(myStatus.isLoaded && (myStatus.pausePosition > getDuration())) {
				// Illegal seek time
				seeking(false);
				seekedEvent();
				pause(0);
			}
		}
		private function seekingEvent():void {
			myStatus.isSeeking = true;
			updateStatusValues();
			this.dispatchEvent(new JplayerEvent(JplayerEvent.JPLAYER_SEEKING, myStatus));
		}
		private function seekedEvent():void {
			myStatus.isSeeking = false;
			updateStatusValues();
			this.dispatchEvent(new JplayerEvent(JplayerEvent.JPLAYER_SEEKED, myStatus));
		}
		public function load():Boolean {
			if(myStatus.loadRequired()) {
				myStatus.startingDownload();
				mySound.load(myRequest, myContext);
				return true;
			} else {
				return false;
			}
		}
		public function play(time:Number = NaN):Boolean {
			var wasPlaying:Boolean = myStatus.isPlaying;

			if(!isNaN(time) && myStatus.srcSet) {
				if(myStatus.isPlaying) {
					myChannel.stop();
					myStatus.isPlaying = false;
				}
				myStatus.pausePosition = time;
			}

			if(myStatus.isStartingDownload) {
				myStatus.playOnLoad = true; // Raise flag, captured in loadOpen()
				return true;
			} else if(myStatus.loadRequired()) {
				myStatus.playOnLoad = true; // Raise flag, captured in loadOpen()
				return load();
			} else if((myStatus.isLoading || myStatus.isLoaded) && !myStatus.isPlaying) {
				if(myStatus.isLoaded && myStatus.pausePosition > getDuration()) { // The time is invalid, ie., past the end.
					myStatus.pausePosition = 0;
					timeUpdates(false);
					timeUpdateEvent();
					if(wasPlaying) { // For when playing and then get a play(huge)
						this.dispatchEvent(new JplayerEvent(JplayerEvent.JPLAYER_PAUSE, myStatus));
					}
				} else if(myStatus.pausePosition > getDuration()) {
					myStatus.playOnSeek = true;
					seeking(true);
				} else {
					myStatus.isPlaying = true; // Set immediately before playing. Could affects events.
					myChannel = mySound.play(myStatus.pausePosition);
					myChannel.soundTransform = myTransform;
					myChannel.addEventListener(Event.SOUND_COMPLETE, soundCompleteHandler);
					timeUpdates(true);
					if(!wasPlaying) {
						this.dispatchEvent(new JplayerEvent(JplayerEvent.JPLAYER_PLAY, myStatus));
					}
				}
				return true;
			} else {
				return false;
			}
		}
		public function pause(time:Number = NaN):Boolean {
			myStatus.playOnLoad = false; // Reset flag in case load/play issued immediately before this command, ie., before loadOpen() event.
			myStatus.playOnSeek = false; // Reset flag in case play(time) issued before the command and is still seeking to time set.

			var wasPlaying:Boolean = myStatus.isPlaying;

			// To avoid possible loops with timeupdate and pause(time). A pause() does not have the problem.
			var alreadyPausedAtTime:Boolean = false;
			if(!isNaN(time) && myStatus.pausePosition == time) {
				alreadyPausedAtTime = true;
			}

			if(myStatus.isPlaying) {
				myStatus.isPlaying = false;
				myChannel.stop();
				if(myChannel.position > 0) { // Required otherwise a fast play then pause causes myChannel.position to equal zero and not the correct value. ie., When it happens leave pausePosition alone.
					myStatus.pausePosition = myChannel.position;
				}
			}

			if(!isNaN(time) && myStatus.srcSet) {
				myStatus.pausePosition = time;
			}

			if(wasPlaying) {
				this.dispatchEvent(new JplayerEvent(JplayerEvent.JPLAYER_PAUSE, myStatus));
			}
			
			if(myStatus.isStartingDownload) {
				return true;
			} else if(myStatus.loadRequired()) {
				if(time > 0) { // We do not want the stop() command, which does pause(0), causing a load operation.
					return load();
				} else {
					return true; // Technically the pause(0) succeeded. ie., It did nothing, since nothing was required.
				}
			} else if(myStatus.isLoading || myStatus.isLoaded) {
				if(myStatus.isLoaded && myStatus.pausePosition > getDuration()) { // The time is invalid, ie., past the end.
					myStatus.pausePosition = 0;
				} else if(myStatus.pausePosition > getDuration()) {
					seeking(true);
				}
				timeUpdates(false);
				// Need to be careful with timeupdate event, otherwise a pause in a timeupdate event can cause a loop.
				// Neither pause() nor pause(time) will cause a timeupdate loop.
				if(wasPlaying || !isNaN(time) && !alreadyPausedAtTime) {
					timeUpdateEvent();
				}
				return true;
			} else {
				return false;
			}
		}
		public function playHead(percent:Number):Boolean {
			var time:Number = percent * getDuration() / 100;
			if(myStatus.isPlaying || myStatus.playOnLoad || myStatus.playOnSeek) {
				return play(time);
			} else {
				return pause(time);
			}
		}
		public function setVolume(v:Number):void {
			myStatus.volume = v;
			myTransform.volume = v;
			myChannel.soundTransform = myTransform;
		}
		private function updateStatusValues():void {
			myStatus.seekPercent = 100 * getLoadRatio();
			myStatus.currentTime = getCurrentTime();
			myStatus.currentPercentRelative = 100 * getCurrentRatioRel();
			myStatus.currentPercentAbsolute = 100 * getCurrentRatioAbs();
			myStatus.duration = getDuration();
		}
		public function getLoadRatio():Number {
			if((myStatus.isLoading || myStatus.isLoaded) && mySound.bytesTotal > 0) {
				return mySound.bytesLoaded / mySound.bytesTotal;
			} else {
				return 0;
			}
		}
		public function getDuration():Number {
			if(mySound.length > 0) {
				return mySound.length;
			} else {
				return 0;
			}
		}
		public function getCurrentTime():Number {
			if(myStatus.isPlaying) {
				return myChannel.position;
			} else {
				return myStatus.pausePosition;
			}
		}
		public function getCurrentRatioRel():Number {
			if((getDuration() > 0) && (getCurrentTime() <= getDuration())) {
				return getCurrentTime() / getDuration();
			} else {
				return 0;
			}
		}
		public function getCurrentRatioAbs():Number {
			return getCurrentRatioRel() * getLoadRatio();
		}
	}
}
