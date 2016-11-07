/**
 * Disable console output unless DEBUG mode is enabled.
 * Add
 *      'debug' => true,
 * To the definition of $CONFIG in config/config.php to enable debug mode.
 * The undefined checks fix the broken ie8 console
 */

/* global oc_isadmin */

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
	/**
	 * Relative path to ownCloud root.
	 * For example: "/owncloud"
	 *
	 * @type string
	 *
	 * @deprecated since 8.2, use OC.getRootPath() instead
	 * @see OC#getRootPath
	 */
	webroot:oc_webroot,

	appswebroots:(typeof oc_appswebroots !== 'undefined') ? oc_appswebroots:false,
	/**
	 * Currently logged in user or null if none
	 *
	 * @type String
	 * @deprecated use {@link OC.getCurrentUser} instead
	 */
	currentUser:(typeof oc_current_user!=='undefined')?oc_current_user:false,
	config: window.oc_config,
	appConfig: window.oc_appconfig || {},
	theme: window.oc_defaults || {},
	coreApps:['', 'admin','log','core/search','settings','core','3rdparty'],
	requestToken: oc_requesttoken,
	menuSpeed: 50,

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
	 * @param {int} version OCS API version
	 * @return {string} OCS API base path
	 */
	linkToOCS: function(service, version) {
		version = (version !== 2) ? 1 : 2;
		return window.location.protocol + '//' + window.location.host + OC.webroot + '/ocs/v' + version + '.php/' + service + '/';
	},

	/**
	 * Generates the absolute url for the given relative url, which can contain parameters.
	 * Parameters will be URL encoded automatically.
	 * @param {string} url
	 * @param [params] params
	 * @param [options] options
	 * @param {bool} [options.escape=true] enable/disable auto escape of placeholders (by default enabled)
	 * @return {string} Absolute URL for the given relative URL
	 */
	generateUrl: function(url, params, options) {
		var defaultOptions = {
				escape: true
			},
			allOptions = options || {};
		_.defaults(allOptions, defaultOptions);

		var _build = function (text, vars) {
			var vars = vars || [];
			return text.replace(/{([^{}]*)}/g,
				function (a, b) {
					var r = (vars[b]);
					if(allOptions.escape) {
						return (typeof r === 'string' || typeof r === 'number') ? encodeURIComponent(r) : encodeURIComponent(a);
					} else {
						return (typeof r === 'string' || typeof r === 'number') ? r : a;
					}
				}
			);
		};
		if (url.charAt(0) !== '/') {
			url = '/' + url;

		}

		if(oc_config.modRewriteWorking == true) {
			return OC.webroot + _build(url, params);
		}

		return OC.webroot + '/index.php' + _build(url, params);
	},

	/**
	 * Get the absolute url for a file in an app
	 * @param {string} app the id of the app
	 * @param {string} type the type of the file to link to (e.g. css,img,ajax.template)
	 * @param {string} file the filename
	 * @return {string} Absolute URL for a file in an app
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
	 * Reloads the current page
	 */
	reload: function() {
		window.location.reload();
	},

	/**
	 * Protocol that is used to access this ownCloud instance
	 * @return {string} Used protocol
	 */
	getProtocol: function() {
		return window.location.protocol.split(':')[0];
	},

	/**
	 * Returns the host used to access this ownCloud instance
	 * Host is sometimes the same as the hostname but now always.
	 *
	 * Examples:
	 * http://example.com => example.com
	 * https://example.com => example.com
	 * http://example.com:8080 => example.com:8080
	 *
	 * @return {string} host
	 *
	 * @since 8.2
	 */
	getHost: function() {
		return window.location.host;
	},

	/**
	 * Returns the hostname used to access this ownCloud instance
	 * The hostname is always stripped of the port
	 *
	 * @return {string} hostname
	 * @since 9.0
	 */
	getHostName: function() {
		return window.location.hostname;
	},

	/**
	 * Returns the port number used to access this ownCloud instance
	 *
	 * @return {int} port number
	 *
	 * @since 8.2
	 */
	getPort: function() {
		return window.location.port;
	},

	/**
	 * Returns the web root path where this ownCloud instance
	 * is accessible, with a leading slash.
	 * For example "/owncloud".
	 *
	 * @return {string} web root path
	 *
	 * @since 8.2
	 */
	getRootPath: function() {
		return OC.webroot;
	},

	/**
	 * Returns the currently logged in user or null if there is no logged in
	 * user (public page mode)
	 *
	 * @return {OC.CurrentUser} user spec
	 * @since 9.0.0
	 */
	getCurrentUser: function() {
		if (_.isUndefined(this._currentUserDisplayName)) {
			this._currentUserDisplayName = document.getElementsByTagName('head')[0].getAttribute('data-user-displayname');
		}
		return {
			uid: this.currentUser,
			displayName: this._currentUserDisplayName
		};
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
		if(file.indexOf('.')==-1){//if no extension is given, use svg
			file+='.svg';
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
	 * Join path sections
	 *
	 * @param {...String} path sections
	 *
	 * @return {String} joined path, any leading or trailing slash
	 * will be kept
	 *
	 * @since 8.2
	 */
	joinPaths: function() {
		if (arguments.length < 1) {
			return '';
		}
		var path = '';
		// convert to array
		var args = Array.prototype.slice.call(arguments);
		// discard empty arguments
		args = _.filter(args, function(arg) {
			return arg.length > 0;
		});
		if (args.length < 1) {
			return '';
		}

		var lastArg = args[args.length - 1];
		var leadingSlash = args[0].charAt(0) === '/';
		var trailingSlash = lastArg.charAt(lastArg.length - 1) === '/';
		var sections = [];
		var i;
		for (i = 0; i < args.length; i++) {
			sections = sections.concat(args[i].split('/'));
		}
		var first = !leadingSlash;
		for (i = 0; i < sections.length; i++) {
			if (sections[i] !== '') {
				if (first) {
					first = false;
				} else {
					path += '/';
				}
				path += sections[i];
			}
		}

		if (trailingSlash) {
			// add it back
			path += '/';
		}
		return path;
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
					popup.prepend('<span class="arrow '+arrowclass+'"></span><h2>'+t('core', 'Settings')+'</h2><a class="close"></a>').show();
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
	},

	/**
	 * For menu toggling
	 * @todo Write documentation
	 */
	registerMenu: function($toggle, $menuEl) {
		var self = this;
		$menuEl.addClass('menu');
		$toggle.on('click.menu', function(event) {
			// prevent the link event (append anchor to URL)
			event.preventDefault();

			if ($menuEl.is(OC._currentMenu)) {
				self.hideMenus();
				return;
			}
			// another menu was open?
			else if (OC._currentMenu) {
				// close it
				self.hideMenus();
			}
			$menuEl.slideToggle(OC.menuSpeed);
			OC._currentMenu = $menuEl;
			OC._currentMenuToggle = $toggle;
		});
	},

	/**
	 *  @todo Write documentation
	 */
	unregisterMenu: function($toggle, $menuEl) {
		// close menu if opened
		if ($menuEl.is(OC._currentMenu)) {
			this.hideMenus();
		}
		$toggle.off('click.menu').removeClass('menutoggle');
		$menuEl.removeClass('menu');
	},

	/**
	 * Hides any open menus
	 *
	 * @param {Function} complete callback when the hiding animation is done
	 */
	hideMenus: function(complete) {
		if (OC._currentMenu) {
			var lastMenu = OC._currentMenu;
			OC._currentMenu.trigger(new $.Event('beforeHide'));
			OC._currentMenu.slideUp(OC.menuSpeed, function() {
				lastMenu.trigger(new $.Event('afterHide'));
				if (complete) {
					complete.apply(this, arguments);
				}
			});
		}
		OC._currentMenu = null;
		OC._currentMenuToggle = null;
	},

	/**
	 * Shows a given element as menu
	 *
	 * @param {Object} [$toggle=null] menu toggle
	 * @param {Object} $menuEl menu element
	 * @param {Function} complete callback when the showing animation is done
	 */
	showMenu: function($toggle, $menuEl, complete) {
		if ($menuEl.is(OC._currentMenu)) {
			return;
		}
		this.hideMenus();
		OC._currentMenu = $menuEl;
		OC._currentMenuToggle = $toggle;
		$menuEl.trigger(new $.Event('beforeShow'));
		$menuEl.show();
		$menuEl.trigger(new $.Event('afterShow'));
		// no animation
		if (_.isFunction(complete)) {
			complete();
		}
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
	},

	/**
	 * Returns whether the current user is an administrator
	 *
	 * @return {bool} true if the user is an admin, false otherwise
	 * @since 9.0.0
	 */
	isUserAdmin: function() {
		return oc_isadmin;
	},

	/**
	 * Process ajax error, redirects to main page
	 * if an error/auth error status was returned.
	 */
	_processAjaxError: function(xhr) {
		var self = this;
		// purposefully aborted request ?
		// this._userIsNavigatingAway needed to distinguish ajax calls cancelled by navigating away
		// from calls cancelled by failed cross-domain ajax due to SSO redirect
		if (xhr.status === 0 && (xhr.statusText === 'abort' || xhr.statusText === 'timeout' || self._reloadCalled)) {
			return;
		}

		if (_.contains([0, 302, 303, 307, 401], xhr.status)) {
			// sometimes "beforeunload" happens later, so need to defer the reload a bit
			setTimeout(function() {
				if (!self._userIsNavigatingAway && !self._reloadCalled) {
					OC.Notification.show(t('core', 'Problem loading page, reloading in 5 seconds'));
					setTimeout(OC.reload, 5000);
					// only call reload once
					self._reloadCalled = true;
				}
			}, 100);
		}
	},

	/**
	 * Registers XmlHttpRequest object for global error processing.
	 *
	 * This means that if this XHR object returns 401 or session timeout errors,
	 * the current page will automatically be reloaded.
	 *
	 * @param {XMLHttpRequest} xhr
	 */
	registerXHRForErrorProcessing: function(xhr) {
		var loadCallback = function() {
			if (xhr.readyState !== 4) {
				return;
			}

			if (xhr.status >= 200 && xhr.status < 300 || xhr.status === 304) {
				return;
			}

			// fire jquery global ajax error handler
			$(document).trigger(new $.Event('ajaxError'), xhr);
		};

		var errorCallback = function() {
			// fire jquery global ajax error handler
			$(document).trigger(new $.Event('ajaxError'), xhr);
		};

		if (xhr.addEventListener) {
			xhr.addEventListener('load', loadCallback);
			xhr.addEventListener('error', errorCallback);
		}

	}
};

/**
 * Current user attributes
 *
 * @typedef {Object} OC.CurrentUser
 *
 * @property {String} uid user id
 * @property {String} displayName display name
 */

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
 * A little class to manage a status field for a "saving" process.
 * It can be used to display a starting message (e.g. "Saving...") and then
 * replace it with a green success message or a red error message.
 *
 * @namespace OC.msg
 */
OC.msg = {
	/**
	 * Displayes a "Saving..." message in the given message placeholder
	 *
	 * @param {Object} selector	Placeholder to display the message in
	 */
	startSaving: function(selector) {
		this.startAction(selector, t('core', 'Saving...'));
	},

	/**
	 * Displayes a custom message in the given message placeholder
	 *
	 * @param {Object} selector	Placeholder to display the message in
	 * @param {string} message	Plain text message to display (no HTML allowed)
	 */
	startAction: function(selector, message) {
		$(selector).text(message)
			.removeClass('success')
			.removeClass('error')
			.stop(true, true)
			.show();
	},

	/**
	 * Displayes an success/error message in the given selector
	 *
	 * @param {Object} selector	Placeholder to display the message in
	 * @param {Object} response	Response of the server
	 * @param {Object} response.data	Data of the servers response
	 * @param {string} response.data.message	Plain text message to display (no HTML allowed)
	 * @param {string} response.status	is being used to decide whether the message
	 * is displayed as an error/success
	 */
	finishedSaving: function(selector, response) {
		this.finishedAction(selector, response);
	},

	/**
	 * Displayes an success/error message in the given selector
	 *
	 * @param {Object} selector	Placeholder to display the message in
	 * @param {Object} response	Response of the server
	 * @param {Object} response.data Data of the servers response
	 * @param {string} response.data.message Plain text message to display (no HTML allowed)
	 * @param {string} response.status is being used to decide whether the message
	 * is displayed as an error/success
	 */
	finishedAction: function(selector, response) {
		if (response.status === "success") {
			this.finishedSuccess(selector, response.data.message);
		} else {
			this.finishedError(selector, response.data.message);
		}
	},

	/**
	 * Displayes an success message in the given selector
	 *
	 * @param {Object} selector Placeholder to display the message in
	 * @param {string} message Plain text success message to display (no HTML allowed)
	 */
	finishedSuccess: function(selector, message) {
		$(selector).text(message)
			.addClass('success')
			.removeClass('error')
			.stop(true, true)
			.delay(3000)
			.fadeOut(900)
			.show();
	},

	/**
	 * Displayes an error message in the given selector
	 *
	 * @param {Object} selector Placeholder to display the message in
	 * @param {string} message Plain text error message to display (no HTML allowed)
	 */
	finishedError: function(selector, message) {
		$(selector).text(message)
			.addClass('error')
			.removeClass('success')
			.show();
	}
};

/**
 * @todo Write documentation
 * @namespace
 */
OC.Notification={
	queuedNotifications: [],
	getDefaultNotificationFunction: null,

	/**
	 * @type Array.<int> array of notification timers
	 */
	notificationTimers: [],

	/**
	 * @param callback
	 * @todo Write documentation
	 */
	setDefault: function(callback) {
		OC.Notification.getDefaultNotificationFunction = callback;
	},

	/**
	 * Hides a notification.
	 *
	 * If a row is given, only hide that one.
	 * If no row is given, hide all notifications.
	 *
	 * @param {jQuery} [$row] notification row
	 * @param {Function} [callback] callback
	 */
	hide: function($row, callback) {
		var self = this;
		var $notification = $('#notification');

		if (_.isFunction($row)) {
			// first arg is the callback
			callback = $row;
			$row = undefined;
		}

		if (!$row) {
			console.warn('Missing argument $row in OC.Notification.hide() call, caller needs to be adjusted to only dismiss its own notification');
			// assume that the row to be hidden is the first one
			$row = $notification.find('.row:first');
		}

		if ($row && $notification.find('.row').length > 1) {
			// remove the row directly
			$row.remove();
			if (callback) {
				callback.call();
			}
			return;
		}

		_.defer(function() {
			// fade out is supposed to only fade when there is a single row
			// however, some code might call hide() and show() directly after,
			// which results in more than one element
			// in this case, simply delete that one element that was supposed to
			// fade out
			//
			// FIXME: remove once all callers are adjusted to only hide their own notifications
			if ($notification.find('.row').length > 1) {
				$row.remove();
				return;
			}

			// else, fade out whatever was present
			$notification.fadeOut('400', function(){
				if (self.isHidden()) {
					if (self.getDefaultNotificationFunction) {
						self.getDefaultNotificationFunction.call();
					}
				}
				if (callback) {
					callback.call();
				}
				$notification.empty();
			});
		});
	},

	/**
	 * Shows a notification as HTML without being sanitized before.
	 * If you pass unsanitized user input this may lead to a XSS vulnerability.
	 * Consider using show() instead of showHTML()
	 *
	 * @param {string} html Message to display
	 * @param {Object} [options] options
	 * @param {string] [options.type] notification type
	 * @param {int} [options.timeout=0] timeout value, defaults to 0 (permanent)
	 * @return {jQuery} jQuery element for notification row
	 */
	showHtml: function(html, options) {
		options = options || {};
		_.defaults(options, {
			timeout: 0
		});

		var self = this;
		var $notification = $('#notification');
		if (this.isHidden()) {
			$notification.fadeIn().css('display','inline-block');
		}
		var $row = $('<div class="row"></div>');
		if (options.type) {
			$row.addClass('type-' + options.type);
		}
		if (options.type === 'error') {
			// add a close button
			var $closeButton = $('<a class="action close icon-close" href="#"></a>');
			$closeButton.attr('alt', t('core', 'Dismiss'));
			$row.append($closeButton);
			$closeButton.one('click', function() {
				self.hide($row);
				return false;
			});
			$row.addClass('closeable');
		}

		$row.prepend(html);
		$notification.append($row);

		if(options.timeout > 0) {
			// register timeout to vanish notification
			this.notificationTimers.push(setTimeout(function() {
				self.hide($row);
			}, (options.timeout * 1000)));
		}

		return $row;
	},

	/**
	 * Shows a sanitized notification
	 *
	 * @param {string} text Message to display
	 * @param {Object} [options] options
	 * @param {string} [options.type] notification type
	 * @param {int} [options.timeout=0] timeout value, defaults to 0 (permanent)
	 * @return {jQuery} jQuery element for notification row
	 */
	show: function(text, options) {
		return this.showHtml($('<div/>').text(text).html(), options);
	},

	/**
	 * Shows a notification that disappears after x seconds, default is
	 * 7 seconds
	 *
	 * @param {string} text Message to show
	 * @param {array} [options] options array
	 * @param {int} [options.timeout=7] timeout in seconds, if this is 0 it will show the message permanently
	 * @param {boolean} [options.isHTML=false] an indicator for HTML notifications (true) or text (false)
	 * @param {string} [options.type] notification type
	 */
	showTemporary: function(text, options) {
		var self = this;
		var defaults = {
			isHTML: false,
			timeout: 7
		};
		options = options || {};
		// merge defaults with passed in options
		_.defaults(options, defaults);

		var $row;
		if(options.isHTML) {
			$row = this.showHtml(text, options);
		} else {
			$row = this.show(text, options);
		}
		return $row;
	},

	/**
	 * Returns whether a notification is hidden.
	 * @return {boolean}
	 */
	isHidden: function() {
		return !$("#notification").find('.row').length;
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
	 * Disable automatic evaluation of responses for $.ajax() functions (and its
	 * higher-level alternatives like $.get() and $.post()).
	 *
	 * If a response to a $.ajax() request returns a content type of "application/javascript"
	 * JQuery would previously execute the response body. This is a pretty unexpected
	 * behaviour and can result in a bypass of our Content-Security-Policy as well as
	 * multiple unexpected XSS vectors.
	 */
	$.ajaxSetup({
		contents: {
			script: false
		}
	});

	/**
	 * Set users locale to moment.js as soon as possible
	 */
	moment.locale(OC.getLocale());

	var userAgent = window.navigator.userAgent;
	var msie = userAgent.indexOf('MSIE ');
	var trident = userAgent.indexOf('Trident/');
	var edge = userAgent.indexOf('Edge/');

	if (msie > 0 || trident > 0) {
		// (IE 10 or older) || IE 11
		$('html').addClass('ie');
	} else if (edge > 0) {
		// for edge
		$('html').addClass('edge');
	}

	$(window).on('unload.main', function() {
		OC._unloadCalled = true;
	});
	$(window).on('beforeunload.main', function() {
		// super-trick thanks to http://stackoverflow.com/a/4651049
		// in case another handler displays a confirmation dialog (ex: navigating away
		// during an upload), there are two possible outcomes: user clicked "ok" or
		// "cancel"

		// first timeout handler is called after unload dialog is closed
		setTimeout(function() {
			OC._userIsNavigatingAway = true;

			// second timeout event is only called if user cancelled (Chrome),
			// but in other browsers it might still be triggered, so need to
			// set a higher delay...
			setTimeout(function() {
				if (!OC._unloadCalled) {
					OC._userIsNavigatingAway = false;
				}
			}, 10000);
		},1);
	});
	$(document).on('ajaxError.main', function( event, request, settings ) {
		if (settings && settings.allowAuthErrors) {
			return;
		}
		OC._processAjaxError(request);
	});

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
		var heartBeatTimeout = null;
		var heartBeat = function() {
			clearTimeout(heartBeatTimeout);
			heartBeatTimeout = setInterval(function() {
				$.post(url);
			}, interval * 1000);
		};
		$(document).ajaxComplete(heartBeat);
		heartBeat();
	}

	// session heartbeat (defaults to enabled)
	if (typeof(oc_config.session_keepalive) === 'undefined' ||
		!!oc_config.session_keepalive) {

		initSessionHeartBeat();
	}

	OC.registerMenu($('#expand'), $('#expanddiv'));

	// toggle for menus
	$(document).on('mouseup.closemenus', function(event) {
		var $el = $(event.target);
		if ($el.closest('.menu').length || $el.closest('.menutoggle').length) {
			// don't close when clicking on the menu directly or a menu toggle
			return false;
		}

		OC.hideMenus();
	});

	/**
	 * Set up the main menu toggle to react to media query changes.
	 * If the screen is small enough, the main menu becomes a toggle.
	 * If the screen is bigger, the main menu is not a toggle any more.
	 */
	function setupMainMenu() {
		// toggle the navigation
		var $toggle = $('#header .header-appname-container');
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
			} else {
				// Close navigation when opening app in
				// a new tab
				OC.hideMenus();
			}
		});
	}

	function setupUserMenu() {
		var $menu = $('#header #settings');

		$menu.delegate('a', 'click', function(event) {
			var $page = $(event.target);
			if (!$page.is('a')) {
				$page = $page.closest('a');
			}
			$page.find('img').remove();
			$page.find('div').remove(); // prevent odd double-clicks
			$page.prepend($('<div/>').addClass('icon-loading-small-dark'));
		});
	}

	setupMainMenu();
	setupUserMenu();

	// move triangle of apps dropdown to align with app name triangle
	// 2 is the additional offset between the triangles
	if($('#navigation').length) {
		$('#header #owncloud + .menutoggle').one('click', function(){
			var caretPosition = $('.header-appname + .icon-caret').offset().left - 2;
			if(caretPosition > 255) {
				// if the app name is longer than the menu, just put the triangle in the middle
				return;
			} else {
				$('head').append('<style>#navigation:after { left: '+ caretPosition +'px; }</style>');
			}
		});
	}

	// just add snapper for logged in users
	if($('#app-navigation').length && !$('html').hasClass('lte9')) {

		// App sidebar on mobile
		var snapper = new Snap({
			element: document.getElementById('app-content'),
			disable: 'right',
			maxPosition: 250,
			minDragDistance: 100
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
		$appNavigation.delegate('a, :button', 'click', function(event) {
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

		// adjust controls bar width
		var adjustControlsWidth = function() {
			if($('#controls').length) {
				var controlsWidth;
				// if there is a scrollbar …
				if($('#app-content').get(0).scrollHeight > $('#app-content').height()) {
					if($(window).width() > 768) {
						controlsWidth = $('#content').width() - $('#app-navigation').width() - getScrollBarWidth();
						if (!$('#app-sidebar').hasClass('hidden') && !$('#app-sidebar').hasClass('disappear')) {
							controlsWidth -= $('#app-sidebar').width();
						}
					} else {
						controlsWidth = $('#content').width() - getScrollBarWidth();
					}
				} else { // if there is none
					if($(window).width() > 768) {
						controlsWidth = $('#content').width() - $('#app-navigation').width();
						if (!$('#app-sidebar').hasClass('hidden') && !$('#app-sidebar').hasClass('disappear')) {
							controlsWidth -= $('#app-sidebar').width();
						}
					} else {
						controlsWidth = $('#content').width();
					}
				}
				$('#controls').css('width', controlsWidth);
				$('#controls').css('min-width', controlsWidth);
			}
		};

		$(window).resize(_.debounce(adjustControlsWidth, 250));

		$('body').delegate('#app-content', 'apprendered appresized', adjustControlsWidth);

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
	var humanList = ['B', 'KB', 'MB', 'GB', 'TB'];
	// Calculate Log with base 1024: size = 1024 ** order
	var order = size > 0 ? Math.floor(Math.log(size) / Math.log(1024)) : 0;
	// Stay in range of the byte sizes that are defined
	order = Math.min(humanList.length - 1, order);
	var readableFormat = humanList[order];
	var relativeSize = (size / Math.pow(1024, order)).toFixed(1);
	if(skipSmallSizes === true && order === 0) {
		if(relativeSize !== "0.0"){
			return '< 1 KB';
		} else {
			return '0 KB';
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
		format = format || "LLL";
		return moment(timestamp).format(format);
	},

	/**
	 * @param timestamp
	 * @returns {string} human readable difference from now
	 */
	relativeModifiedDate: function (timestamp) {
		var diff = moment().diff(moment(timestamp));
		if (diff >= 0 && diff < 45000 ) {
			return t('core', 'seconds ago');
		}
		return moment(timestamp).fromNow();
	},
	/**
	 * Returns whether the browser supports SVG
	 * @deprecated SVG is always supported (since 9.0)
	 * @return {boolean} true if the browser supports SVG, false otherwise
	 */
	hasSVGSupport: function(){
		return true
	},
	/**
	 * If SVG is not supported, replaces the given icon's extension
	 * from ".svg" to ".png".
	 * If SVG is supported, return the image path as is.
	 * @param {string} file image path with svg extension
	 * @deprecated SVG is always supported (since 9.0)
	 * @return {string} fixed image path with png extension if SVG is not supported
	 */
	replaceSVGIcon: function(file) {
		return file;
	},
	/**
	 * Replace SVG images in all elements that have the "svg" class set
	 * with PNG images.
	 *
	 * @param $el root element from which to search, defaults to $('body')
	 * @deprecated SVG is always supported (since 9.0)
	 */
	replaceSVG: function($el) {},

	/**
	 * Fix image scaling for IE8, since background-size is not supported.
	 *
	 * This scales the image to the element's actual size, the URL is
	 * taken from the "background-image" CSS attribute.
	 *
	 * @deprecated IE8 isn't supported since 9.0
	 * @param {Object} $el image element
	 */
	scaleFixForIE8: function($el) {},

	/**
	 * Returns whether this is IE
	 *
	 * @return {bool} true if this is IE, false otherwise
	 */
	isIE: function() {
		return $('html').hasClass('ie');
	},

	/**
	 * Returns whether this is IE8
	 *
	 * @deprecated IE8 isn't supported since 9.0
	 * @return {bool} false (IE8 isn't supported anymore)
	 */
	isIE8: function() {
		return false;
	},

	/**
	 * Returns the width of a generic browser scrollbar
	 *
	 * @return {int} width of scrollbar
	 */
	getScrollBarWidth: function() {
		if (this._scrollBarWidth) {
			return this._scrollBarWidth;
		}

		var inner = document.createElement('p');
		inner.style.width = "100%";
		inner.style.height = "200px";

		var outer = document.createElement('div');
		outer.style.position = "absolute";
		outer.style.top = "0px";
		outer.style.left = "0px";
		outer.style.visibility = "hidden";
		outer.style.width = "200px";
		outer.style.height = "150px";
		outer.style.overflow = "hidden";
		outer.appendChild (inner);

		document.body.appendChild (outer);
		var w1 = inner.offsetWidth;
		outer.style.overflow = 'scroll';
		var w2 = inner.offsetWidth;
		if(w1 === w2) {
			w2 = outer.clientWidth;
		}

		document.body.removeChild (outer);

		this._scrollBarWidth = (w1 - w2);

		return this._scrollBarWidth;
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
	},
	/**
	 * Calls the callback in a given interval until it returns true
	 * @param {function} callback
	 * @param {integer} interval in milliseconds
	 */
	waitFor: function(callback, interval) {
		var internalCallback = function() {
			if(callback() !== true) {
				setTimeout(internalCallback, interval);
			}
		};

		internalCallback();
	},
	/**
	 * Checks if a cookie with the given name is present and is set to the provided value.
	 * @param {string} name name of the cookie
	 * @param {string} value value of the cookie
	 * @return {boolean} true if the cookie with the given name has the given value
	 */
	isCookieSetToValue: function(name, value) {
		var cookies = document.cookie.split(';');
		for (var i=0; i < cookies.length; i++) {
			var cookie = cookies[i].split('=');
			if (cookie[0].trim() === name && cookie[1].trim() === value) {
				return true;
			}
		}
		return false;
	}
};

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
	 * @param {boolean} [replace=false] whether to replace instead of pushing
	 */
	_pushState: function(params, replace) {
		var strParams;
		if (typeof(params) === 'string') {
			strParams = params;
		}
		else {
			strParams = OC.buildQueryString(params);
		}
		if (window.history.pushState) {
			var url = location.pathname + '?' + strParams;
			// Workaround for bug with SVG and window.history.pushState on Firefox < 51
			// https://bugzilla.mozilla.org/show_bug.cgi?id=652991
			var isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
			if (isFirefox && parseInt(navigator.userAgent.split('/').pop()) < 51) {
				var patterns = document.querySelectorAll('[fill^="url(#"], [stroke^="url(#"], [filter^="url(#invert"]');
				for (var i = 0, ii = patterns.length, pattern; i < ii; i++) {
					pattern = patterns[i];
					pattern.style.fill = pattern.style.fill;
					pattern.style.stroke = pattern.style.stroke;
					pattern.removeAttribute("filter");
					pattern.setAttribute("filter", "url(#invert)");
				}
			}
			if (replace) {
				window.history.replaceState(params, '', url);
			} else {
				window.history.pushState(params, '', url);
			}
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
	 * Push the current URL parameters to the history stack
	 * and change the visible URL.
	 * Note: this includes a workaround for IE8/IE9 that uses
	 * the hash part instead of the search part.
	 *
	 * @param params to append to the URL, can be either a string
	 * or a map
	 */
	pushState: function(params) {
		return this._pushState(params, false);
	},

	/**
	 * Push the current URL parameters to the history stack
	 * and change the visible URL.
	 * Note: this includes a workaround for IE8/IE9 that uses
	 * the hash part instead of the search part.
	 *
	 * @param params to append to the URL, can be either a string
	 * or a map
	 */
	replaceState: function(params) {
		return this._pushState(params, true);
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
		params = _.extend(params || {}, OC.parseQueryString(this._decodeQuery(location.search)));
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
		params = (e && e.state);
		if (_.isString(params)) {
			params = OC.parseQueryString(params);
		} else if (!params) {
			params = this.parseUrlQuery() || {};
		}
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

/**
 * @deprecated use OC.Util.getScrollBarWidth() instead
 */
function getScrollBarWidth() {
	return OC.Util.getScrollBarWidth();
}

/**
 * jQuery tipsy shim for the bootstrap tooltip
 */
jQuery.fn.tipsy = function(argument) {
	console.warn('Deprecation warning: tipsy is deprecated. Use tooltip instead.');
	if(typeof argument === 'object' && argument !== null) {

		// tipsy defaults
		var options = {
			placement: 'bottom',
			delay: { 'show': 0, 'hide': 0},
			trigger: 'hover',
			html: false,
			container: 'body'
		};
		if(argument.gravity) {
			switch(argument.gravity) {
				case 'n':
				case 'nw':
				case 'ne':
					options.placement='bottom';
					break;
				case 's':
				case 'sw':
				case 'se':
					options.placement='top';
					break;
				case 'w':
					options.placement='right';
					break;
				case 'e':
					options.placement='left';
					break;
			}
		}
		if(argument.trigger) {
			options.trigger = argument.trigger;
		}
		if(argument.delayIn) {
			options.delay["show"] = argument.delayIn;
		}
		if(argument.delayOut) {
			options.delay["hide"] = argument.delayOut;
		}
		if(argument.html) {
			options.html = true;
		}
		if(argument.fallback) {
			options.title = argument.fallback;
		}
		// destroy old tooltip in case the title has changed
		jQuery.fn.tooltip.call(this, 'destroy');
		jQuery.fn.tooltip.call(this, options);
	} else {
		this.tooltip(argument);
		jQuery.fn.tooltip.call(this, argument);
	}
	return this;
}
