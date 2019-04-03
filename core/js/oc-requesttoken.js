$(document).on('ajaxSend',function(elm, xhr, settings) {
	if(settings.crossDomain === false) {
		xhr.setRequestHeader('requesttoken', oc_requesttoken);
		xhr.setRequestHeader('OCS-APIREQUEST', 'true');
	}
});

(function () {
	var originalFetch = window.fetch;
	window.fetch = function(input, init) {
		init = init || {};
		init.headers = init.headers || {};
		init.headers.requesttoken = oc_requesttoken;
		return originalFetch(input, init);
	}
})();
