var _l10ncache = {};
function t(app,text){
	if( !( app in _l10ncache )){
		$.post( oc_webroot+'/core/ajax/translations.php', {'app': app}, function(jsondata){
			_l10ncache[app] = jsondata.data;
		});

		// Bad answer ...
		if( !( app in _l10ncache )){
			_l10ncache[app] = [];
		}
	}
	if( typeof( _l10ncache[app][text] ) !== 'undefined' ){
		return _l10ncache[app][text];
	}
	else{
		return text;
	}
}

$(document).ready(function(){
	// Put fancy stuff in here
});
