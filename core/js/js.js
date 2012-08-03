/**
 * translate a string
 * @param app the id of the app for which to translate the string
 * @param text the string to translate
 * @return string
 */
function t(app,text){
	if( !( app in t.cache )){
		$.ajax(OC.filePath('core','ajax','translations.php'),{
			async:false,//todo a proper sollution for this without sync ajax calls
			data:{'app': app},
			type:'POST',
			success:function(jsondata){
				t.cache[app] = jsondata.data;
			},
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
	appswebroot:oc_appswebroot,
	currentUser:(typeof oc_current_user!=='undefined')?oc_current_user:false,
	coreApps:['', 'admin','log','search','settings','core','3rdparty'],
	/**
	 * get an absolute url to a file in an appen
	 * @param app the id of the app the file belongs to
	 * @param file the file path relative to the app folder
	 * @return string
	 */
	linkTo:function(app,file){
		return OC.filePath(app,'',file);
	},
	/**
	 * get the absolute url for a file in an app
	 * @param app the id of the app
	 * @param type the type of the file to link to (e.g. css,img,ajax.template)
	 * @param file the filename
	 * @return string
	 */
	filePath:function(app,type,file){
		var isCore=OC.coreApps.indexOf(app)!=-1;
		var link=OC.webroot;
		if((file.substring(file.length-3) == 'php' || file.substring(file.length-3) == 'css') && !isCore){
			link+='/?app=' + app;
			if (file != 'index.php') {
				link+='&getfile=';
				if(type){
					link+=encodeURI(type + '/');
				}
				link+= file;
			}
		}else if(file.substring(file.length-3) != 'php' && !isCore){
			link=OC.appswebroot;
			link+='/';
			link+='apps/';
			link+=app+'/';
			if(type){
				link+=type+'/';
			}
			link+=file;
		}else{
			link+='/';
			if(!isCore){
				link+='apps/';
			}
			if (app != '') {
				app+='/';
				link+=app;
			}
			if(type){
				link+=type+'/';
			}
			link+=file;	
		}
		return link;
	},
	/**
	 * get the absolute path to an image file
	 * @param app the app id to which the image belongs
	 * @param file the name of the image file
	 * @return string
	 * 
	 * if no extension is given for the image, it will automatically decide between .png and .svg based on what the browser supports
	 */ 
	imagePath:function(app,file){
		if(file.indexOf('.')==-1){//if no extension is given, use png or svg depending on browser support
			file+=(SVGSupport())?'.svg':'.png';
		}
		return OC.filePath(app,'img',file);
	},
	/**
	 * load a script for the server and load it
	 * @param app the app id to which the script belongs
	 * @param script the filename of the script
	 * @param ready event handeler to be called when the script is loaded
	 * 
	 * if the script is already loaded, the event handeler will be called directly
	 */
	addScript:function(app,script,ready){
		var path=OC.filePath(app,'js',script+'.js');
		if(OC.addScript.loaded.indexOf(path)==-1){
			OC.addScript.loaded.push(path);
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
	/**
	 * load a css file and load it
	 * @param app the app id to which the css style belongs
	 * @param style the filename of the css file
	 */
	addStyle:function(app,style){
		var path=OC.filePath(app,'css',style+'.css');
		if(OC.addStyle.loaded.indexOf(path)==-1){
			OC.addStyle.loaded.push(path);
			var style=$('<link rel="stylesheet" type="text/css" href="'+path+'"/>');
			$('head').append(style);
		}
	},
	/**
	 * do a search query and display the results
	 * @param query the search query
	 */
	search:function(query){
		if(query){
			OC.addStyle('search','results');
			$.getJSON(OC.filePath('search','ajax','search.php')+'?query='+encodeURIComponent(query), function(results){
				OC.search.lastResults=results;
				OC.search.showResults(results);
			});
		}
	},
	dialogs:OCdialogs,
  mtime2date:function(mtime) {
    mtime = parseInt(mtime);
    var date = new Date(1000*mtime);
    var ret = date.getDate()+'.'+(date.getMonth()+1)+'.'+date.getFullYear()+', '+date.getHours()+':'+date.getMinutes();
    return ret;
  }
};
OC.search.customResults={};
OC.search.currentResult=-1;
OC.search.lastQuery='';
OC.search.lastResults={};
OC.addStyle.loaded=[];
OC.addScript.loaded=[];

if(typeof localStorage !='undefined' && localStorage != null){
	//user and instance awere localstorage
	OC.localStorage={
		namespace:'oc_'+OC.currentUser+'_'+OC.webroot+'_',
		hasItem:function(name){
			return OC.localStorage.getItem(name)!=null;
		},
		setItem:function(name,item){
			return localStorage.setItem(OC.localStorage.namespace+name,JSON.stringify(item));
		},
		getItem:function(name){
			if(localStorage.getItem(OC.localStorage.namespace+name)==null){return null;}
			return JSON.parse(localStorage.getItem(OC.localStorage.namespace+name));
		}
	};
}else{
	//dummy localstorage
	OC.localStorage={
		hasItem:function(name){
			return false;
		},
		setItem:function(name,item){
			return false;
		},
		getItem:function(name){
			return null;
		}
	};
}

/**
 * implement Array.filter for browsers without native support
 */
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
/**
 * implement Array.indexOf for browsers without native support
 */
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

/**
 * check if the browser support svg images
 */
function SVGSupport() {
	return SVGSupport.checkMimeType.correct && !!document.createElementNS && !!document.createElementNS('http://www.w3.org/2000/svg', "svg").createSVGRect;
}
SVGSupport.checkMimeType=function(){
	$.ajax({
		url: OC.imagePath('core','breadcrumb.svg'),
		success:function(data,text,xhr){
			var headerParts=xhr.getAllResponseHeaders().split("\n");
			var headers={};
			$.each(headerParts,function(i,text){
				if(text){
					var parts=text.split(':',2);
					if(parts.length==2){
						var value=parts[1].trim();
						if(value[0]=='"'){
							value=value.substr(1,value.length-2);
						}
						headers[parts[0]]=value;
					}
				}
			});
			if(headers["Content-Type"]!='image/svg+xml'){
				replaceSVG();
				SVGSupport.checkMimeType.correct=false;
			}
		}
	});
};
SVGSupport.checkMimeType.correct=true;

//replace all svg images with png for browser compatibility
function replaceSVG(){
	$('img.svg').each(function(index,element){
		element=$(element);
		var src=element.attr('src');
		element.attr('src',src.substr(0,src.length-3)+'png');
	});
	$('.svg').each(function(index,element){
		element=$(element);
		var background=element.css('background-image');
		if(background){
			var i=background.lastIndexOf('.svg');
			if(i>=0){
				background=background.substr(0,i)+'.png'+background.substr(i+4);
				element.css('background-image',background);
			}
		}
		element.find('*').each(function(index,element) {
			element=$(element);
			var background=element.css('background-image');
			if(background){
				var i=background.lastIndexOf('.svg');
				if(i>=0){
					background=background.substr(0,i)+'.png'+background.substr(i+4);
					element.css('background-image',background);
				}
			}
		});
	});
}

/**
 * prototypal inharitence functions
 * 
 * usage:
 * MySubObject=object(MyObject)
 */
function object(o) {
	function F() {}
    F.prototype = o;
	return new F();
}


/**
 * Fills height of window. (more precise than height: 100%;)
 */
function fillHeight(selector) {
	if (selector.length == 0) {
		return;
	}
	var height = parseFloat($(window).height())-selector.offset().top;
	selector.css('height', height + 'px');
	if(selector.outerHeight() > selector.height())
		selector.css('height', height-(selector.outerHeight()-selector.height()) + 'px');
}

/**
 * Fills height and width of window. (more precise than height: 100%; or width: 100%;)
 */
function fillWindow(selector) {
	if (selector.length == 0) {
		return;
	}
	fillHeight(selector);
	var width = parseFloat($(window).width())-selector.offset().left;
	selector.css('width', width + 'px');
	if(selector.outerWidth() > selector.width())
		selector.css('width', width-(selector.outerWidth()-selector.width()) + 'px');
}

$(document).ready(function(){

	$(window).resize(function () {
		fillHeight($('#leftcontent'));
		fillWindow($('#content'));
		fillWindow($('#rightcontent'));
	});
	$(window).trigger('resize');
	
	if(!SVGSupport()){ //replace all svg images with png images for browser that dont support svg
		replaceSVG();
	}else{
		SVGSupport.checkMimeType();
	}
	$('form.searchbox').submit(function(event){
		event.preventDefault();
	});
	$('#searchbox').keyup(function(event){
		if(event.keyCode==13){//enter
			if(OC.search.currentResult>-1){
				var result=$('#searchresults tr.result a')[OC.search.currentResult];
				window.location = $(result).attr('href');
			}
		}else if(event.keyCode==38){//up
			if(OC.search.currentResult>0){
				OC.search.currentResult--;
				OC.search.renderCurrent();
			}
		}else if(event.keyCode==40){//down
			if(OC.search.lastResults.length>OC.search.currentResult+1){
				OC.search.currentResult++;
				OC.search.renderCurrent();
			}
		}else if(event.keyCode==27){//esc
			OC.search.hide();
		}else{
			var query=$('#searchbox').val();
			if(OC.search.lastQuery!=query){
				OC.search.lastQuery=query;
				OC.search.currentResult=-1;
				if(query.length>2){
					OC.search(query);
				}else{
					if(OC.search.hide){
						OC.search.hide();
					}
				}
			}
		}
	});

	// 'show password' checkbox	
	$('#pass2').showPassword();

	//use infield labels
	$("label.infield").inFieldLabels();

	checkShowCredentials = function() {
		var empty = false;
		$('input#user, input#password').each(function() {
			if ($(this).val() == '') {
				empty = true;
			}
		});
		if(empty) {
			$('#submit').fadeOut();
			$('#remember_login').hide();
			$('#remember_login+label').fadeOut();
		} else {
			$('#submit').fadeIn();
			$('#remember_login').show();
			$('#remember_login+label').fadeIn();
		}
	}
	// hide log in button etc. when form fields not filled
	checkShowCredentials();
	$('input#user, input#password').keyup(checkShowCredentials);

	$('#settings #expand').keydown(function(event) {
		if (event.which == 13 || event.which == 32) {
			$('#expand').click()
		}
	});
	$('#settings #expand').click(function(event) {
		$('#settings #expanddiv').slideToggle();
		event.stopPropagation();
	});
	$('#settings #expanddiv').click(function(event){
		event.stopPropagation();
	});
	$(window).click(function(){//hide the settings menu when clicking oustide it
		if($('body').attr("id")=="body-user"){
			$('#settings #expanddiv').slideUp();
		}
	});

	// all the tipsy stuff needs to be here (in reverse order) to work
	$('.jp-controls .jp-previous').tipsy({gravity:'nw', fade:true, live:true});
	$('.jp-controls .jp-next').tipsy({gravity:'n', fade:true, live:true});
	$('.password .action').tipsy({gravity:'se', fade:true, live:true});
	$('.file_upload_button_wrapper').tipsy({gravity:'w', fade:true});
	$('.selectedActions a').tipsy({gravity:'s', fade:true, live:true});
	$('a.delete').tipsy({gravity: 'e', fade:true, live:true});
	$('a.action').tipsy({gravity:'s', fade:true, live:true});
	$('#headerSize').tipsy({gravity:'s', fade:true, live:true});
	$('td.filesize').tipsy({gravity:'s', fade:true, live:true});
	$('td .modified').tipsy({gravity:'s', fade:true, live:true});

	$('input').tipsy({gravity:'w', fade:true});
	$('input[type=text]').focus(function(){
		this.select();
	});
});

if (!Array.prototype.map){
	Array.prototype.map = function(fun /*, thisp */){
		"use strict";
		
		if (this === void 0 || this === null)
			throw new TypeError();
		
		var t = Object(this);
		var len = t.length >>> 0;
		if (typeof fun !== "function")
			throw new TypeError();
		
		var res = new Array(len);
		var thisp = arguments[1];
		for (var i = 0; i < len; i++){
			if (i in t){
				res[i] = fun.call(thisp, t[i], i, t);
			}
		}
		
	    return res;
	};
}

/**
 * Filter Jquery selector by attribute value
 **/
$.fn.filterAttr = function(attr_name, attr_value) {  
   return this.filter(function() { return $(this).attr(attr_name) === attr_value; });
};

function humanFileSize(size) {
	humanList = ['B', 'kB', 'MB', 'GB', 'TB'];
	// Calculate Log with base 1024: size = 1024 ** order
	order = Math.floor(Math.log(size) / Math.log(1024));
	// Stay in range of the byte sizes that are defined
	order = Math.min(humanList.length - 1, order);
	readableFormat = humanList[order];
	relativeSize = (size / Math.pow(1024, order)).toFixed(1);
	if(relativeSize.substr(relativeSize.length-2,2)=='.0'){
		relativeSize=relativeSize.substr(0,relativeSize.length-2);
	}
	return relativeSize + ' ' + readableFormat;
}

function simpleFileSize(bytes) {
	mbytes = Math.round(bytes/(1024*1024/10))/10;
	if(bytes == 0) { return '0'; }
	else if(mbytes < 0.1) { return '< 0.1'; }
	else if(mbytes > 1000) { return '> 1000'; }
	else { return mbytes.toFixed(1); }
}

function formatDate(date){
	if(typeof date=='number'){
		date=new Date(date);
	}
	var monthNames = [ t('files','January'), t('files','February'), t('files','March'), t('files','April'), t('files','May'), t('files','June'),
	t('files','July'), t('files','August'), t('files','September'), t('files','October'), t('files','November'), t('files','December') ];
	return monthNames[date.getMonth()]+' '+date.getDate()+', '+date.getFullYear()+', '+((date.getHours()<10)?'0':'')+date.getHours()+':'+((date.getMinutes()<10)?'0':'')+date.getMinutes();
}
