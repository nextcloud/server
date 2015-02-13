/**
 * Disable console output unless DEBUG mode is enabled.
 * Add
 *      define('DEBUG', true);
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
if (
	oc_debug !== true || typeof console === "undefined" ||
	typeof console.log === "undefined"
) {
	if (!window.console) {
		window.console = {};
	}
	var noOp = function() { };
	var methods = ['log', 'debug', 'warn', 'info', 'error', 'assert', 'time', 'timeEnd'];
	for (var i = 0; i < methods.length; i++) {
		console[methods[i]] = noOp;
	}
}

/**
* Sanitizes a HTML string by replacing all potential dangerous characters with HTML entities
* @param {string} s String to sanitize
* @return {string} Sanitized string
*/
function escapeHTML(s) {
	return s.toString().split('&').join('&amp;').split('<').join('&lt;').split('>').join('&gt;').split('"').join('&quot;').split('\'').join('&#039;');
}

/**
* Get the path to download a file
* @param {string} file The filename
* @param {string} dir The directory the file is in - e.g. $('#dir').val()
* @return {string} Path to download the file
* @deprecated use Files.getDownloadURL() instead
*/
function fileDownloadPath(dir, file) {
	return OC.filePath('files', 'ajax', 'download.php')+'?files='+encodeURIComponent(file)+'&dir='+encodeURIComponent(dir);
}

/** @namespace */
var OC={
	PERMISSION_CREATE:4,
	PERMISSION_READ:1,
	PERMISSION_UPDATE:2,
	PERMISSION_DELETE:8,
	PERMISSION_SHARE:16,
	PERMISSION_ALL:31,
	TAG_FAVORITE: '_$!<Favorite>!$_',
	/* jshint camelcase: false */
	webroot:oc_webroot,
	appswebroots:(typeof oc_appswebroots !== 'undefined') ? oc_appswebroots:false,
	currentUser:(typeof oc_current_user!=='undefined')?oc_current_user:false,
	config: window.oc_config,
	appConfig: window.oc_appconfig || {},
	theme: window.oc_defaults || {},
	coreApps:['', 'admin','log','core/search','settings','core','3rdparty'],
	menuSpeed: 100,

	/**
	 * Get an absolute url to a file in an app
	 * @param {string} app the id of the app the file belongs to
	 * @param {string} file the file path relative to the app folder
	 * @return {string} Absolute URL to a file
	 */
	linkTo:function(app,file){
		return OC.filePath(app,'',file);
	},

	/**
	 * Creates a relative url for remote use
	 * @param {string} service id
	 * @return {string} the url
	 */
	linkToRemoteBase:function(service) {
		return OC.webroot + '/remote.php/' + service;
	},

	/**
	 * @brief Creates an absolute url for remote use
	 * @param {string} service id
	 * @return {string} the url
	 */
	linkToRemote:function(service) {
		return window.location.protocol + '//' + window.location.host + OC.linkToRemoteBase(service);
	},

	/**
	 * Gets the base path for the given OCS API service.
	 * @param {string} service name
	 * @return {string} OCS API base path
	 */
	linkToOCS: function(service) {
		return window.location.protocol + '//' + window.location.host + OC.webroot + '/ocs/v1.php/' + service + '/';
	},

	/**
	 * Generates the absolute url for the given relative url, which can contain parameters.
	 * @param {string} url
	 * @param params
	 * @return {string} Absolute URL for the given relative URL
	 */
	generateUrl: function(url, params) {
		var _build = function (text, vars) {
			var vars = vars || [];
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
		// TODO save somewhere whether the webserver is able to skip the index.php to have shorter links (e.g. for sharing)
		return OC.webroot + '/index.php' + _build(url, params);
	},

	/**
	 * Get the absolute url for a file in an app
	 * @param {string} app the id of the app
	 * @param {string} type the type of the file to link to (e.g. css,img,ajax.template)
	 * @param {string} file the filename
	 * @return {string} Absolute URL for a file in an app
	 * @deprecated use OC.generateUrl() instead
	 */
	filePath:function(app,type,file){
		var isCore=OC.coreApps.indexOf(app)!==-1,
			link=OC.webroot;
		if(file.substring(file.length-3) === 'php' && !isCore){
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
	 * @param {string} targetURL URL to redirect to
	 */
	redirect: function(targetURL) {
		window.location = targetURL;
	},

	/**
	 * get the absolute path to an image file
	 * if no extension is given for the image, it will automatically decide
	 * between .png and .svg based on what the browser supports
	 * @param {string} app the app id to which the image belongs
	 * @param {string} file the name of the image file
	 * @return {string}
	 */
	imagePath:function(app,file){
		if(file.indexOf('.')==-1){//if no extension is given, use png or svg depending on browser support
			file+=(OC.Util.hasSVGSupport())?'.svg':'.png';
		}
		return OC.filePath(app,'img',file);
	},

	/**
	 * URI-Encodes a file path but keep the path slashes.
	 *
	 * @param path path
	 * @return encoded path
	 */
	encodePath: function(path) {
		if (!path) {
			return path;
		}
		var parts = path.split('/');
		var result = [];
		for (var i = 0; i < parts.length; i++) {
			result.push(encodeURIComponent(parts[i]));
		}
		return result.join('/');
	},

	/**
	 * Load a script for the server and load it. If the script is already loaded,
	 * the event handler will be called directly
	 * @param {string} app the app id to which the script belongs
	 * @param {string} script the filename of the script
	 * @param ready event handler to be called when the script is loaded
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
	 * Loads a CSS file
	 * @param {string} app the app id to which the css style belongs
	 * @param {string} style the filename of the css file
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

	/**
	 * Loads translations for the given app asynchronously.
	 *
	 * @param {String} app app name
	 * @param {Function} callback callback to call after loading
	 * @return {Promise}
	 */
	addTranslations: function(app, callback) {
		return OC.L10N.load(app, callback);
	},

	/**
	 * Returns the base name of the given path.
	 * For example for "/abc/somefile.txt" it will return "somefile.txt"
	 *
	 * @param {String} path
	 * @return {String} base name
	 */
	basename: function(path) {
		return path.replace(/\\/g,'/').replace( /.*\//, '' );
	},

	/**
	 * Returns the dir name of the given path.
	 * For example for "/abc/somefile.txt" it will return "/abc"
	 *
	 * @param {String} path
	 * @return {String} dir name
	 */
	dirname: function(path) {
		return path.replace(/\\/g,'/').replace(/\/[^\/]*$/, '');
	},

	/**
	 * Do a search query and display the results
	 * @param {string} query the search query
	 */
	search: function (query) {
		OC.Search.search(query, null, 0, 30);
	},
	/**
	 * Dialog helper for jquery dialogs.
	 *
	 * @namespace OC.dialogs
	 */
	dialogs:OCdialogs,
	/**
	 * Parses a URL query string into a JS map
	 * @param {string} queryString query string in the format param1=1234&param2=abcde&param3=xyz
	 * @return {Object.<string, string>} map containing key/values matching the URL parameters
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
			var part = parts[i];
			pos = part.indexOf('=');
			if (pos >= 0) {
				components = [
					part.substr(0, pos),
					part.substr(pos + 1)
				];
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
	 * @param {Object.<string, string>} params map containing key/values matching the URL parameters
	 * @return {string} String containing a URL query (without question) mark
	 */
	buildQueryString: function(params) {
		if (!params) {
			return '';
		}
		return $.map(params, function(value, key) {
			var s = encodeURIComponent(key);
			if (value !== null && typeof(value) !== 'undefined') {
				s += '=' + encodeURIComponent(value);
			}
			return s;
		}).join('&');
	},

	/**
	 * Opens a popup with the setting for an app.
	 * @param {string} appid The ID of the app e.g. 'calendar', 'contacts' or 'files'.
	 * @param {boolean|string} loadJS If true 'js/settings.js' is loaded. If it's a string
	 * it will attempt to load a script by that name in the 'js' directory.
	 * @param {boolean} [cache] If true the javascript file won't be forced refreshed. Defaults to true.
	 * @param {string} [scriptName] The name of the PHP file to load. Defaults to 'settings.php' in
	 * the root of the app directory hierarchy.
	 */
	appSettings:function(args) {
		if(typeof args === 'undefined' || typeof args.appid === 'undefined') {
			throw { name: 'MissingParameter', message: 'The parameter appid is missing' };
		}
		var props = {scriptName:'settings.php', cache:true};
		$.extend(props, args);
		var settings = $('#appsettings');
		if(settings.length === 0) {
			throw { name: 'MissingDOMElement', message: 'There has be be an element with id "appsettings" for the popup to show.' };
		}
		var popup = $('#appsettings_popup');
		if(popup.length === 0) {
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
					if(!OC.Util.hasSVGSupport()) {
						OC.Util.replaceSVG();
					}
				}).show();
			}, 'html');
		}
	},

	/**
	 * For menu toggling
	 * @todo Write documentation
	 */
	registerMenu: function($toggle, $menuEl) {
		$menuEl.addClass('menu');
		$toggle.on('click.menu', function(event) {
			if ($menuEl.is(OC._currentMenu)) {
				$menuEl.slideUp(OC.menuSpeed);
				OC._currentMenu = null;
				OC._currentMenuToggle = null;
				return false;
			}
			// another menu was open?
			else if (OC._currentMenu) {
				// close it
				OC._currentMenu.hide();
			}
			$menuEl.slideToggle(OC.menuSpeed);
			OC._currentMenu = $menuEl;
			OC._currentMenuToggle = $toggle;
			return false;
		});
	},

	/**
	 *  @todo Write documentation
	 */
	unregisterMenu: function($toggle, $menuEl) {
		// close menu if opened
		if ($menuEl.is(OC._currentMenu)) {
			$menuEl.slideUp(OC.menuSpeed);
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
	 * @private
	 */
	_matchMedia: function(media) {
		if (window.matchMedia) {
			return window.matchMedia(media);
		}
		return false;
	},

	/**
	 * Returns the user's locale
	 *
	 * @return {String} locale string
	 */
	getLocale: function() {
		return $('html').prop('lang');
	}
};

/**
 * @namespace OC.Plugins
 */
OC.Plugins = {
	/**
	 * @type Array.<OC.Plugin>
	 */
	_plugins: {},

	/**
	 * Register plugin
	 *
	 * @param {String} targetName app name / class name to hook into
	 * @param {OC.Plugin} plugin
	 */
	register: function(targetName, plugin) {
		var plugins = this._plugins[targetName];
		if (!plugins) {
			plugins = this._plugins[targetName] = [];
		}
		plugins.push(plugin);
	},

	/**
	 * Returns all plugin registered to the given target
	 * name / app name / class name.
	 *
	 * @param {String} targetName app name / class name to hook into
	 * @return {Array.<OC.Plugin>} array of plugins
	 */
	getPlugins: function(targetName) {
		return this._plugins[targetName] || [];
	},

	/**
	 * Call attach() on all plugins registered to the given target name.
	 *
	 * @param {String} targetName app name / class name
	 * @param {Object} object to be extended
	 * @param {Object} [options] options
	 */
	attach: function(targetName, targetObject, options) {
		var plugins = this.getPlugins(targetName);
		for (var i = 0; i < plugins.length; i++) {
			if (plugins[i].attach) {
				plugins[i].attach(targetObject, options);
			}
		}
	},

	/**
	 * Call detach() on all plugins registered to the given target name.
	 *
	 * @param {String} targetName app name / class name
	 * @param {Object} object to be extended
	 * @param {Object} [options] options
	 */
	detach: function(targetName, targetObject, options) {
		var plugins = this.getPlugins(targetName);
		for (var i = 0; i < plugins.length; i++) {
			if (plugins[i].detach) {
				plugins[i].detach(targetObject, options);
			}
		}
	},

	/**
	 * Plugin
	 *
	 * @todo make this a real class in the future
	 * @typedef {Object} OC.Plugin
	 *
	 * @property {String} name plugin name
	 * @property {Function} attach function that will be called when the
	 * plugin is attached
	 * @property {Function} [detach] function that will be called when the
	 * plugin is detached
	 */

};

/**
 * @namespace OC.search
 */
OC.search.customResults = {};
/**
 * @deprecated use get/setFormatter() instead
 */
OC.search.resultTypes = {};

OC.addStyle.loaded=[];
OC.addScript.loaded=[];

/**
 * @todo Write documentation
 */
OC.msg={
	/**
	 * @param selector
	 * @todo Write documentation
	 */
	startSaving:function(selector){
		OC.msg.startAction(selector, t('core', 'Saving...'));
	},

	/**
	 * @param selector
	 * @param data
	 * @todo Write documentation
	 */
	finishedSaving:function(selector, data){
		OC.msg.finishedAction(selector, data);
	},

	/**
	 * @param selector
	 * @param {string} message Message to display
	 * @todo WRite documentation
	 */
	startAction:function(selector, message){
		$(selector)
			.html( message )
			.removeClass('success')
			.removeClass('error')
			.stop(true, true)
			.show();
	},

	/**
	 * @param selector
	 * @param data
	 * @todo Write documentation
	 */
	finishedAction:function(selector, data){
		if( data.status === "success" ){
			$(selector).html( data.data.message )
					.addClass('success')
					.removeClass('error')
					.stop(true, true)
					.delay(3000)
					.fadeOut(900)
					.show();
		}else{
			$(selector).html( data.data.message )
					.addClass('error')
					.removeClass('success')
					.show();
		}
	}
};

/**
 * @todo Write documentation
 * @namespace
 */
OC.Notification={
	queuedNotifications: [],
	getDefaultNotificationFunction: null,
	notificationTimer: 0,

	/**
	 * @param callback
	 * @todo Write documentation
	 */
	setDefault: function(callback) {
		OC.Notification.getDefaultNotificationFunction = callback;
	},

	/**
	 * Hides a notification
	 * @param callback
	 * @todo Write documentation
	 */
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

	/**
	 * Shows a notification as HTML without being sanitized before.
	 * If you pass unsanitized user input this may lead to a XSS vulnerability.
	 * Consider using show() instead of showHTML()
	 * @param {string} html Message to display
	 */
	showHtml: function(html) {
		var notification = $('#notification');
		if((notification.filter('span.undo').length == 1) || OC.Notification.isHidden()){
			notification.html(html);
			notification.fadeIn().css('display','inline-block');
		}else{
			OC.Notification.queuedNotifications.push(html);
		}
	},

	/**
	 * Shows a sanitized notification
	 * @param {string} text Message to display
	 */
	show: function(text) {
		var notification = $('#notification');
		if((notification.filter('span.undo').length == 1) || OC.Notification.isHidden()){
			notification.text(text);
			notification.fadeIn().css('display','inline-block');
		}else{
			OC.Notification.queuedNotifications.push($('<div/>').text(text).html());
		}
	},


	/**
	 * Shows a notification that disappears after x seconds, default is
	 * 7 seconds
	 * @param {string} text Message to show
	 * @param {array} [options] options array
	 * @param {int} [options.timeout=7] timeout in seconds, if this is 0 it will show the message permanently
	 * @param {boolean} [options.isHTML=false] an indicator for HTML notifications (true) or text (false)
	 */
	showTemporary: function(text, options) {
		var defaults = {
				isHTML: false,
				timeout: 7
			},
			options = options || {};
		// merge defaults with passed in options
		_.defaults(options, defaults);

		// clear previous notifications
		OC.Notification.hide();
		if(OC.Notification.notificationTimer) {
			clearTimeout(OC.Notification.notificationTimer);
		}

		if(options.isHTML) {
			OC.Notification.showHtml(text);
		} else {
			OC.Notification.show(text);
		}

		if(options.timeout > 0) {
			// register timeout to vanish notification
			OC.Notification.notificationTimer = setTimeout(OC.Notification.hide, (options.timeout * 1000));
		}
	},

	/**
	 * Returns whether a notification is hidden.
	 * @return {boolean}
	 */
	isHidden: function() {
		return ($("#notification").text() === '');
	}
};

/**
 * Breadcrumb class
 *
 * @namespace
 *
 * @deprecated will be replaced by the breadcrumb implementation
 * of the files app in the future
 */
OC.Breadcrumb={
	container:null,
	/**
	 * @todo Write documentation
	 * @param dir
	 * @param leafName
	 * @param leafLink
	 */
	show:function(dir, leafName, leafLink){
		if(!this.container){//default
			this.container=$('#controls');
		}
		this._show(this.container, dir, leafName, leafLink);
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

	/**
	 * @todo Write documentation
	 * @param {string} name
	 * @param {string} link
	 */
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

	/**
	 * @todo Write documentation
	 */
	pop:function(){
		if(!this.container){//default
			this.container=$('#controls');
		}
		this.container.find('div.crumb').last().remove();
		this.container.find('div.crumb').last().addClass('last');
	},

	/**
	 * @todo Write documentation
	 */
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
	/**
	 * User and instance aware localstorage
	 * @namespace
	 */
	OC.localStorage={
		namespace:'oc_'+OC.currentUser+'_'+OC.webroot+'_',

		/**
		 * Whether the storage contains items
		 * @param {string} name
		 * @return {boolean}
		 */
		hasItem:function(name){
			return OC.localStorage.getItem(name)!==null;
		},

		/**
		 * Add an item to the storage
		 * @param {string} name
		 * @param {string} item
		 */
		setItem:function(name,item){
			return localStorage.setItem(OC.localStorage.namespace+name,JSON.stringify(item));
		},

		/**
		 * Removes an item from the storage
		 * @param {string} name
		 * @param {string} item
		 */
		removeItem:function(name,item){
			return localStorage.removeItem(OC.localStorage.namespace+name);
		},

		/**
		 * Get an item from the storage
		 * @param {string} name
		 * @return {null|string}
		 */
		getItem:function(name){
			var item = localStorage.getItem(OC.localStorage.namespace+name);
			if(item === null) {
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
 * @return {boolean}
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
				OC.Util.replaceSVG();
				SVGSupport.checkMimeType.correct=false;
			}
		}
	});
};
SVGSupport.checkMimeType.correct=true;

/**
 * Replace all svg images with png for browser compatibility
 * @param $el
 * @deprecated use OC.Util.replaceSVG instead
 */
function replaceSVG($el){
	return OC.Util.replaceSVG($el);
}

/**
 * prototypical inheritance functions
 * @todo Write documentation
 * usage:
 * MySubObject=object(MyObject)
 */
function object(o) {
	function F() {}
	F.prototype = o;
	return new F();
}

/**
 * Initializes core
 */
function initCore() {

	/**
	 * Set users locale to moment.js as soon as possible
	 */
	moment.locale(OC.getLocale());


	/**
	 * Calls the server periodically to ensure that session doesn't
	 * time out
	 */
	function initSessionHeartBeat(){
		// max interval in seconds set to 24 hours
		var maxInterval = 24 * 3600;
		// interval in seconds
		var interval = 900;
		if (oc_config.session_lifetime) {
			interval = Math.floor(oc_config.session_lifetime / 2);
		}
		// minimum one minute
		if (interval < 60) {
			interval = 60;
		}
		if (interval > maxInterval) {
			interval = maxInterval;
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

	if(!OC.Util.hasSVGSupport()){ //replace all svg images with png images for browser that dont support svg
		OC.Util.replaceSVG();
	}else{
		SVGSupport.checkMimeType();
	}

	// user menu
	$('#settings #expand').keydown(function(event) {
		if (event.which === 13 || event.which === 32) {
			$('#expand').click();
		}
	});
	$('#settings #expand').click(function(event) {
		$('#settings #expanddiv').slideToggle(OC.menuSpeed);
		event.stopPropagation();
	});
	$('#settings #expanddiv').click(function(event){
		event.stopPropagation();
	});
	//hide the user menu when clicking outside it
	$(document).click(function(){
		$('#settings #expanddiv').slideUp(OC.menuSpeed);
	});

	// all the tipsy stuff needs to be here (in reverse order) to work
	$('.displayName .action').tipsy({gravity:'se', fade:true, live:true});
	$('.password .action').tipsy({gravity:'se', fade:true, live:true});
	$('#upload').tipsy({gravity:'w', fade:true});
	$('.selectedActions a').tipsy({gravity:'s', fade:true, live:true});
	$('a.action.delete').tipsy({gravity:'e', fade:true, live:true});
	$('a.action').tipsy({gravity:'s', fade:true, live:true});
	$('td .modified').tipsy({gravity:'s', fade:true, live:true});
	$('td.lastLogin').tipsy({gravity:'s', fade:true, html:true});
	$('input').tipsy({gravity:'w', fade:true});
	$('.extra-data').tipsy({gravity:'w', fade:true, live:true});

	// toggle for menus
	$(document).on('mouseup.closemenus', function(event) {
		var $el = $(event.target);
		if ($el.closest('.menu').length || $el.closest('.menutoggle').length) {
			// don't close when clicking on the menu directly or a menu toggle
			return false;
		}
		if (OC._currentMenu) {
			OC._currentMenu.slideUp(OC.menuSpeed);
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
		// toggle the navigation
		var $toggle = $('#header .menutoggle');
		var $navigation = $('#navigation');

		// init the menu
		OC.registerMenu($toggle, $navigation);
		$toggle.data('oldhref', $toggle.attr('href'));
		$toggle.attr('href', '#');
		$navigation.hide();

		// show loading feedback
		$navigation.delegate('a', 'click', function(event) {
			var $app = $(event.target);
			if(!$app.is('a')) {
				$app = $app.closest('a');
			}
			if(!event.ctrlKey) {
				$app.addClass('app-loading');
			}
		});
	}

	setupMainMenu();

	// just add snapper for logged in users
	if($('#app-navigation').length && !$('html').hasClass('lte9')) {

		// App sidebar on mobile
		var snapper = new Snap({
			element: document.getElementById('app-content'),
			disable: 'right',
			maxPosition: 250
		});
		$('#app-content').prepend('<div id="app-navigation-toggle" class="icon-menu" style="display:none;"></div>');
		$('#app-navigation-toggle').click(function(){
			if(snapper.state().state == 'left'){
				snapper.close();
			} else {
				snapper.open('left');
			}
		});
		// close sidebar when switching navigation entry
		var $appNavigation = $('#app-navigation');
		$appNavigation.delegate('a', 'click', function(event) {
			var $target = $(event.target);
			// don't hide navigation when changing settings or adding things
			if($target.is('.app-navigation-noclose') ||
				$target.closest('.app-navigation-noclose').length) {
				return;
			}
			if($target.is('.add-new') ||
				$target.closest('.add-new').length) {
				return;
			}
			if($target.is('#app-settings') ||
				$target.closest('#app-settings').length) {
				return;
			}
			snapper.close();
		});

		var toggleSnapperOnSize = function() {
			if($(window).width() > 768) {
				snapper.close();
				snapper.disable();
			} else {
				snapper.enable();
			}
		};

		$(window).resize(_.debounce(toggleSnapperOnSize, 250));

		// initial call
		toggleSnapperOnSize();

	}

}

$(document).ready(initCore);

/**
 * Filter Jquery selector by attribute value
 */
$.fn.filterAttr = function(attr_name, attr_value) {
	return this.filter(function() { return $(this).attr(attr_name) === attr_value; });
};

/**
 * Returns a human readable file size
 * @param {number} size Size in bytes
 * @param {boolean} skipSmallSizes return '< 1 kB' for small files
 * @return {string}
 */
function humanFileSize(size, skipSmallSizes) {
	var humanList = ['B', 'kB', 'MB', 'GB', 'TB'];
	// Calculate Log with base 1024: size = 1024 ** order
	var order = size > 0 ? Math.floor(Math.log(size) / Math.log(1024)) : 0;
	// Stay in range of the byte sizes that are defined
	order = Math.min(humanList.length - 1, order);
	var readableFormat = humanList[order];
	var relativeSize = (size / Math.pow(1024, order)).toFixed(1);
	if(skipSmallSizes === true && order === 0) {
		if(relativeSize !== "0.0"){
			return '< 1 kB';
		} else {
			return '0 kB';
		}
	}
	if(order < 2){
		relativeSize = parseFloat(relativeSize).toFixed(0);
	}
	else if(relativeSize.substr(relativeSize.length-2,2)==='.0'){
		relativeSize=relativeSize.substr(0,relativeSize.length-2);
	}
	return relativeSize + ' ' + readableFormat;
}

/**
 * Format an UNIX timestamp to a human understandable format
 * @param {number} timestamp UNIX timestamp
 * @return {string} Human readable format
 */
function formatDate(timestamp){
	return OC.Util.formatDate(timestamp);
}

//
/**
 * Get the value of a URL parameter
 * @link http://stackoverflow.com/questions/1403888/get-url-parameter-with-jquery
 * @param {string} name URL parameter
 * @return {string}
 */
function getURLParameter(name) {
	return decodeURI(
			(RegExp(name + '=' + '(.+?)(&|$)').exec(location.search) || [, null])[1]
			);
}

/**
 * Takes an absolute timestamp and return a string with a human-friendly relative date
 * @param {number} timestamp A Unix timestamp
 */
function relative_modified_date(timestamp) {
	/*
	 Were multiplying by 1000 to bring the timestamp back to its original value
	 per https://github.com/owncloud/core/pull/10647#discussion_r16790315
	  */
	return OC.Util.relativeModifiedDate(timestamp * 1000);
}

/**
 * Utility functions
 * @namespace
 */
OC.Util = {
	// TODO: remove original functions from global namespace
	humanFileSize: humanFileSize,

	/**
	 * @param timestamp
	 * @param format
	 * @returns {string} timestamp formatted as requested
	 */
	formatDate: function (timestamp, format) {
		format = format || "MMMM D, YYYY h:mm";
		return moment(timestamp).format(format);
	},

	/**
	 * @param timestamp
	 * @returns {string} human readable difference from now
	 */
	relativeModifiedDate: function (timestamp) {
		return moment(timestamp).fromNow();
	},
	/**
	 * Returns whether the browser supports SVG
	 * @return {boolean} true if the browser supports SVG, false otherwise
	 */
	// TODO: replace with original function
	hasSVGSupport: SVGSupport,
	/**
	 * If SVG is not supported, replaces the given icon's extension
	 * from ".svg" to ".png".
	 * If SVG is supported, return the image path as is.
	 * @param {string} file image path with svg extension
	 * @return {string} fixed image path with png extension if SVG is not supported
	 */
	replaceSVGIcon: function(file) {
		if (file && !OC.Util.hasSVGSupport()) {
			var i = file.lastIndexOf('.svg');
			if (i >= 0) {
				file = file.substr(0, i) + '.png' + file.substr(i+4);
			}
		}
		return file;
	},
	/**
	 * Replace SVG images in all elements that have the "svg" class set
	 * with PNG images.
	 *
	 * @param $el root element from which to search, defaults to $('body')
	 */
	replaceSVG: function($el) {
		if (!$el) {
			$el = $('body');
		}
		$el.find('img.svg').each(function(index,element){
			element=$(element);
			var src=element.attr('src');
			element.attr('src',src.substr(0, src.length-3) + 'png');
		});
		$el.find('.svg').each(function(index,element){
			element = $(element);
			var background = element.css('background-image');
			if (background){
				var i = background.lastIndexOf('.svg');
				if (i >= 0){
					background = background.substr(0,i) + '.png' + background.substr(i + 4);
					element.css('background-image', background);
				}
			}
			element.find('*').each(function(index, element) {
				element = $(element);
				var background = element.css('background-image');
				if (background) {
					var i = background.lastIndexOf('.svg');
					if(i >= 0){
						background = background.substr(0,i) + '.png' + background.substr(i + 4);
						element.css('background-image', background);
					}
				}
			});
		});
	},

	/**
	 * Remove the time component from a given date
	 *
	 * @param {Date} date date
	 * @return {Date} date with stripped time
	 */
	stripTime: function(date) {
		// FIXME: likely to break when crossing DST
		// would be better to use a library like momentJS
		return new Date(date.getFullYear(), date.getMonth(), date.getDate());
	},

	_chunkify: function(t) {
		// Adapted from http://my.opera.com/GreyWyvern/blog/show.dml/1671288
		var tz = [], x = 0, y = -1, n = 0, code, c;

		while (x < t.length) {
			c = t.charAt(x);
			// only include the dot in strings
			var m = ((!n && c === '.') || (c >= '0' && c <= '9'));
			if (m !== n) {
				// next chunk
				y++;
				tz[y] = '';
				n = m;
			}
			tz[y] += c;
			x++;
		}
		return tz;
	},
	/**
	 * Compare two strings to provide a natural sort
	 * @param a first string to compare
	 * @param b second string to compare
	 * @return -1 if b comes before a, 1 if a comes before b
	 * or 0 if the strings are identical
	 */
	naturalSortCompare: function(a, b) {
		var x;
		var aa = OC.Util._chunkify(a);
		var bb = OC.Util._chunkify(b);

		for (x = 0; aa[x] && bb[x]; x++) {
			if (aa[x] !== bb[x]) {
				var aNum = Number(aa[x]), bNum = Number(bb[x]);
				// note: == is correct here
				if (aNum == aa[x] && bNum == bb[x]) {
					return aNum - bNum;
				} else {
					// Forcing 'en' locale to match the server-side locale which is
					// always 'en'.
					//
					// Note: This setting isn't supported by all browsers but for the ones
					// that do there will be more consistency between client-server sorting
					return aa[x].localeCompare(bb[x], 'en');
				}
			}
		}
		return aa.length - bb.length;
	}
}

/**
 * Utility class for the history API,
 * includes fallback to using the URL hash when
 * the browser doesn't support the history API.
 *
 * @namespace
 */
OC.Util.History = {
	_handlers: [],

	/**
	 * Push the current URL parameters to the history stack
	 * and change the visible URL.
	 * Note: this includes a workaround for IE8/IE9 that uses
	 * the hash part instead of the search part.
	 *
	 * @param params to append to the URL, can be either a string
	 * or a map
	 */
	pushState: function(params) {
		var strParams;
		if (typeof(params) === 'string') {
			strParams = params;
		}
		else {
			strParams = OC.buildQueryString(params);
		}
		if (window.history.pushState) {
			var url = location.pathname + '?' + strParams;
			window.history.pushState(params, '', url);
		}
		// use URL hash for IE8
		else {
			window.location.hash = '?' + strParams;
			// inhibit next onhashchange that just added itself
			// to the event queue
			this._cancelPop = true;
		}
	},

	/**
	 * Add a popstate handler
	 *
	 * @param handler function
	 */
	addOnPopStateHandler: function(handler) {
		this._handlers.push(handler);
	},

	/**
	 * Parse a query string from the hash part of the URL.
	 * (workaround for IE8 / IE9)
	 */
	_parseHashQuery: function() {
		var hash = window.location.hash,
			pos = hash.indexOf('?');
		if (pos >= 0) {
			return hash.substr(pos + 1);
		}
		if (hash.length) {
			// remove hash sign
			return hash.substr(1);
		}
		return '';
	},

	_decodeQuery: function(query) {
		return query.replace(/\+/g, ' ');
	},

	/**
	 * Parse the query/search part of the URL.
	 * Also try and parse it from the URL hash (for IE8)
	 *
	 * @return map of parameters
	 */
	parseUrlQuery: function() {
		var query = this._parseHashQuery(),
			params;
		// try and parse from URL hash first
		if (query) {
			params = OC.parseQueryString(this._decodeQuery(query));
		}
		// else read from query attributes
		if (!params) {
			params = OC.parseQueryString(this._decodeQuery(location.search));
		}
		return params || {};
	},

	_onPopState: function(e) {
		if (this._cancelPop) {
			this._cancelPop = false;
			return;
		}
		var params;
		if (!this._handlers.length) {
			return;
		}
		params = (e && e.state) || this.parseUrlQuery() || {};
		for (var i = 0; i < this._handlers.length; i++) {
			this._handlers[i](params);
		}
	}
};

// fallback to hashchange when no history support
if (window.history.pushState) {
	window.onpopstate = _.bind(OC.Util.History._onPopState, OC.Util.History);
}
else {
	$(window).on('hashchange', _.bind(OC.Util.History._onPopState, OC.Util.History));
}

/**
 * Get a variable by name
 * @param {string} name
 * @return {*}
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
 * Set a variable by name
 * @param {string} name
 * @param {*} value
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
 * Namespace for apps
 * @namespace OCA
 */
window.OCA = {};

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
