/*
 * jPlayer Plugin for jQuery JavaScript Library
 * http://www.jplayer.org
 *
 * Copyright (c) 2009 - 2013 Happyworm Ltd
 * Dual licensed under the MIT and GPL licenses.
 *  - http://www.opensource.org/licenses/mit-license.php
 *  - http://www.gnu.org/copyleft/gpl.html
 *
 * Author: Robert M. Hall
 * Date: 7th August 2012
 * Custom NetConnection Manager for more robust RTMP support
 * Based in part on work by Will Law for the old Akamai NCManager.as
 * and some of Will's new work in the OVP base classes (Open Video Player)
 * as well as similar approaches by many other NetConnection managers
 *
 */
 
 /* 
 TODO LIST 08/18/2011:
 1. Wired up errors to dispatch events to Jplayer events to allow them to bubble up to JS
 2. Rework event dispatch to handoff netconnection instead of a passed in reference
 3. Allow a customizeable list of protocols and ports to be used instead of entire list
 4. Allow a specific port/protocol (1 connect type) to be used first, and then optionally fallback on a custom list or the default list
 5. Remove some traces and check a few other items below where I've made notes
 */

package happyworm.jPlayer {
	
	import flash.events.*;
	import flash.net.*;
	
	import flash.utils.Timer;
	import flash.utils.getTimer;
	import flash.utils.clearInterval;
	import flash.utils.setInterval;

	public class ConnectManager extends Object {
		
		private var protocols_arr:Array = new Array("rtmp","rtmpt","rtmpe","rtmpte","rtmps");
		private var ports_arr:Array = new Array("",":1935",":80",":443");
		private const protCount:Number = 5;
		private const portCount:Number = 4;
		
		private var _ncRef:Object;
		
		private var _aNC:Array;

		private var k_TIMEOUT:Number = 30000;
		private var k_startConns:Number;
		private var m_connList:Array = [];
		private var m_serverName:String;
		private var m_appName:String;
		private var m_streamName:String;
		private var m_connListCounter:Number;
		private var m_flashComConnectTimeOut:Number;
		private var m_validNetConnection:NetConnection;
		
		private var connectSuccess:Boolean=false;
		
		private var negotiating:Boolean=false;
		private var idleTimeOut:Boolean=false;

		public function ConnectManager() {
			trace ("ConnectManager Initialized Version: 1.00 DT");
			createPortsProtocolsArray();
		}
		
		private function createPortsProtocolsArray():void {
		var outerLoop:Number=0;
		var innerLoop:Number=0;
			for (outerLoop=0; outerLoop<protocols_arr.length; outerLoop++) {
				
				for (innerLoop=0; innerLoop<ports_arr.length; innerLoop++) {
					m_connList.push( { protocol: protocols_arr[outerLoop], port: ports_arr[innerLoop] } );
				}
				
			}		
		}
		
		public function negotiateConnect(ncRef:Object,p_serverName:String,p_appName:String):void
		{
			negotiating=true;
			_ncRef=ncRef;
			trace("*** SERVER NAME: "+p_serverName);
			trace("*** APP NAME: "+p_serverName);
			k_startConns = getTimer();
			m_serverName = p_serverName;
			m_appName = p_appName;
		
			// Set a timeout function, just in case we never connect successfully
			clearInterval(m_flashComConnectTimeOut);
			m_flashComConnectTimeOut = setInterval(onFlashComConnectTimeOut,k_TIMEOUT,k_TIMEOUT);
			
			// Createe a NetConnection for each of the protocols/ports listed in the m_connList list.
			// Connection attempts occur at intervals of 1.5 seconds. 
			// The first connection to succeed will be used, all the others will be closed.
			_aNC = new Array();
			for (var i:uint = 0; i < m_connList.length; i++)
			{
				_aNC[i] = new NetConnection();
				_aNC[i].addEventListener(NetStatusEvent.NET_STATUS,netStatus);
				_aNC[i].addEventListener(SecurityErrorEvent.SECURITY_ERROR,netSecurityError);
				_aNC[i].addEventListener(AsyncErrorEvent.ASYNC_ERROR,asyncError);      
				_aNC[i].client = new Object;
				_aNC[i].client.owner = this;
				_aNC[i].client.connIndex = i;
				_aNC[i].client.id = i;
				_aNC[i].client.pending = true;
				
				/* Revisit this chunk - not needed at the moment as NC is handed off and this 
				// is handled elsewhere
				// Need to put in some event dispatching as a more elegant solution and leave it here
				
				_aNC[i].client.onBWDone = function (p_bw, deltaDown, deltaTime, latency) {
					//this.owner.dispatchEvent ({type:"ncBandWidth", kbps:p_bw, latency:latency});
				};

				_aNC[i].client.onBWCheck = function (counter) {
					return ++counter;
				};

				_aNC[i].client.onStatus = function (info) {
					//
				};
				*/
				
			}
			m_connListCounter = 0;
			nextConnect ();
		}
		
		private function nextConnect():void
		{
			trace("*** Connection: "+ m_connListCounter + ": "+m_connList[m_connListCounter].protocol + "://" + m_serverName + m_connList[m_connListCounter].port + "/" + m_appName);

			try {
				_aNC[m_connListCounter].connect(m_connList[m_connListCounter].protocol + "://" + m_serverName + m_connList[m_connListCounter].port + "/" + m_appName);

			} catch (error:Error) {
				// statements
				trace("*** Caught an error condition: "+error);
				m_connListCounter = m_connList.length+1;
			}
			// statements
				clearInterval(_aNC["ncInt" + m_connListCounter]);

			if ((m_connListCounter < m_connList.length - 1))
			{
				m_connListCounter++;
				_aNC["ncInt" + m_connListCounter] = setInterval(nextConnect,1500);
			}

		}
		
		// Cleans up all connections if none have succeeded by the timeout interval
		private function onFlashComConnectTimeOut(timeout:Number):void
		{
			stopAll();
		}
		
		private function handleGoodConnect(_nc:NetConnection):void {
			negotiating=false;
			trace("Handing OFF NetConnection");
			clearInterval(m_flashComConnectTimeOut);
			_ncRef.connectStream();
			_ncRef.onBWDone(null,_nc);
			//dispatchEvent(event);
			// Need to enable and pass to Jplayer event system- revisit
			// right now handing back a hardcoded reference that is passed in
			// Should come up with a more loosely coupled way via event dispatch

		}
		
		public function getNegotiating():Boolean {
			return negotiating;
		}
		
		public function setNegotiating(bool:Boolean):void {
			negotiating=bool;
		}
		
		
		public function stopAll(bool:Boolean=false):void {
			
			//this.dispatchEvent ({type:"ncFailedToConnect", timeout:timeout});
			// Need to enable and pass to Jplayer event system- revisit
			// trace(_aNC+":"+m_flashComConnectTimeOut+":"+m_connList.length)
			if(_aNC!=null && !isNaN(m_flashComConnectTimeOut) ) {
				clearInterval(m_flashComConnectTimeOut);
			for (var i:uint = 0; i < m_connList.length; i++)
			{
				if (_aNC[i]!=null)
				{
					clearInterval(_aNC["ncInt" + i]);
					_aNC[i].close();
					if(bool==false) {
						_aNC[i].client = null;
					}
					_aNC[i] = null;
					delete _aNC[i];
				}
			}
			}
						
		}
		
		
		private function netStatus(event:NetStatusEvent):void {
			
			trace(event.info.code);
			if(event.info.description != undefined) {
				trace(event.info.description);
			}
			_aNC[event.target.client.id].client.pending = true;
			
				// this.owner.m_validNetConnection = this.client.owner[this.client.connIndex];
				// if (info.description == "[ License.Limit.Exceeded ]") {

				switch (event.info.code) {
					case "NetConnection.Connect.IdleTimeOut":
					trace("IDLE TIMEOUT OCCURRED!")
					negotiating=true;
					idleTimeOut=true;
					_ncRef.shutDownNcNs();
					break;
				case "NetConnection.Connect.Closed":
					if(!negotiating && !idleTimeOut) {
						idleTimeOut = false;
						_ncRef.doneYet();
					}
					break;
				case "NetConnection.Connect.InvalidApp":
				case "NetConnection.Connect.Rejected":
					//handleRejectedOrInvalid(event) 
    				break;
				case "NetConnection.Call.Failed":
					/*
					if (event.info.description.indexOf("_checkbw") != -1) {
						event.target.expectBWDone = true;
						event.target.call("checkBandwidth",null);
					}
					*/
					break;
					case "NetConnection.Connect.Success":
						var i:uint=0;
						for ( i = 0; i<_aNC.length; i++) {
						if (_aNC[i] && (i != event.target.client.id)) {
							_aNC[i].close();
							_aNC[i] = null;
						}
					}
					var _nc:NetConnection = NetConnection(event.target);
					var connID:Number = event.target.client.id;
					var _actualPort:String = m_connList[m_connListCounter].port;
					var _actualProtocol:String = m_connList[m_connListCounter].protocol;
															
					// See if we have version info
					var _serverVersion:String = "UNKNOWN";
					if (event.info.data && event.info.data.version) {
						_serverVersion = event.info.data.version;
					}
					trace("Connect ID: "+connID+" - PORT: "+_actualPort+" - PROTOCOL: "+_actualProtocol+" - FMS Version: "+_serverVersion);
					
					clearInterval(_aNC["ncInt" + connID]);
					clearInterval(_aNC["ncInt" + m_connListCounter]);

					handleGoodConnect(_nc);
					break;
				}
		}
						

		/** Catches any netconnection net security errors
		 * @private
		 */
		private function netSecurityError(event:SecurityErrorEvent):void {
			trace("SECURITY ERROR:"+event);
			//dispatchEvent(event);
			// Need to enable and pass to Jplayer event system- revisit
    	}
    	
    	/** Catches any async errors
    	 * @private
    	 */
		private function asyncError(event:AsyncErrorEvent):void {
			trace("ASYNC ERROR:"+event.error);
			//dispatchEvent(event);
			// Need to enable and pass to Jplayer event system- revisit
    	}
		
		

	}// class
	
} //package
