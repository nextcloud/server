/**
 * Disable console output unless DEBUG mode is enabled.
 * Add
 *	 define('DEBUG', true);
 * To the end of config/config.php to enable debug mode.
 * The undefined checks fix the broken ie8 console
 */
var oc_debug;
var oc_webroot;

var oc_current_user = document.getElementsByTagName('head')[0].getAttribute('data-user');
var oc_requesttoken = document.getElementsByTagName('head')[0].getAttribute('data-requesttoken');

window.oc_config = window.oc_config || {};

if (typeof oc_webroot === "undefined") {
	oc_webroot = location.pathname;
	var pos = oc_webroot.indexOf('/index.php/');
	if (pos !== -1) {
		oc_webroot = oc_webroot.substr(0, pos);
	}
	else {
		oc_webroot = oc_webroot.substr(0, oc_webroot.lastIndexOf('/'));
	}
}
if (oc_debug !== true || typeof console === "undefined" || typeof console.log === "undefined") {
	if (!window.console) {
		window.console = {};
	}
	var methods = ['log', 'debug', 'warn', 'info', 'error', 'assert', 'time', 'timeEnd'];
	for (var i = 0; i < methods.length; i++) {
		console[methods[i]] = function () { };
	}
}

function initL10N(app) {
	if (!( t.cache[app] )) {
		$.ajax(OC.filePath('core', 'ajax', 'translations.php'), {
			async: false,//todo a proper solution for this without sync ajax calls
			data: {'app': app},
			type: 'POST',
			success: function (jsondata) {
				t.cache[app] = jsondata.data;
				t.plural_form = jsondata.plural_form;
			}
		});

		// Bad answer ...
		if (!( t.cache[app] )) {
			t.cache[app] = [];
		}
	}
	if (typeof t.plural_function[app] == 'undefined') {
		t.plural_function[app] = function (n) {
			var p = (n != 1) ? 1 : 0;
			return { 'nplural' : 2, 'plural' : p };
		};

		/**
		 * code below has been taken from jsgettext - which is LGPL licensed
		 * https://developer.berlios.de/projects/jsgettext/
		 * http://cvs.berlios.de/cgi-bin/viewcvs.cgi/jsgettext/jsgettext/lib/Gettext.js
		 */
		var pf_re = new RegExp('^(\\s*nplurals\\s*=\\s*[0-9]+\\s*;\\s*plural\\s*=\\s*(?:\\s|[-\\?\\|&=!<>+*/%:;a-zA-Z0-9_\(\)])+)', 'm');
		if (pf_re.test(t.plural_form)) {
			//ex english: "Plural-Forms: nplurals=2; plural=(n != 1);\n"
			//pf = "nplurals=2; plural=(n != 1);";
			//ex russian: nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10< =4 && (n%100<10 or n%100>=20) ? 1 : 2)
			//pf = "nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)";
			var pf = t.plural_form;
			if (! /;\s*$/.test(pf)) pf = pf.concat(';');
			/* We used to use eval, but it seems IE has issues with it.
			 * We now use "new Function", though it carries a slightly
			 * bigger performance hit.
			 var code = 'function (n) { var plural; var nplurals; '+pf+' return { "nplural" : nplurals, "plural" : (plural === true ? 1 : plural ? plural : 0) }; };';
			 Gettext._locale_data[domain].head.plural_func = eval("("+code+")");
			 */
			var code = 'var plural; var nplurals; '+pf+' return { "nplural" : nplurals, "plural" : (plural === true ? 1 : plural ? plural : 0) };';
			t.plural_function[app] = new Function("n", code);
		} else {
			console.log("Syntax error in language file. Plural-Forms header is invalid ["+t.plural_forms+"]");
		}
	}
}
/**
 * translate a string
 * @param app the id of the app for which to translate the string
 * @param text the string to translate
 * @param vars (optional) FIXME
 * @param count (optional) number to replace %n with
 * @return string
 */
function t(app, text, vars, count){
	initL10N(app);
	var _build = function (text, vars, count) {
		return text.replace(/%n/g, count).replace(/{([^{}]*)}/g,
			function (a, b) {
				var r = vars[b];
				return typeof r === 'string' || typeof r === 'number' ? r : a;
			}
		);
	};
	var translation = text;
	if( typeof( t.cache[app][text] ) !== 'undefined' ){
		translation = t.cache[app][text];
	}

	if(typeof vars === 'object' || count !== undefined ) {
		return _build(translation, vars, count);
	} else {
		return translation;
	}
}
t.cache = {};
// different apps might or might not redefine the nplurals function correctly
// this is to make sure that a "broken" app doesn't mess up with the
// other app's plural function
t.plural_function = {};

/**
 * translate a string
 * @param app the id of the app for which to translate the string
 * @param text_singular the string to translate for exactly one object
 * @param text_plural the string to translate for n objects
 * @param count number to determine whether to use singular or plural
 * @param vars (optional) FIXME
 * @return string
 */
function n(app, text_singular, text_plural, count, vars) {
	initL10N(app);
	var identifier = '_' + text_singular + '_::_' + text_plural + '_';
	if( typeof( t.cache[app][identifier] ) !== 'undefined' ){
		var translation = t.cache[app][identifier];
		if ($.isArray(translation)) {
			var plural = t.plural_function[app](count);
			return t(app, translation[plural.plural], vars, count);
		}
	}

	if(count === 1) {
		return t(app, text_singular, vars, count);
	}
	else{
		return t(app, text_plural, vars, count);
	}
}

/**
* Sanitizes a HTML string
* @param s string
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
* @deprecated use Files.getDownloadURL() instead
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
	PERMISSION_ALL:31,
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
	 * Generates the absolute url for the given relative url, which can contain parameters.
	 *
	 * @returns {string}
	 * @param {string} url
	 * @param params
	 */
	generateUrl: function(url, params) {
		var _build = function (text, vars) {
			return text.replace(/{([^{}]*)}/g,
				function (a, b) {
					var r = vars[b];
					return typeof r === 'string' || typeof r === 'number' ? r : a;
				}
			);
		};
		if (url.charAt(0) !== '/') {
			url = '/' + url;

		}
		return OC.webroot + '/index.php' + _build(url, params);
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
	 * Redirect to the target URL, can also be used for downloads.
	 */
	redirect: function(targetUrl) {
		window.location = targetUrl;
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
			if (document.createStyleSheet) {
				document.createStyleSheet(path);
			} else {
				style=$('<link rel="stylesheet" type="text/css" href="'+path+'"/>');
				$('head').append(style);
			}
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
	 * Parses a URL query string into a JS map
	 * @param queryString query string in the format param1=1234&param2=abcde&param3=xyz
	 * @return map containing key/values matching the URL parameters
	 */
	parseQueryString:function(queryString){
		var parts,
			pos,
			components,
			result = {},
			key,
			value;
		if (!queryString){
			return null;
		}
		pos = queryString.indexOf('?');
		if (pos >= 0){
			queryString = queryString.substr(pos + 1);
		}
		parts = queryString.replace(/\+/g, '%20').split('&');
		for (var i = 0; i < parts.length; i++){
			// split on first equal sign
			var part = parts[i]
			pos = part.indexOf('=');
			if (pos >= 0) {
				components = [
					part.substr(0, pos),
					part.substr(pos + 1)
				]
			}
			else {
				// key only
				components = [part];
			}
			if (!components.length){
				continue;
			}
			key = decodeURIComponent(components[0]);
			if (!key){
				continue;
			}
			// if equal sign was there, return string
			if (components.length > 1) {
				result[key] = decodeURIComponent(components[1]);
			}
			// no equal sign => null value
			else {
				result[key] = null;
			}
		}
		return result;
	},

	/**
	 * Builds a URL query from a JS map.
	 * @param params parameter map
	 * @return string containing a URL query (without question) mark
	 */
	buildQueryString: function(params) {
		var s = '';
		var first = true;
		if (!params) {
			return s;
		}
		for (var key in params) {
			var value = params[key];
			if (first) {
				first = false;
			}
			else {
				s += '&';
			}
			s += encodeURIComponent(key);
			if (value !== null && typeof(value) !== 'undefined') {
				s += '=' + encodeURIComponent(value);
			}
		}
		return s;
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
					if(!SVGSupport()) {
						replaceSVG();
					}
				}).show();
			}, 'html');
		}
	},

	// for menu toggling
	registerMenu: function($toggle, $menuEl) {
		$menuEl.addClass('menu');
		$toggle.addClass('menutoggle');
		$toggle.on('click.menu', function(event) {
			if ($menuEl.is(OC._currentMenu)) {
				$menuEl.hide();
				OC._currentMenu = null;
				OC._currentMenuToggle = null;
				return false;
			}
			// another menu was open?
			else if (OC._currentMenu) {
				// close it
				OC._currentMenu.hide();
			}
			$menuEl.show();
			OC._currentMenu = $menuEl;
			OC._currentMenuToggle = $toggle;
			return false
		});
	},

	unregisterMenu: function($toggle, $menuEl) {
		// close menu if opened
		if ($menuEl.is(OC._currentMenu)) {
			$menuEl.hide();
			OC._currentMenu = null;
			OC._currentMenuToggle = null;
		}
		$toggle.off('click.menu').removeClass('menutoggle');
		$menuEl.removeClass('menu');
	},

	/**
	 * Wrapper for matchMedia
	 *
	 * This is makes it possible for unit tests to
	 * stub matchMedia (which doesn't work in PhantomJS)
	 */
	_matchMedia: function(media) {
		if (window.matchMedia) {
			return window.matchMedia(media);
		}
		return false;
	}
};
OC.search.customResults={};
OC.search.currentResult=-1;
OC.search.lastQuery='';
OC.search.lastResults={};
OC.addStyle.loaded=[];
OC.addScript.loaded=[];

OC.msg={
	startSaving:function(selector){
		OC.msg.startAction(selector, t('core', 'Saving...'));
	},
	finishedSaving:function(selector, data){
		OC.msg.finishedAction(selector, data);
	},
	startAction:function(selector, message){
		$(selector)
			.html( message )
			.removeClass('success')
			.removeClass('error')
			.stop(true, true)
			.show();
	},
	finishedAction:function(selector, data){
		if( data.status === "success" ){
			$(selector).html( data.data.message )
				.addClass('success')
				.stop(true, true)
				.delay(3000)
				.fadeOut(900);
		}else{
			$(selector).html( data.data.message ).addClass('error');
		}
	}
};

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
			$('#notification').text(text);
			$('#notification').fadeIn().css("display","inline");
		}else{
			OC.Notification.queuedNotifications.push($('<div/>').text(text).html());
		}
	},
	isHidden: function() {
		return ($("#notification").text() === '');
	}
};

OC.Breadcrumb={
	container:null,
	show:function(dir, leafname, leaflink){
		if(!this.container){//default
			this.container=$('#controls');
		}
		this._show(this.container, dir, leafname, leaflink);
	},
	_show:function(container, dir, leafname, leaflink){
		var self = this;

		this._clear(container);

		// show home + path in subdirectories
		if (dir) {
			//add home
			var link = OC.linkTo('files','index.php');

			var crumb=$('<div/>');
			crumb.addClass('crumb');

			var crumbLink=$('<a/>');
			crumbLink.attr('href',link);

			var crumbImg=$('<img/>');
			crumbImg.attr('src',OC.imagePath('core','places/home'));
			crumbLink.append(crumbImg);
			crumb.append(crumbLink);
			container.prepend(crumb);

			//add path parts
			var segments = dir.split('/');
			var pathurl = '';
			jQuery.each(segments, function(i,name) {
				if (name !== '') {
					pathurl = pathurl+'/'+name;
					var link = OC.linkTo('files','index.php')+'?dir='+encodeURIComponent(pathurl);
					self._push(container, name, link);
				}
			});
		}

		//add leafname
		if (leafname && leaflink) {
			this._push(container, leafname, leaflink);
		}
	},
	push:function(name, link){
		if(!this.container){//default
			this.container=$('#controls');
		}
		return this._push(OC.Breadcrumb.container, name, link);
	},
	_push:function(container, name, link){
		var crumb=$('<div/>');
		crumb.addClass('crumb').addClass('last');

		var crumbLink=$('<a/>');
		crumbLink.attr('href',link);
		crumbLink.text(name);
		crumb.append(crumbLink);

		var existing=container.find('div.crumb');
		if(existing.length){
			existing.removeClass('last');
			existing.last().after(crumb);
		}else{
			container.prepend(crumb);
		}
		return crumb;
	},
	pop:function(){
		if(!this.container){//default
			this.container=$('#controls');
		}
		this.container.find('div.crumb').last().remove();
		this.container.find('div.crumb').last().addClass('last');
	},
	clear:function(){
		if(!this.container){//default
			this.container=$('#controls');
		}
		this._clear(this.container);
	},
	_clear:function(container) {
		container.find('div.crumb').remove();
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
		removeItem:function(name,item){
			return localStorage.removeItem(OC.localStorage.namespace+name);
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
						headers[parts[0].toLowerCase()]=value;
					}
				}
			});
			if(headers["content-type"]!=='image/svg+xml'){
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

/**
 * Initializes core
 */
function initCore() {

	/**
	 * Calls the server periodically to ensure that session doesn't
	 * time out
	 */
	function initSessionHeartBeat(){
		// interval in seconds
		var interval = 900;
		if (oc_config.session_lifetime) {
			interval = Math.floor(oc_config.session_lifetime / 2);
		}
		// minimum one minute
		if (interval < 60) {
			interval = 60;
		}
		var url = OC.generateUrl('/heartbeat');
		setInterval(function(){
			$.post(url);
		}, interval * 1000);
	}

	// session heartbeat (defaults to enabled)
	if (typeof(oc_config.session_keepalive) === 'undefined' ||
		!!oc_config.session_keepalive) {

		initSessionHeartBeat();
	}

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
			if (FileList && typeof FileList.unfilter === 'function') { //TODO add hook system
				FileList.unfilter();
			}
		}else{
			var query=$('#searchbox').val();
			if(OC.search.lastQuery!==query){
				OC.search.lastQuery=query;
				OC.search.currentResult=-1;
				if (FileList && typeof FileList.filter === 'function') { //TODO add hook system
						FileList.filter(query);
				}
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

	var setShowPassword = function(input, label) {
		input.showPassword().keyup();
	};
	setShowPassword($('#adminpass'), $('label[for=show]'));
	setShowPassword($('#pass2'), $('label[for=personal-show]'));
	setShowPassword($('#dbpass'), $('label[for=dbpassword]'));

	//use infield labels
	$("label.infield").inFieldLabels({
		pollDuration: 100
	});

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

	// user menu
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
	//hide the user menu when clicking outside it
	$(document).click(function(){
		$('#settings #expanddiv').slideUp(200);
	});

	// all the tipsy stuff needs to be here (in reverse order) to work
	$('.displayName .action').tipsy({gravity:'se', fade:true, live:true});
	$('.password .action').tipsy({gravity:'se', fade:true, live:true});
	$('#upload').tipsy({gravity:'w', fade:true});
	$('.selectedActions a').tipsy({gravity:'s', fade:true, live:true});
	$('a.action.delete').tipsy({gravity:'e', fade:true, live:true});
	$('a.action').tipsy({gravity:'s', fade:true, live:true});
	$('td .modified').tipsy({gravity:'s', fade:true, live:true});
	$('input').tipsy({gravity:'w', fade:true});

	// toggle for menus
	$(document).on('mouseup.closemenus', function(event) {
		var $el = $(event.target);
		if ($el.closest('.menu').length || $el.closest('.menutoggle').length) {
			// don't close when clicking on the menu directly or a menu toggle
			return false;
		}
		if (OC._currentMenu) {
			OC._currentMenu.hide();
		}
		OC._currentMenu = null;
		OC._currentMenuToggle = null;
	});


	/**
	 * Set up the main menu toggle to react to media query changes.
	 * If the screen is small enough, the main menu becomes a toggle.
	 * If the screen is bigger, the main menu is not a toggle any more.
	 */
	function setupMainMenu() {
		// toggle the navigation on mobile
		if (!OC._matchMedia) {
			return;
		}
		var mq = OC._matchMedia('(max-width: 768px)');
		var lastMatch = mq.matches;
		var $toggle = $('#header #owncloud');
		var $navigation = $('#navigation');

		function updateMainMenu() {
			// mobile mode ?
			if (lastMatch && !$toggle.hasClass('menutoggle')) {
				// init the menu
				OC.registerMenu($toggle, $navigation);
				$toggle.data('oldhref', $toggle.attr('href'));
				$toggle.attr('href', '#');
				$navigation.hide();
			}
			else {
				OC.unregisterMenu($toggle, $navigation);
				$toggle.attr('href', $toggle.data('oldhref'));
				$navigation.show();
			}
		}

		updateMainMenu();

		// TODO: debounce this
		$(window).resize(function() {
			if (lastMatch !== mq.matches) {
				lastMatch = mq.matches;
				updateMainMenu();
			}
		});
	}

	if (window.matchMedia) {
		setupMainMenu();
	}
}

$(document).ready(initCore);

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
	if(order < 2){
		relativeSize = parseFloat(relativeSize).toFixed(0);
	}
	else if(relativeSize.substr(relativeSize.length-2,2)==='.0'){
		relativeSize=relativeSize.substr(0,relativeSize.length-2);
	}
	return relativeSize + ' ' + readableFormat;
}

function formatDate(date){
	if(typeof date=='number'){
		date=new Date(date);
	}
	return $.datepicker.formatDate(datepickerFormatDate, date)+' '+date.getHours()+':'+((date.getMinutes()<10)?'0':'')+date.getMinutes();
}

// taken from http://stackoverflow.com/questions/1403888/get-url-parameter-with-jquery
function getURLParameter(name) {
	return decodeURI(
			(RegExp(name + '=' + '(.+?)(&|$)').exec(location.search) || [, null])[1]
			);
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
	else if(timediff < 3600) { return n('core','%n minute ago', '%n minutes ago', diffminutes); }
	else if(timediff < 86400) { return n('core', '%n hour ago', '%n hours ago', diffhours); }
	else if(timediff < 86400) { return t('core','today'); }
	else if(timediff < 172800) { return t('core','yesterday'); }
	else if(timediff < 2678400) { return n('core', '%n day ago', '%n days ago', diffdays); }
	else if(timediff < 5184000) { return t('core','last month'); }
	else if(timediff < 31556926) { return n('core', '%n month ago', '%n months ago', diffmonths); }
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

// fix device width on windows phone
(function() {
	if ("-ms-user-select" in document.documentElement.style && navigator.userAgent.match(/IEMobile\/10\.0/)) {
		var msViewportStyle = document.createElement("style");
		msViewportStyle.appendChild(
			document.createTextNode("@-ms-viewport{width:auto!important}")
		);
		document.getElementsByTagName("head")[0].appendChild(msViewportStyle);
	}
})();

/**
 * select a range in an input field
 * @link http://stackoverflow.com/questions/499126/jquery-set-cursor-position-in-text-area
 * @param {type} start
 * @param {type} end
 */
jQuery.fn.selectRange = function(start, end) {
	return this.each(function() {
		if (this.setSelectionRange) {
			this.focus();
			this.setSelectionRange(start, end);
		} else if (this.createTextRange) {
			var range = this.createTextRange();
			range.collapse(true);
			range.moveEnd('character', end);
			range.moveStart('character', start);
			range.select();
		}
	});
};

/**
 * check if an element exists.
 * allows you to write if ($('#myid').exists()) to increase readability
 * @link http://stackoverflow.com/questions/31044/is-there-an-exists-function-for-jquery
 */
jQuery.fn.exists = function(){
	return this.length > 0;
};

