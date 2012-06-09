package {
	import flash.display.Sprite;
	import flash.display.LoaderInfo;
	import flash.external.ExternalInterface;
	public class PUT extends Sprite {
		public function PUT() {
			ExternalInterface.addCallback("put", this.put);
			var callback:String = LoaderInfo(root.loaderInfo).parameters["readyCallback"];
			ExternalInterface.call(callback);
		}
		public function put(host:String, port:uint, path:String,
				data:String, callbackFunctionName:String,
                                callbackId:String):void {
			new Request(host, port, path, data,
					callbackFunctionName, callbackId);
		}
	}
}
