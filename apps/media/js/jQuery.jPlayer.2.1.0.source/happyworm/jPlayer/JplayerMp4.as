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
 * Date: 7th August 2011
 */

package happyworm.jPlayer {
	import flash.display.Sprite;

	import flash.media.Video;
	import flash.media.SoundTransform;

	import flash.net.NetConnection;
	import flash.net.NetStream;

	import flash.utils.Timer;

	import flash.events.NetStatusEvent;
	import flash.events.SecurityErrorEvent;
	import flash.events.TimerEvent;

	public class JplayerMp4 extends Sprite {
		
		public var myVideo:Video = new Video();
		private var myConnection:NetConnection;
		private var myStream:NetStream;
		
		private var myTransform:SoundTransform = new SoundTransform();

		public var myStatus:JplayerStatus = new JplayerStatus();
		
		private var timeUpdateTimer:Timer = new Timer(250, 0); // Matched to HTML event freq
		private var progressTimer:Timer = new Timer(250, 0); // Matched to HTML event freq
		private var seekingTimer:Timer = new Timer(100, 0); // Internal: How often seeking is checked to see if it is over.

		public function JplayerMp4(volume:Number) {
			myConnection = new NetConnection();
			myConnection.addEventListener(NetStatusEvent.NET_STATUS, netStatusHandler);
			myConnection.addEventListener(SecurityErrorEvent.SECURITY_ERROR, securityErrorHandler);
			myVideo.smoothing = true;
			this.addChild(myVideo);
			
			timeUpdateTimer.addEventListener(TimerEvent.TIMER, timeUpdateHandler);
			progressTimer.addEventListener(TimerEvent.TIMER, progressHandler);
			seekingTimer.addEventListener(TimerEvent.TIMER, seekingHandler);

			myStatus.volume = volume;
		}
		private function progressUpdates(active:Boolean):void {
			if(active) {
				progressTimer.start();
			} else {
				progressTimer.stop();
			}
		}
		private function progressHandler(e:TimerEvent):void {
			if(myStatus.isLoading) {
				if(getLoadRatio() == 1) { // Close as can get to a loadComplete event since client.onPlayStatus only works with FMS
					this.dispatchEvent(new JplayerEvent(JplayerEvent.DEBUG_MSG, myStatus, "progressHandler: loadComplete"));
					myStatus.loaded();
					progressUpdates(false);
				}
			}
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
				}
				seekingTimer.start();
			} else {
				if(myStatus.isSeeking) {
					seekedEvent();
				}
				seekingTimer.stop();
			}
		}
		private function seekingHandler(e:TimerEvent):void {
			if(getSeekTimeRatio() <= getLoadRatio()) {
				seeking(false);
				if(myStatus.playOnSeek) {
					myStatus.playOnSeek = false; // Capture the flag.
					play(myStatus.pausePosition); // Must pass time or the seek time is never set.
				} else {
					pause(myStatus.pausePosition); // Must pass time or the stream.time is read.
				}
			} else if(myStatus.metaDataReady && myStatus.pausePosition > myStatus.duration) {
				// Illegal seek time
				seeking(false);
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
		private function netStatusHandler(e:NetStatusEvent):void {
			this.dispatchEvent(new JplayerEvent(JplayerEvent.DEBUG_MSG, myStatus, "netStatusHandler: '" + e.info.code + "'"));
			switch(e.info.code) {
				case "NetConnection.Connect.Success":
					connectStream();
					break;
				case "NetStream.Play.Start":
					// This event code occurs once, when the media is opened. Equiv to loadOpen() in mp3 player.
					myStatus.loading();
					this.dispatchEvent(new JplayerEvent(JplayerEvent.JPLAYER_LOADSTART, myStatus));
					progressUpdates(true);
					// See onMetaDataHandler() for other condition, since duration is vital.
					break;
				case "NetStream.Play.Stop":
					this.dispatchEvent(new JplayerEvent(JplayerEvent.DEBUG_MSG, myStatus, "NetStream.Play.Stop: getDuration() - getCurrentTime() = " + (getDuration() - getCurrentTime())));

					// Check if media is at the end (or close) otherwise this was due to download bandwidth stopping playback. ie., Download is not fast enough.
					if(Math.abs(getDuration() - getCurrentTime()) < 150) { // Testing found 150ms worked best for M4A files, where playHead(99.9) caused a stuck state due to firing with ~116ms left to play.
						endedEvent();
					}
					break;
				case "NetStream.Seek.InvalidTime":
					// Used for capturing invalid set times and clicks on the end of the progress bar.
					endedEvent();
					break;
				case "NetStream.Play.StreamNotFound":
					myStatus.error(); // Resets status except the src, and it sets srcError property.
					this.dispatchEvent(new JplayerEvent(JplayerEvent.JPLAYER_ERROR, myStatus));
					break;
			}
			// "NetStream.Seek.Notify" event code is not very useful. It occurs after every seek(t) command issued and does not appear to wait for the media to be ready.
		}
		private function endedEvent():void {
			var wasPlaying:Boolean = myStatus.isPlaying;
			pause(0);
			timeUpdates(false);
			timeUpdateEvent();
			if(wasPlaying) {
				this.dispatchEvent(new JplayerEvent(JplayerEvent.JPLAYER_ENDED, myStatus));
			}
		}
		private function securityErrorHandler(event:SecurityErrorEvent):void {
			this.dispatchEvent(new JplayerEvent(JplayerEvent.DEBUG_MSG, myStatus, "securityErrorHandler."));
		}
		private function connectStream():void {
			this.dispatchEvent(new JplayerEvent(JplayerEvent.DEBUG_MSG, myStatus, "connectStream."));
			var customClient:Object = new Object();
			customClient.onMetaData = onMetaDataHandler;
			// customClient.onPlayStatus = onPlayStatusHandler; // According to the forums and my tests, onPlayStatus only works with FMS (Flash Media Server).
			myStream = null;
			myStream = new NetStream(myConnection);
			myStream.addEventListener(NetStatusEvent.NET_STATUS, netStatusHandler);
			myStream.client = customClient;
			myVideo.attachNetStream(myStream);
			setVolume(myStatus.volume);
			myStream.play(myStatus.src);
		}
		public function setFile(src:String):void {
			if(myStream != null) {
				myStream.close();
			}
			myVideo.clear();
			progressUpdates(false);
			timeUpdates(false);

			myStatus.reset();
			myStatus.src = src;
			myStatus.srcSet = true;
			timeUpdateEvent();
		}
		public function clearFile():void {
			setFile("");
			myStatus.srcSet = false;
		}
		public function load():Boolean {
			if(myStatus.loadRequired()) {
				myStatus.startingDownload();
				myConnection.connect(null);
				return true;
			} else {
				return false;
			}
		}
		public function play(time:Number = NaN):Boolean {
			var wasPlaying:Boolean = myStatus.isPlaying;

			if(!isNaN(time) && myStatus.srcSet) {
				if(myStatus.isPlaying) {
					myStream.pause();
					myStatus.isPlaying = false;
				}
				myStatus.pausePosition = time;
			}

			if(myStatus.isStartingDownload) {
				myStatus.playOnLoad = true; // Raise flag, captured in onMetaDataHandler()
				return true;
			} else if(myStatus.loadRequired()) {
				myStatus.playOnLoad = true; // Raise flag, captured in onMetaDataHandler()
				return load();
			} else if((myStatus.isLoading || myStatus.isLoaded) && !myStatus.isPlaying) {
				if(myStatus.metaDataReady && myStatus.pausePosition > myStatus.duration) { // The time is invalid, ie., past the end.
					myStream.pause(); // Since it is playing by default at this point.
					myStatus.pausePosition = 0;
					myStream.seek(0);
					timeUpdates(false);
					timeUpdateEvent();
					if(wasPlaying) { // For when playing and then get a play(huge)
						this.dispatchEvent(new JplayerEvent(JplayerEvent.JPLAYER_PAUSE, myStatus));
					}
				} else if(getSeekTimeRatio() > getLoadRatio()) { // Use an estimate based on the downloaded amount
					myStatus.playOnSeek = true;
					seeking(true);
					myStream.pause(); // Since it is playing by default at this point.
				} else {
					if(!isNaN(time)) { // Avoid using seek() when it is already correct.
						myStream.seek(myStatus.pausePosition/1000); // Since time is in ms and seek() takes seconds
					}
					myStatus.isPlaying = true; // Set immediately before playing. Could affects events.
					myStream.resume();
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
			myStatus.playOnLoad = false; // Reset flag in case load/play issued immediately before this command, ie., before onMetadata() event.
			myStatus.playOnSeek = false; // Reset flag in case play(time) issued before the command and is still seeking to time set.

			var wasPlaying:Boolean = myStatus.isPlaying;

			// To avoid possible loops with timeupdate and pause(time). A pause() does not have the problem.
			var alreadyPausedAtTime:Boolean = false;
			if(!isNaN(time) && myStatus.pausePosition == time) {
				alreadyPausedAtTime = true;
			}

			// Need to wait for metadata to load before ever issuing a pause. The metadata handler will call this function if needed, when ready.
			if(myStream != null && myStatus.metaDataReady) { // myStream is a null until the 1st media is loaded. ie., The 1st ever setMedia being followed by a pause() or pause(t).
				myStream.pause();
			}
			if(myStatus.isPlaying) {
				myStatus.isPlaying = false;
				myStatus.pausePosition = myStream.time * 1000;
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
				if(myStatus.metaDataReady && myStatus.pausePosition > myStatus.duration) { // The time is invalid, ie., past the end.
					myStatus.pausePosition = 0;
					myStream.seek(0);
					seekedEvent(); // Deals with seeking effect when using setMedia() then pause(huge). NB: There is no preceeding seeking event.
				} else if(!isNaN(time)) {
					if(getSeekTimeRatio() > getLoadRatio()) { // Use an estimate based on the downloaded amount
						seeking(true);
					} else {
						if(myStatus.metaDataReady) { // Otherwise seek(0) will stop the metadata loading.
							myStream.seek(myStatus.pausePosition/1000);
						}
					}
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
			var time:Number = percent * getDuration() * getLoadRatio() / 100;
			if(myStatus.isPlaying || myStatus.playOnLoad || myStatus.playOnSeek) {
				return play(time);
			} else {
				return pause(time);
			}
		}
		public function setVolume(v:Number):void {
			myStatus.volume = v;
			myTransform.volume = v;
			if(myStream != null) {
				myStream.soundTransform = myTransform;
			}
		}
		private function updateStatusValues():void {
			myStatus.seekPercent = 100 * getLoadRatio();
			myStatus.currentTime = getCurrentTime();
			myStatus.currentPercentRelative = 100 * getCurrentRatioRel();
			myStatus.currentPercentAbsolute = 100 * getCurrentRatioAbs();
			myStatus.duration = getDuration();
		}
		public function getLoadRatio():Number {
			if((myStatus.isLoading || myStatus.isLoaded) && myStream.bytesTotal > 0) {
				return myStream.bytesLoaded / myStream.bytesTotal;
			} else {
				return 0;
			}
		}
		public function getDuration():Number {
			return myStatus.duration; // Set from meta data.
		}
		public function getCurrentTime():Number {
			if(myStatus.isPlaying) {
				return myStream.time * 1000;
			} else {
				return myStatus.pausePosition;
			}
		}
		public function getCurrentRatioRel():Number {
			if((getLoadRatio() > 0) && (getCurrentRatioAbs() <= getLoadRatio())) {
				return getCurrentRatioAbs() / getLoadRatio();
			} else {
				return 0;
			}
		}
		public function getCurrentRatioAbs():Number {
			if(getDuration() > 0) {
				return getCurrentTime() / getDuration();
			} else {
				return 0;
			}
		}
		public function getSeekTimeRatio():Number {
			if(getDuration() > 0) {
				return myStatus.pausePosition / getDuration();
			} else {
				return 1;
			}
		}
		public function onMetaDataHandler(info:Object):void { // Used in connectStream() in myStream.client object.
			// This event occurs when jumping to the start of static files! ie., seek(0) will cause this event to occur.
			if(!myStatus.metaDataReady) {
				this.dispatchEvent(new JplayerEvent(JplayerEvent.DEBUG_MSG, myStatus, "onMetaDataHandler: " + info.duration + " | " + info.width + "x" + info.height));

				myStatus.metaDataReady = true; // Set flag so that this event only effects jPlayer the 1st time.
				myStatus.metaData = info;
				myStatus.duration = info.duration * 1000; // Only available via Meta Data.
				if(info.width != undefined) {
					myVideo.width = info.width;
				}
				if(info.height != undefined) {
					myVideo.height = info.height;
				}

				if(myStatus.playOnLoad) {
					myStatus.playOnLoad = false; // Capture the flag
					if(myStatus.pausePosition > 0 ) { // Important for setMedia followed by play(time).
						play(myStatus.pausePosition);
					} else {
						play(); // Not always sending pausePosition avoids the extra seek(0) for a normal play() command.
					}
				} else {
					pause(myStatus.pausePosition); // Always send the pausePosition. Important for setMedia() followed by pause(time). Deals with not reading stream.time with setMedia() and play() immediately followed by stop() or pause(0)
				}
				this.dispatchEvent(new JplayerEvent(JplayerEvent.JPLAYER_LOADEDMETADATA, myStatus));
			} else {
				this.dispatchEvent(new JplayerEvent(JplayerEvent.DEBUG_MSG, myStatus, "onMetaDataHandler: Already read (NO EFFECT)"));
			}
		}
	}
}
