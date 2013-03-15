/**
 * Disable console output unless DEBUG mode is enabled.
 * Add 
 *	 define('DEBUG', true);
 * To the end of config/config.php to enable debug mode.
 * The undefined checks fix the broken ie8 console
 */
var oc_debug;
var oc_webroot;
var oc_requesttoken;
if (typeof oc_webroot === "undefined") {
	oc_webroot = location.pathname.substr(0, location.pathname.lastIndexOf('/'));
}
if (oc_debug !== true || typeof console === "undefined" || typeof console.log === "undefined") {
	if (!window.console) {
		window.console = {};
	}
	var methods = ['log', 'debug', 'warn', 'info', 'error', 'assert'];
	for (var i = 0; i < methods.length; i++) {
		console[methods[i]] = function () { };
	}
}

/**
 * translate a string
 * @param app the id of the app for which to translate the string
 * @param text the string to translate
 * @return string
 */
function t(app,text, vars){
	if( !( t.cache[app] )){
		$.ajax(OC.filePath('core','ajax','translations.php'),{
			async:false,//todo a proper sollution for this without sync ajax calls
			data:{'app': app},
			type:'POST',
			success:function(jsondata){
				t.cache[app] = jsondata.data;
			}
		});

		// Bad answer ...
		if( !( t.cache[app] )){
			t.cache[app] = [];
		}
	}
	var _build = function (text, vars) {
		return text.replace(/{([^{}]*)}/g,
			function (a, b) {
				var r = vars[b];
				return typeof r === 'string' || typeof r === 'number' ? r : a;
			}
		);
	};
	if( typeof( t.cache[app][text] ) !== 'undefined' ){
		if(typeof vars === 'object') {
			return _build(t.cache[app][text], vars);
		} else {
			return t.cache[app][text];
		}
	}
	else{
		if(typeof vars === 'object') {
			return _build(text, vars);
		} else {
			return text;
		}
	}
}
t.cache={};

/*
* Sanitizes a HTML string
* @param string
* @return Sanitized string
*/
function escapeHTML(s) {
		return s.toString().split('&').join('&amp;').split('<').join('&lt;').split('"').join('&quot;');
}

/**
* Get the path to download a file
* @param file The filename
* @param dir The directory the file is in - e.g. $('#dir').val()
* @return string
*/
function fileDownloadPath(dir, file) {
	return OC.filePath('files', 'ajax', 'download.php')+'?files='+encodeURIComponent(file)+'&dir='+encodeURIComponent(dir);
}

var OC={
	PERMISSION_CREATE:4,
	PERMISSION_READ:1,
	PERMISSION_UPDATE:2,
	PERMISSION_DELETE:8,
	PERMISSION_SHARE:16,
	webroot:oc_webroot,
	appswebroots:(typeof oc_appswebroots !== 'undefined') ? oc_appswebroots:false,
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
	 * Creates an url for remote use
	 * @param string $service id
	 * @return string the url
	 *
	 * Returns a url to the given service.
	 */
	linkToRemoteBase:function(service) {
		return OC.webroot + '/remote.php/' + service;
	},
	/**
	 * @brief Creates an absolute url for remote use
	 * @param string $service id
	 * @param bool $add_slash
	 * @return string the url
	 *
	 * Returns a absolute url to the given service.
	 */
	linkToRemote:function(service) {
		return window.location.protocol + '//' + window.location.host + OC.linkToRemoteBase(service);
	},
	/**
	 * get the absolute url for a file in an app
	 * @param app the id of the app
	 * @param type the type of the file to link to (e.g. css,img,ajax.template)
	 * @param file the filename
	 * @return string
	 */
	filePath:function(app,type,file){
		var isCore=OC.coreApps.indexOf(app)!==-1,
			link=OC.webroot;
		if((file.substring(file.length-3) === 'php' || file.substring(file.length-3) === 'css') && !isCore){
			link+='/index.php/apps/' + app;
			if (file != 'index.php') {
				link+='/';
				if(type){
					link+=encodeURI(type + '/');
				}
				link+= file;
			}
		}else if(file.substring(file.length-3) !== 'php' && !isCore){
			link=OC.appswebroots[app];
			if(type){
				link+= '/'+type+'/';
			}
			if(link.substring(link.length-1) !== '/'){
				link+='/';
			}
			link+=file;
		}else{
			if ((app == 'settings' || app == 'core' || app == 'search') && type == 'ajax') {
				link+='/index.php/';
			}
			else {
				link+='/';
			}
			if(!isCore){
				link+='apps/';
			}
			if (app !== '') {
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
		var deferred, path=OC.filePath(app,'js',script+'.js');
		if(!OC.addScript.loaded[path]){
			if(ready){
				deferred=$.getScript(path,ready);
			}else{
				deferred=$.getScript(path);
			}
			OC.addScript.loaded[path]=deferred;
		}else{
			if(ready){
				ready();
			}
		}
		return OC.addScript.loaded[path];
	},
	/**
	 * load a css file and load it
	 * @param app the app id to which the css style belongs
	 * @param style the filename of the css file
	 */
	addStyle:function(app,style){
		var path=OC.filePath(app,'css',style+'.css');
		if(OC.addStyle.loaded.indexOf(path)===-1){
			OC.addStyle.loaded.push(path);
			style=$('<link rel="stylesheet" type="text/css" href="'+path+'"/>');
			$('head').append(style);
		}
	},
	basename: function(path) {
		return path.replace(/\\/g,'/').replace( /.*\//, '' );
	},
	dirname: function(path) {
		return path.replace(/\\/g,'/').replace(/\/[^\/]*$/, '');
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
		mtime = parseInt(mtime,10);
		var date = new Date(1000*mtime);
		return date.getDate()+'.'+(date.getMonth()+1)+'.'+date.getFullYear()+', '+date.getHours()+':'+date.getMinutes();
	},
	/**
	 * Opens a popup with the setting for an app.
	 * @param appid String. The ID of the app e.g. 'calendar', 'contacts' or 'files'.
	 * @param loadJS boolean or String. If true 'js/settings.js' is loaded. If it's a string
	 * it will attempt to load a script by that name in the 'js' directory.
	 * @param cache boolean. If true the javascript file won't be forced refreshed. Defaults to true.
	 * @param scriptName String. The name of the PHP file to load. Defaults to 'settings.php' in
	 * the root of the app directory hierarchy.
	 */
	appSettings:function(args) {
		if(typeof args === 'undefined' || typeof args.appid === 'undefined') {
			throw { name: 'MissingParameter', message: 'The parameter appid is missing' };
		}
		var props = {scriptName:'settings.php', cache:true};
		$.extend(props, args);
		var settings = $('#appsettings');
		if(settings.length == 0) {
			throw { name: 'MissingDOMElement', message: 'There has be be an element with id "appsettings" for the popup to show.' };
		}
		var popup = $('#appsettings_popup');
		if(popup.length == 0) {
			$('body').prepend('<div class="popup hidden" id="appsettings_popup"></div>');
			popup = $('#appsettings_popup');
			popup.addClass(settings.hasClass('topright') ? 'topright' : 'bottomleft');
		}
		if(popup.is(':visible')) {
			popup.hide().remove();
		} else {
			var arrowclass = settings.hasClass('topright') ? 'up' : 'left';
			var jqxhr = $.get(OC.filePath(props.appid, '', props.scriptName), function(data) {
				popup.html(data).ready(function() {
					popup.prepend('<span class="arrow '+arrowclass+'"></span><h2>'+t('core', 'Settings')+'</h2><a class="close svg"></a>').show();
					popup.find('.close').bind('click', function() {
						popup.remove();
					});
					if(typeof props.loadJS !== 'undefined') {
						var scriptname;
						if(props.loadJS === true) {
							scriptname = 'settings.js';
						} else if(typeof props.loadJS === 'string') {
							scriptname = props.loadJS;
						} else {
							throw { name: 'InvalidParameter', message: 'The "loadJS" parameter must be either boolean or a string.' };
						}
						if(props.cache) {
							$.ajaxSetup({cache: true});
						}
						$.getScript(OC.filePath(props.appid, 'js', scriptname))
						.fail(function(jqxhr, settings, e) {
							throw e;
						});
					}
				}).show();
			}, 'html');
		}
	}
};
OC.search.customResults={};
OC.search.currentResult=-1;
OC.search.lastQuery='';
OC.search.lastResults={};
OC.addStyle.loaded=[];
OC.addScript.loaded=[];

OC.Notification={
	queuedNotifications: [],
	getDefaultNotificationFunction: null,
	setDefault: function(callback) {
		OC.Notification.getDefaultNotificationFunction = callback;
	},
	hide: function(callback) {
		$('#notification').fadeOut('400', function(){
			if (OC.Notification.isHidden()) {
				if (OC.Notification.getDefaultNotificationFunction) {
					OC.Notification.getDefaultNotificationFunction.call();
				}
			}
			if (callback) {
				callback.call();
			}
			$('#notification').empty();
			if(OC.Notification.queuedNotifications.length > 0){
				OC.Notification.showHtml(OC.Notification.queuedNotifications[0]);
				OC.Notification.queuedNotifications.shift();
			}
		});
	},
	showHtml: function(html) {
		if(($('#notification').filter('span.undo').length == 1) || OC.Notification.isHidden()){
			$('#notification').html(html);
			$('#notification').fadeIn().css("display","inline");
		}else{
			OC.Notification.queuedNotifications.push(html);
		}
	},
	show: function(text) {
		if(($('#notification').filter('span.undo').length == 1) || OC.Notification.isHidden()){
			$('#notification').html(text);
			$('#notification').fadeIn().css("display","inline");
		}else{
			OC.Notification.queuedNotifications.push($(text).html());
		}
	},
	isHidden: function() {
		return ($("#notification").text() === '');
	}
};

OC.Breadcrumb={
	container:null,
	crumbs:[],
	push:function(name, link){
		if(!OC.Breadcrumb.container){//default
			OC.Breadcrumb.container=$('#controls');
		}
		var crumb=$('<div/>');
		crumb.addClass('crumb').addClass('last');

		var crumbLink=$('<a/>');
		crumbLink.attr('href',link);
		crumbLink.text(name);
		crumb.append(crumbLink);

		var existing=OC.Breadcrumb.container.find('div.crumb');
		if(existing.length){
			existing.removeClass('last');
			existing.last().after(crumb);
		}else{
			OC.Breadcrumb.container.append(crumb);
		}
		OC.Breadcrumb.crumbs.push(crumb);
		return crumb;
	},
	pop:function(){
		if(!OC.Breadcrumb.container){//default
			OC.Breadcrumb.container=$('#controls');
		}
		OC.Breadcrumb.container.find('div.crumb').last().remove();
		OC.Breadcrumb.container.find('div.crumb').last().addClass('last');
		OC.Breadcrumb.crumbs.pop();
	},
	clear:function(){
		if(!OC.Breadcrumb.container){//default
			OC.Breadcrumb.container=$('#controls');
		}
		OC.Breadcrumb.container.find('div.crumb').remove();
		OC.Breadcrumb.crumbs=[];
	}
};

if(typeof localStorage !=='undefined' && localStorage !== null){
	//user and instance aware localstorage
	OC.localStorage={
		namespace:'oc_'+OC.currentUser+'_'+OC.webroot+'_',
		hasItem:function(name){
			return OC.localStorage.getItem(name)!==null;
		},
		setItem:function(name,item){
			return localStorage.setItem(OC.localStorage.namespace+name,JSON.stringify(item));
		},
		getItem:function(name){
			var item = localStorage.getItem(OC.localStorage.namespace+name);
			if(item===null) {
				return null;
			} else if (typeof JSON === 'undefined') {
				//fallback to jquery for IE6/7/8
				return $.parseJSON(item);
			} else {
				return JSON.parse(item);
			}
		}
	};
}else{
	//dummy localstorage
	OC.localStorage={
		hasItem:function(){
			return false;
		},
		setItem:function(){
			return false;
		},
		getItem:function(){
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
		if (typeof fun !== "function"){
			throw new TypeError();
		}

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
		from = (from < 0) ? Math.ceil(from) : Math.floor(from);
		if (from < 0){
			from += len;
		}

		for (; from < len; from++)
		{
			if (from in this && this[from] === elt){
				return from;
			}
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
					if(parts.length===2){
						var value=parts[1].trim();
						if(value[0]==='"'){
							value=value.substr(1,value.length-2);
						}
						headers[parts[0]]=value;
					}
				}
			});
			if(headers["Content-Type"]!=='image/svg+xml'){
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
	if (selector.length === 0) {
		return;
	}
	var height = parseFloat($(window).height())-selector.offset().top;
	selector.css('height', height + 'px');
	if(selector.outerHeight() > selector.height()){
		selector.css('height', height-(selector.outerHeight()-selector.height()) + 'px');
	}
	console.warn("This function is deprecated! Use CSS instead");
}

/**
 * Fills height and width of window. (more precise than height: 100%; or width: 100%;)
 */
function fillWindow(selector) {
	if (selector.length === 0) {
		return;
	}
	fillHeight(selector);
	var width = parseFloat($(window).width())-selector.offset().left;
	selector.css('width', width + 'px');
	if(selector.outerWidth() > selector.width()){
		selector.css('width', width-(selector.outerWidth()-selector.width()) + 'px');
	}
	console.warn("This function is deprecated! Use CSS instead");
}

$(document).ready(function(){
	sessionHeartBeat();

	if(!SVGSupport()){ //replace all svg images with png images for browser that dont support svg
		replaceSVG();
	}else{
		SVGSupport.checkMimeType();
	}
	$('form.searchbox').submit(function(event){
		event.preventDefault();
	});
	$('#searchbox').keyup(function(event){
		if(event.keyCode===13){//enter
			if(OC.search.currentResult>-1){
				var result=$('#searchresults tr.result a')[OC.search.currentResult];
				window.location = $(result).attr('href');
			}
		}else if(event.keyCode===38){//up
			if(OC.search.currentResult>0){
				OC.search.currentResult--;
				OC.search.renderCurrent();
			}
		}else if(event.keyCode===40){//down
			if(OC.search.lastResults.length>OC.search.currentResult+1){
				OC.search.currentResult++;
				OC.search.renderCurrent();
			}
		}else if(event.keyCode===27){//esc
			OC.search.hide();
		}else{
			var query=$('#searchbox').val();
			if(OC.search.lastQuery!==query){
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
	$('#password').showPassword();
	$('#adminpass').showPassword();	
	$('#pass2').showPassword();

	//use infield labels
	$("label.infield").inFieldLabels();

	var checkShowCredentials = function() {
		var empty = false;
		$('input#user, input#password').each(function() {
			if ($(this).val() === '') {
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
	};
	// hide log in button etc. when form fields not filled
	// commented out due to some browsers having issues with it
	// checkShowCredentials();
	// $('input#user, input#password').keyup(checkShowCredentials);

	$('#settings #expand').keydown(function(event) {
		if (event.which === 13 || event.which === 32) {
			$('#expand').click()
		}
	});
	$('#settings #expand').click(function(event) {
		$('#settings #expanddiv').slideToggle(200);
		event.stopPropagation();
	});
	$('#settings #expanddiv').click(function(event){
		event.stopPropagation();
	});
	$(document).click(function(){//hide the settings menu when clicking outside it
		$('#settings #expanddiv').slideUp(200);
	});

	// all the tipsy stuff needs to be here (in reverse order) to work
	$('.jp-controls .jp-previous').tipsy({gravity:'nw', fade:true, live:true});
	$('.jp-controls .jp-next').tipsy({gravity:'n', fade:true, live:true});
	$('.displayName .action').tipsy({gravity:'se', fade:true, live:true});
	$('.password .action').tipsy({gravity:'se', fade:true, live:true});
	$('#upload').tipsy({gravity:'w', fade:true});
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

		if (this === void 0 || this === null){
			throw new TypeError();
		}

		var t = Object(this);
		var len = t.length >>> 0;
		if (typeof fun !== "function"){
			throw new TypeError();
		}

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
 */
$.fn.filterAttr = function(attr_name, attr_value) {
	return this.filter(function() { return $(this).attr(attr_name) === attr_value; });
};

function humanFileSize(size) {
	var humanList = ['B', 'kB', 'MB', 'GB', 'TB'];
	// Calculate Log with base 1024: size = 1024 ** order
	var order = size?Math.floor(Math.log(size) / Math.log(1024)):0;
	// Stay in range of the byte sizes that are defined
	order = Math.min(humanList.length - 1, order);
	var readableFormat = humanList[order];
	var relativeSize = (size / Math.pow(1024, order)).toFixed(1);
	if(relativeSize.substr(relativeSize.length-2,2)=='.0'){
		relativeSize=relativeSize.substr(0,relativeSize.length-2);
	}
	return relativeSize + ' ' + readableFormat;
}

function simpleFileSize(bytes) {
	var mbytes = Math.round(bytes/(1024*1024/10))/10;
	if(bytes == 0) { return '0'; }
	else if(mbytes < 0.1) { return '< 0.1'; }
	else if(mbytes > 1000) { return '> 1000'; }
	else { return mbytes.toFixed(1); }
}

function formatDate(date){
	if(typeof date=='number'){
		date=new Date(date);
	}
	return $.datepicker.formatDate(datepickerFormatDate, date)+' '+date.getHours()+':'+((date.getMinutes()<10)?'0':'')+date.getMinutes();
}

/**
 * takes an absolute timestamp and return a string with a human-friendly relative date
 * @param int a Unix timestamp
 */
function relative_modified_date(timestamp) {
	var timediff = Math.round((new Date()).getTime() / 1000) - timestamp;
	var diffminutes = Math.round(timediff/60);
	var diffhours = Math.round(diffminutes/60);
	var diffdays = Math.round(diffhours/24);
	var diffmonths = Math.round(diffdays/31);
	if(timediff < 60) { return t('core','seconds ago'); }
	else if(timediff < 120) { return t('core','1 minute ago'); }
	else if(timediff < 3600) { return t('core','{minutes} minutes ago',{minutes: diffminutes}); }
	else if(timediff < 7200) { return t('core','1 hour ago'); }
	else if(timediff < 86400) { return t('core','{hours} hours ago',{hours: diffhours}); }
	else if(timediff < 86400) { return t('core','today'); }
	else if(timediff < 172800) { return t('core','yesterday'); }
	else if(timediff < 2678400) { return t('core','{days} days ago',{days: diffdays}); }
	else if(timediff < 5184000) { return t('core','last month'); }
	else if(timediff < 31556926) { return t('core','{months} months ago',{months: diffmonths}); }
	//else if(timediff < 31556926) { return t('core','months ago'); }
	else if(timediff < 63113852) { return t('core','last year'); }
	else { return t('core','years ago'); }
}

/**
 * get a variable by name
 * @param string name
 */
OC.get=function(name) {
	var namespaces = name.split(".");
	var tail = namespaces.pop();
	var context=window;
	
	for(var i = 0; i < namespaces.length; i++) {
		context = context[namespaces[i]];
		if(!context){
			return false;
		}
	}
	return context[tail];
};

/**
 * set a variable by name
 * @param string name
 * @param mixed value
 */
OC.set=function(name, value) {
	var namespaces = name.split(".");
	var tail = namespaces.pop();
	var context=window;
	
	for(var i = 0; i < namespaces.length; i++) {
		if(!context[namespaces[i]]){
			context[namespaces[i]]={};
		}
		context = context[namespaces[i]];
	}
	context[tail]=value;
};


/**
 * Calls the server periodically every 15 mins to ensure that session doesnt
 * time out
 */
function sessionHeartBeat(){
	OC.Router.registerLoadedCallback(function(){
		var url = OC.Router.generate('heartbeat');
		setInterval(function(){
			$.post(url);
		}, 900000);
	});
}
