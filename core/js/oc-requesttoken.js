$(document).on('ajaxSend',function(elm, xhr) {
	xhr.setRequestHeader('requesttoken', oc_requesttoken);
	xhr.setRequestHeader('OCS-APIREQUEST', 'true');
});
