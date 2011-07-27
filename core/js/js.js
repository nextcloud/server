function t(app,text){
	if( !( app in t.cache )){
		
		$.post( OC.filePath('core','ajax','translations.php'), {'app': app}, function(jsondata){
			t.cache[app] = jsondata.data;
		});

		// Bad answer ...
		if( !( app in t.cache )){
			t.cache[app] = [];
		}
	}
	if( typeof( t.cache[app][text] ) !== 'undefined' ){
		return t.cache[app][text];
	}
	else{
		return text;
	}
}
t.cache={};

OC={
	webroot:oc_webroot,
	coreApps:['files','admin','log','search','settings','core'],
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

if (!Array.prototype.indexOf){
	Array.prototype.indexOf = function(elt /*, from*/)
	{
		var len = this.length;
		
		var from = Number(arguments[1]) || 0;
		from = (from < 0)
		? Math.ceil(from)
		: Math.floor(from);
		if (from < 0)
			from += len;
		
		for (; from < len; from++)
		{
			if (from in this &&
				this[from] === elt)
				return from;
		}
		return -1;
	};
}