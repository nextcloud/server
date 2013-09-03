$(document).on('ajaxSend',function(elm, xhr, s) {
	xhr.setRequestHeader('requesttoken', oc_requesttoken);
});

