package blackberry.custom.filereader {
	import flash.filesystem.File;
	import flash.filesystem.FileMode;
	import flash.filesystem.FileStream;
	import flash.utils.ByteArray;
	import mx.utils.Base64Encoder;
	import webworks.extension.DefaultExtension;
	
	public class CustomFileReader extends DefaultExtension {

		public function CustomFileReader() {
			super();
		}

		override public function getFeatureList():Array {
			return new Array ("blackberry.custom.filereader");
		}

		public function readAsDataURL(path:String):String {
			var file:File = new File(path);
			if (!file.exists) {
				return "";
			}
			var bytes:ByteArray = new ByteArray();
			var stream:FileStream = new FileStream();
			stream.open(file, FileMode.READ);
			stream.readBytes(bytes);
			var btoa:Base64Encoder = new Base64Encoder();
			btoa.encodeBytes(bytes);
			stream.close();			
			return "data:;base64," + btoa.toString();
		}
	}
}