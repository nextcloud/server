package {
	import flash.events.*;
	import flash.external.ExternalInterface;
	import flash.net.Socket;
	import flash.utils.ByteArray;
	import mx.utils.Base64Decoder;
	import mx.utils.Base64Encoder;
	internal class Request {
		private var id:String;
		private var header:String;
		private var data:ByteArray;
		private var callbackName:String;
		private var socket:Socket;
		public function Request(host:String, port:uint, path:String,
                                data:String, callbackName:String,
                                callbackId:String) {
			var atob:Base64Decoder = new Base64Decoder();
			atob.decode(data);
			this.data = atob.toByteArray();
			this.header = 
				"PUT " + path + " HTTP/1.1\r\n" +
				"Host: " + host + "\r\n" +
				"Content-Length: " + this.data.length + "\r\n" +
				"\r\n";
			this.callbackName = callbackName;
			this.id = callbackId;
			this.socket = new Socket();
			this.socket.timeout = 1000;
			this.socket.addEventListener(Event.CONNECT,
					connectHandler);
			this.socket.addEventListener(IOErrorEvent.IO_ERROR,
					ioErrorHandler);
			this.socket.addEventListener(
					SecurityErrorEvent.SECURITY_ERROR,
					securityErrorHandler);
			this.socket.addEventListener(Event.CLOSE, closeHandler);
			this.socket.addEventListener(ProgressEvent.SOCKET_DATA,
					dataHandler); 
			this.socket.connect(host, port);
		}
		private function connectHandler(connect:Event):void {
			this.socket.writeBytes(toByteArray(this.header));
			this.socket.writeBytes(this.data);
			this.socket.flush();
			this.header = null;
			this.data.length = 0;
		}
		private function ioErrorHandler(ioError:IOErrorEvent):void {
			ExternalInterface.call(this.callbackName, this.id,
					null);
			this.socket.close();
		}
		private function securityErrorHandler(
				securityError:SecurityErrorEvent):void {
			ExternalInterface.call(this.callbackName, this.id,
					null);
			this.socket.close();
		}
		private function closeHandler(close:Event):void {
			ExternalInterface.call(this.callbackName, this.id,
					null);
		}
		private function dataHandler(data:ProgressEvent):void {
			// assume it went well, TODO: parse reply, handle error
			this.socket.readBytes(this.data);
			var btoa:Base64Encoder = new Base64Encoder();
			btoa.encodeBytes(this.data);
			ExternalInterface.call(this.callbackName, this.id,
					btoa.toString());
			this.data.length = 0;
			this.socket.close();
		}
		public function toByteArray(str:String):ByteArray {
			var length:uint = str.length;
			var i:uint = 0;
			var data:ByteArray = new ByteArray();
			for (i; i < length; i++) {
				data.writeByte(str.charCodeAt(i) & 0xff);
			}
			return data;
		}
	}
}
