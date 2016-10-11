$(document).on('ajaxSend',function(elm, xhr, settings) {
	if(settings.crossDomain === false) {
		xhr.setRequestHeader('requesttoken', oc_requesttoken);
		xhr.setRequestHeader('OCS-APIREQUEST', 'true');
	}
});
