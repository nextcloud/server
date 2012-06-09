(function () {
	var CUSTOM_FILEREADER_API_URL = "blackberry/custom/filereader";

	var ARGS_PATH = "path";
	var ARGS_DATA = "data";

	function CustomFileReader() {
	};

	CustomFileReader.prototype.readAsDataURL = function(path) {
		var remoteCall = new blackberry.transport.RemoteFunctionCall(CUSTOM_FILEREADER_API_URL + "/readAsDataURL");
		remoteCall.addParam(ARGS_PATH, path);
		return remoteCall.makeSyncCall();
	};

	blackberry.Loader.javascriptLoaded("blackberry.custom.filereader", CustomFileReader);
})();
