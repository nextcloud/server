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
 * Date: 8th August 2011
 */

package happyworm.jPlayer {
	import flash.events.Event;
	
	public class JplayerEvent extends Event {
		
		// The event strings must match those in the JavaScript's $.jPlayer.event object

		public static const JPLAYER_READY:String = "jPlayer_ready";
		public static const JPLAYER_FLASHRESET:String = "jPlayer_flashreset"; // Handled in JavaScript
		public static const JPLAYER_RESIZE:String = "jPlayer_resize"; // Handled in JavaScript
		public static const JPLAYER_REPEAT:String = "jPlayer_repeat"; // Handled in JavaScript
		public static const JPLAYER_CLICK:String = "jPlayer_click";
		public static const JPLAYER_ERROR:String = "jPlayer_error";
		public static const JPLAYER_WARNING:String = "jPlayer_warning"; // Currently not used by the flash solution

		public static const JPLAYER_LOADSTART:String = "jPlayer_loadstart";
		public static const JPLAYER_PROGRESS:String = "jPlayer_progress";
		public static const JPLAYER_SUSPEND:String = "jPlayer_suspend"; // Not implemented
		public static const JPLAYER_ABORT:String = "jPlayer_abort"; // Not implemented
		public static const JPLAYER_EMPTIED:String = "jPlayer_emptied"; // Not implemented
		public static const JPLAYER_STALLED:String = "jPlayer_stalled"; // Not implemented
		public static const JPLAYER_PLAY:String = "jPlayer_play";
		public static const JPLAYER_PAUSE:String = "jPlayer_pause";
		public static const JPLAYER_LOADEDMETADATA:String = "jPlayer_loadedmetadata"; // MP3 has no equivilent
		public static const JPLAYER_LOADEDDATA:String = "jPlayer_loadeddata"; // Not implemented
		public static const JPLAYER_WAITING:String = "jPlayer_waiting"; // Not implemented
		public static const JPLAYER_PLAYING:String = "jPlayer_playing"; // Not implemented
		public static const JPLAYER_CANPLAY:String = "jPlayer_canplay"; // Not implemented
		public static const JPLAYER_CANPLAYTHROUGH:String = "jPlayer_canplaythrough"; // Not implemented
		public static const JPLAYER_SEEKING:String = "jPlayer_seeking";
		public static const JPLAYER_SEEKED:String = "jPlayer_seeked";
		public static const JPLAYER_TIMEUPDATE:String = "jPlayer_timeupdate";
		public static const JPLAYER_ENDED:String = "jPlayer_ended";
		public static const JPLAYER_RATECHANGE:String = "jPlayer_ratechange"; // Not implemented
		public static const JPLAYER_DURATIONCHANGE:String = "jPlayer_durationchange"; // Not implemented
		public static const JPLAYER_VOLUMECHANGE:String = "jPlayer_volumechange"; // See JavaScript

		// Events used internal to jPlayer's Flash.
		public static const DEBUG_MSG:String = "debug_msg";

		public var data:JplayerStatus;
		public var msg:String = ""

		public function JplayerEvent(type:String, data:JplayerStatus, msg:String = "", bubbles:Boolean = false, cancelable:Boolean = false) {
			super(type, bubbles, cancelable);
			this.data = data;
			this.msg = msg;
		}
		public override function clone():Event {
			return new JplayerEvent(type, data, msg, bubbles, cancelable);
		}
		public override function toString():String {
			return formatToString("JplayerEvent", "type", "bubbles", "cancelable", "eventPhase", "data", "msg");
		}
	}
}