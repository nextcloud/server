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
		if(file.indexOf('.')==-1){//if no extention is given, use png or svg depending on browser support
			file+=(SVGSupport())?'.svg':'.png'
		}
		return OC.filePath(app,'img',file);
	},
	addScript:function(app,script,ready){
		var path=OC.filePath(app,'js',script+'.js');
		if(OC.addStyle.loaded.indexOf(path)==-1){
			OC.addStyle.loaded.push(path);
			if(ready){
				$.getScript(path,ready);
			}else{
				$.getScript(path);
			}
		}else{
			if(ready){
				ready();
			}
		}
	},
	addStyle:function(app,style){
		var path=OC.filePath(app,'css',style+'.css');
		if(OC.addScript.loaded.indexOf(path)==-1){
			OC.addScript.loaded.push(path);
			var style=$('<link rel="stylesheet" type="text/css" href="'+path+'"/>');
			$('head').append(style);
		}
	},
	search:function(query){
		if(query){
			OC.addScript('search','result',function(){
				OC.addStyle('search','results');
				$.getJSON(OC.filePath('search','ajax','search.php')+'?query='+encodeURIComponent(query), OC.search.showResults);
			});
		}
	}
}
OC.search.customResults={};
OC.addStyle.loaded=[];
OC.addScript.loaded=[];

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

function SVGSupport() {
	return document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#BasicStructure", "1.1") || document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#Shape", "1.0");
}

$(document).ready(function(){
	if(!SVGSupport()){//replace all svg images with png images for browser that dont support svg
		$('img.svg').each(function(index,element){
			element=$(element);
			var src=element.attr('src');
			element.attr('src',src.substr(0,src.length-3)+'png');
		});
	};
	$('#searchbox').keyup(function(){
		var query=$('#searchbox').val();
		if(query.length>2){
			OC.search(query);
		}else{
			if(OC.search.hide){
				OC.search.hide();
			}
		}
	});
	$('#searchbox').click(function(){$('#searchbox').trigger('keyup')});
});
