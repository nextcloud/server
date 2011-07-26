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

OC={
	webroot:oc_webroot,
	coreApps:['files','admin','log','search','settings'],
	linkTo:function(app,file){
		return OC.filePath(app,'',file);
	},
	filePath:function(app,type,file){
		var isCore=OC.coreApps.indexOf(app)!=-1;
		app+='/';
		var link=OC.webroot+'/';
		if(!isCore){
			link+='apps/';
		}
		link+=app;
		if(type){
			link+=type+'/'
		}
		link+=file;
		return link;
	},
	imagePath:function(app,file){
		return OC.filePath(app,'img',file);
	},
	addScript:function(app,script,ready){
		var path=OC.filePath(app,'js',script+'.js');
		if(ready){
			$.getScript(path,ready);
		}else{
			$.getScript(path);
		}
	},
	addStyle:function(app,style){
		var path=OC.filePath(app,'css',style+'.css');
		var style=$('<link rel="stylesheet" type="text/css" href="'+path+'"/>');
		$('head').append(style);
	}
}

if (!Array.prototype.filter) {
	Array.prototype.filter = function(fun /*, thisp*/) {
		var len = this.length >>> 0;
		if (typeof fun != "function")
			throw new TypeError();
		
		var res = [];
		var thisp = arguments[1];
		for (var i = 0; i < len; i++) {
			if (i in this) {
				var val = this[i]; // in case fun mutates this
				if (fun.call(thisp, val, i, this))
					res.push(val);
			}
		}
		return res;
	};
}