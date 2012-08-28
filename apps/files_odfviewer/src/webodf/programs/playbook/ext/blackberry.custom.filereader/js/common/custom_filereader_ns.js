(function () {

	function CustomFileReader(disp) {
		this.constructor.prototype.readAsDataURL = function(path) { return disp.readAsDataURL(path); };
	};

	blackberry.Loader.javascriptLoaded("blackberry.custom.filereader", CustomFileReader);
})();
