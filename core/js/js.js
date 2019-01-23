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
if (typeof console === "undefined" || typeof console.log === "undefined") {
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

/** @namespace OCP */
var OCP = {},
	/**
	 * @namespace OC
	 */
	OC = {
	PERMISSION_NONE:0,
	PERMISSION_CREATE:4,
	PERMISSION_READ:1,
	PERMISSION_UPDATE:2,
	PERMISSION_DELETE:8,
	PERMISSION_SHARE:16,
	PERMISSION_ALL:31,
	TAG_FAVORITE: '_$!<Favorite>!$_',
	/* jshint camelcase: false */
	/**
	 * Relative path to Nextcloud root.
	 * For example: "/nextcloud"
	 *
	 * @type string
	 *
	 * @deprecated since 8.2, use OC.getRootPath() instead
	 * @see OC#getRootPath
	 */
	webroot:oc_webroot,

	/**
	 * Capabilities
	 *
	 * @type array
	 */
	_capabilities: window.oc_capabilities || null,

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
		return OC.getRootPath() + '/remote.php/' + service;
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
		return window.location.protocol + '//' + window.location.host + OC.getRootPath() + '/ocs/v' + version + '.php/' + service + '/';
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
			vars = vars || [];
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
			return OC.getRootPath() + _build(url, params);
		}

		return OC.getRootPath() + '/index.php' + _build(url, params);
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
			link=OC.getRootPath();
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
	 * Check if a user file is allowed to be handled.
	 * @param {string} file to check
	 */
	fileIsBlacklisted: function(file) {
		return !!(file.match(oc_config.blacklist_files_regex));
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
	 * Protocol that is used to access this Nextcloud instance
	 * @return {string} Used protocol
	 */
	getProtocol: function() {
		return window.location.protocol.split(':')[0];
	},

	/**
	 * Returns the host used to access this Nextcloud instance
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
	 * Returns the hostname used to access this Nextcloud instance
	 * The hostname is always stripped of the port
	 *
	 * @return {string} hostname
	 * @since 9.0
	 */
	getHostName: function() {
		return window.location.hostname;
	},

	/**
	 * Returns the port number used to access this Nextcloud instance
	 *
	 * @return {int} port number
	 *
	 * @since 8.2
	 */
	getPort: function() {
		return window.location.port;
	},

	/**
	 * Returns the web root path where this Nextcloud instance
	 * is accessible, with a leading slash.
	 * For example "/nextcloud".
	 *
	 * @return {string} web root path
	 *
	 * @since 8.2
	 */
	getRootPath: function() {
		return OC.webroot;
	},


	/**
	 * Returns the capabilities
	 *
	 * @return {array} capabilities
	 *
	 * @since 14.0
	 */
	getCapabilities: function() {
		return OC._capabilities;
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
		if(!OC.addScript.loaded[path]) {
			deferred = $.Deferred();
			$.getScript(path, function() {
				deferred.resolve();
			});
			OC.addScript.loaded[path] = deferred;
		} else {
			if (ready) {
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
	 * Returns whether the given paths are the same, without
	 * leading, trailing or doubled slashes and also removing
	 * the dot sections.
	 *
	 * @param {String} path1 first path
	 * @param {String} path2 second path
	 * @return {bool} true if the paths are the same
	 *
	 * @since 9.0
	 */
	isSamePath: function(path1, path2) {
		var filterDot = function(p) {
			return p !== '.';
		};
		var pathSections1 = _.filter((path1 || '').split('/'), filterDot);
		var pathSections2 = _.filter((path2 || '').split('/'), filterDot);
		path1 = OC.joinPaths.apply(OC, pathSections1);
		path2 = OC.joinPaths.apply(OC, pathSections2);
		return path1 === path2;
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
	 *
	 * @param {jQuery} $toggle
	 * @param {jQuery} $menuEl
	 * @param {function|undefined} toggle callback invoked everytime the menu is opened
	 * @param {boolean} headerMenu is this a top right header menu?
	 * @returns {undefined}
	 */
	registerMenu: function($toggle, $menuEl, toggle, headerMenu) {
		var self = this;
		$menuEl.addClass('menu');

		// On link, the enter key trigger a click event
		// Only use the click to avoid two fired events
		$toggle.on($toggle.prop('tagName') === 'A'
			? 'click.menu'
			: 'click.menu keyup.menu', function(event) {
			// prevent the link event (append anchor to URL)
			event.preventDefault();

			// allow enter key as a trigger
			if (event.key && event.key !== "Enter") {
				return;
			}

			if ($menuEl.is(OC._currentMenu)) {
				self.hideMenus();
				return;
			}
			// another menu was open?
			else if (OC._currentMenu) {
				// close it
				self.hideMenus();
			}

			if (headerMenu === true) {
				$menuEl.parent().addClass('openedMenu');
			}

			// Set menu to expanded
			$toggle.attr('aria-expanded', true);

			$menuEl.slideToggle(OC.menuSpeed, toggle);
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

		// Set menu to closed
		$('.menutoggle').attr('aria-expanded', false);

		$('.openedMenu').removeClass('openedMenu');
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
	 * Returns the user's locale as a BCP 47 compliant language tag
	 *
	 * @return {String} locale string
	 */
	getCanonicalLocale: function() {
		var locale = this.getLocale();
		return typeof locale === 'string' ? locale.replace(/_/g, '-') : locale;
	},

	/**
	 * Returns the user's locale
	 *
	 * @return {String} locale string
	 */
	getLocale: function() {
		return $('html').data('locale');
	},

	/**
	 * Returns the user's language
	 *
	 * @returns {String} language string
	 */
	getLanguage: function () {
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
	 * Warn users that the connection to the server was lost temporarily
	 *
	 * This function is throttled to prevent stacked notfications.
	 * After 7sec the first notification is gone, then we can show another one
	 * if necessary.
	 */
	_ajaxConnectionLostHandler: _.throttle(function() {
		OC.Notification.showTemporary(t('core', 'Connection to server lost'));
	}, 7 * 1000, {trailing: false}),

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

		if (_.contains([302, 303, 307, 401], xhr.status) && OC.currentUser) {
			// sometimes "beforeunload" happens later, so need to defer the reload a bit
			setTimeout(function() {
				if (!self._userIsNavigatingAway && !self._reloadCalled) {
					var timer = 0;
					var seconds = 5;
					var interval = setInterval( function() {
						OC.Notification.showUpdate(n('core', 'Problem loading page, reloading in %n second', 'Problem loading page, reloading in %n seconds', seconds - timer));
						if (timer >= seconds) {
							clearInterval(interval);
							OC.reload();
						}
						timer++;
						}, 1000 // 1 second interval
					);

					// only call reload once
					self._reloadCalled = true;
				}
			}, 100);
		} else if(xhr.status === 0) {
			// Connection lost (e.g. WiFi disconnected or server is down)
			setTimeout(function() {
				if (!self._userIsNavigatingAway && !self._reloadCalled) {
					self._ajaxConnectionLostHandler();
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
	 * @type Array<int>
	 * @description array of notification timers
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
	 * @param {string} [options.type] notification type
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
	 * Updates (replaces) a sanitized notification.
	 *
	 * @param {string} text Message to display
	 * @return {jQuery} JQuery element for notificaiton row
	 */
	showUpdate: function(text) {
		var $notification = $('#notification');
		// sanitise
		var $html = $('<div/>').text(text).html();

		// new notification
		if (text && $notification.find('.row').length == 0) {
			return this.showHtml($html);
		}

		var $row = $('<div class="row"></div>').prepend($html);

		// just update html in notification
		$notification.html($row);

		return $row;
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
	 * Disable execution of eval in jQuery. We do require an allowed eval CSP
	 * configuration at the moment for handlebars et al. But for jQuery there is
	 * not much of a reason to execute JavaScript directly via eval.
	 *
	 * This thus mitigates some unexpected XSS vectors.
	 */
	jQuery.globalEval = function(){};

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

	// css variables fallback for IE
	if (msie > 0 || trident > 0) {
		cssVars();
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
	 * Calls the server periodically to ensure that session and CSRF
	 * token doesn't expire
	 */
	function initSessionHeartBeat() {
		// interval in seconds
		var interval = NaN;
		if (oc_config.session_lifetime) {
			interval = Math.floor(oc_config.session_lifetime / 2);
		}
		interval = isNaN(interval)? 900: interval;

		// minimum one minute
		interval = Math.max(60, interval);
		// max interval in seconds set to 24 hours
		interval = Math.min(24 * 3600, interval);

		var url = OC.generateUrl('/csrftoken');
		setInterval(function() {
			$.ajax(url).then(function(resp) {
				oc_requesttoken = resp.token;
				OC.requestToken = resp.token;
			}).fail(function(e) {
				console.error('session heartbeat failed', e);
			});
		}, interval * 1000);
	}

	// session heartbeat (defaults to enabled)
	if (typeof(oc_config.session_keepalive) === 'undefined' ||
		!!oc_config.session_keepalive) {

		initSessionHeartBeat();
	}

	OC.registerMenu($('#expand'), $('#expanddiv'), false, true);

	// toggle for menus
	//$(document).on('mouseup.closemenus keyup', function(event) {
	$(document).on('mouseup.closemenus', function(event) {

		// allow enter as a trigger
		// if (event.key && event.key !== "Enter") {
		// 	return;
		// }

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

		// init the more-apps menu
		OC.registerMenu($('#more-apps > a'), $('#navigation'));

		// toggle the navigation
		var $toggle = $('#header .header-appname-container');
		var $navigation = $('#navigation');
		var $appmenu = $('#appmenu');

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
			if(event.which === 1 && !event.ctrlKey && !event.metaKey) {
				$app.addClass('app-loading');
			} else {
				// Close navigation when opening app in
				// a new tab
				OC.hideMenus(function(){return false;});
			}
		});

		$navigation.delegate('a', 'mouseup', function(event) {
			if(event.which === 2) {
				// Close navigation when opening app in
				// a new tab via middle click
				OC.hideMenus(function(){return false;});
			}
		});

		$appmenu.delegate('a', 'click', function(event) {
			var $app = $(event.target);
			if(!$app.is('a')) {
				$app = $app.closest('a');
			}
			if(event.which === 1 && !event.ctrlKey && !event.metaKey) {
				$app.addClass('app-loading');
			} else {
				// Close navigation when opening app in
				// a new tab
				OC.hideMenus(function(){return false;});
			}
		});
	}

	function setupUserMenu() {
		var $menu = $('#header #settings');

		// show loading feedback
		$menu.delegate('a', 'click', function(event) {
			var $page = $(event.target);
			if (!$page.is('a')) {
				$page = $page.closest('a');
			}
			if(event.which === 1 && !event.ctrlKey && !event.metaKey) {
				$page.find('img').remove();
				$page.find('div').remove(); // prevent odd double-clicks
				$page.prepend($('<div/>').addClass('icon-loading-small'));
			} else {
				// Close navigation when opening menu entry in
				// a new tab
				OC.hideMenus(function(){return false;});
			}
		});

		$menu.delegate('a', 'mouseup', function(event) {
			if(event.which === 2) {
				// Close navigation when opening app in
				// a new tab via middle click
				OC.hideMenus(function(){return false;});
			}
		});
	}

	function setupContactsMenu() {
		new OC.ContactsMenu({
			el: $('#contactsmenu .menu'),
			trigger: $('#contactsmenu .menutoggle')
		});
	}

	setupMainMenu();
	setupUserMenu();
	setupContactsMenu();

	// move triangle of apps dropdown to align with app name triangle
	// 2 is the additional offset between the triangles
	if($('#navigation').length) {
		$('#header #nextcloud + .menutoggle').on('click', function(){
			$('#menu-css-helper').remove();
			var caretPosition = $('.header-appname + .icon-caret').offset().left - 2;
			if(caretPosition > 255) {
				// if the app name is longer than the menu, just put the triangle in the middle
				return;
			} else {
				$('head').append('<style id="menu-css-helper">#navigation:after { left: '+ caretPosition +'px; }</style>');
			}
		});
		$('#header #appmenu .menutoggle').on('click', function() {
			$('#appmenu').toggleClass('menu-open');
			if($('#appmenu').is(':visible')) {
				$('#menu-css-helper').remove();
			}
		});
	}

	var resizeMenu = function() {
		var appList = $('#appmenu li');
		var rightHeaderWidth = $('.header-right').outerWidth();
		var headerWidth = $('header').outerWidth();
		var usePercentualAppMenuLimit = 0.33;
		var minAppsDesktop = 8;
		var availableWidth =  headerWidth - $('#nextcloud').outerWidth() - (rightHeaderWidth > 210 ? rightHeaderWidth : 210)
		var isMobile = $(window).width() < 768;
		if (!isMobile) {
			availableWidth = availableWidth * usePercentualAppMenuLimit;
		}
		var appCount = Math.floor((availableWidth / $(appList).width()));
		if (isMobile && appCount > minAppsDesktop) {
			appCount = minAppsDesktop;
		}
		if (!isMobile && appCount < minAppsDesktop) {
			appCount = minAppsDesktop;
		}

		// show at least 2 apps in the popover
		if(appList.length-1-appCount >= 1) {
			appCount--;
		}

		$('#more-apps a').removeClass('active');
		var lastShownApp;
		for (var k = 0; k < appList.length-1; k++) {
			var name = $(appList[k]).data('id');
			if(k < appCount) {
				$(appList[k]).removeClass('hidden');
				$('#apps li[data-id=' + name + ']').addClass('in-header');
				lastShownApp = appList[k];
			} else {
				$(appList[k]).addClass('hidden');
				$('#apps li[data-id=' + name + ']').removeClass('in-header');
				// move active app to last position if it is active
				if(appCount > 0 && $(appList[k]).children('a').hasClass('active')) {
					$(lastShownApp).addClass('hidden');
					$('#apps li[data-id=' + $(lastShownApp).data('id') + ']').removeClass('in-header');
					$(appList[k]).removeClass('hidden');
					$('#apps li[data-id=' + name + ']').addClass('in-header');
				}
			}
		}

		// show/hide more apps icon
		if($('#apps li:not(.in-header)').length === 0) {
			$('#more-apps').hide();
			$('#navigation').hide();
		} else {
			$('#more-apps').show();
		}
	};
	$(window).resize(resizeMenu);
	setTimeout(resizeMenu, 0);

	// just add snapper for logged in users
	if($('#app-navigation').length && !$('html').hasClass('lte9')) {

		// App sidebar on mobile
		var snapper = new Snap({
			element: document.getElementById('app-content'),
			disable: 'right',
			maxPosition: 300, // $navigation-width
			minDragDistance: 100
		});

		$('#app-content').prepend('<div id="app-navigation-toggle" class="icon-menu" style="display:none;" tabindex="0"></div>');

		var toggleSnapperOnButton = function(){
			if(snapper.state().state == 'left'){
				snapper.close();
			} else {
				snapper.open('left');
			}
		};

		$('#app-navigation-toggle').click(function(){
			toggleSnapperOnButton();
		});

		$('#app-navigation-toggle').keypress(function(e) {
			if(e.which == 13) {
				toggleSnapperOnButton();
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
			if($target.is('.app-navigation-entry-utils-menu-button') ||
				$target.closest('.app-navigation-entry-utils-menu-button').length) {
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

		var navigationBarSlideGestureEnabled = false;
		var navigationBarSlideGestureAllowed = true;
		var navigationBarSlideGestureEnablePending = false;

		OC.allowNavigationBarSlideGesture = function() {
			navigationBarSlideGestureAllowed = true;

			if (navigationBarSlideGestureEnablePending) {
				snapper.enable();

				navigationBarSlideGestureEnabled = true;
				navigationBarSlideGestureEnablePending = false;
			}
		};

		OC.disallowNavigationBarSlideGesture = function() {
			navigationBarSlideGestureAllowed = false;

			if (navigationBarSlideGestureEnabled) {
				var endCurrentDrag = true;
				snapper.disable(endCurrentDrag);

				navigationBarSlideGestureEnabled = false;
				navigationBarSlideGestureEnablePending = true;
			}
		};

		var toggleSnapperOnSize = function() {
			if($(window).width() > 768) {
				snapper.close();
				snapper.disable();

				navigationBarSlideGestureEnabled = false;
				navigationBarSlideGestureEnablePending = false;
			} else if (navigationBarSlideGestureAllowed) {
				snapper.enable();

				navigationBarSlideGestureEnabled = true;
				navigationBarSlideGestureEnablePending = false;
			} else {
				navigationBarSlideGestureEnablePending = true;
			}
		};

		$(window).resize(_.debounce(toggleSnapperOnSize, 250));

		// initial call
		toggleSnapperOnSize();

	}

	// Update live timestamps every 30 seconds
	setInterval(function() {
		$('.live-relative-timestamp').each(function() {
			$(this).text(OC.Util.relativeModifiedDate(parseInt($(this).attr('data-timestamp'), 10)));
		});
	}, 30 * 1000);

	OC.PasswordConfirmation.init();
}

OC.PasswordConfirmation = {
	callback: null,
	pageLoadTime: null,
	init: function() {
		$('.password-confirm-required').on('click', _.bind(this.requirePasswordConfirmation, this));
		this.pageLoadTime = moment.now();
	},

	requiresPasswordConfirmation: function() {
		var serverTimeDiff = this.pageLoadTime - (nc_pageLoad * 1000);
		var timeSinceLogin = moment.now() - (serverTimeDiff + (nc_lastLogin * 1000));

		// if timeSinceLogin > 30 minutes and user backend allows password confirmation
		return (backendAllowsPasswordConfirmation && timeSinceLogin > 30 * 60 * 1000);
	},

	/**
	 * @param {function} callback
	 */
	requirePasswordConfirmation: function(callback, options, rejectCallback) {
		options = typeof options !== 'undefined' ? options : {};
		var defaults = {
			title: t('core','Authentication required'),
			text: t(
				'core',
				'This action requires you to confirm your password'
			),
			confirm: t('core', 'Confirm'),
			label: t('core','Password'),
			error: '',
		};

		var config = _.extend(defaults, options);

		var self = this;

		if (this.requiresPasswordConfirmation()) {
			OC.dialogs.prompt(
				config.text,
				config.title,
				function (result, password) {
					if (result && password !== '') {
						self._confirmPassword(password, config);
					} else if (_.isFunction(rejectCallback)) {
						rejectCallback()
					}
				},
				true,
				config.label,
				true
			).then(function() {
				var $dialog = $('.oc-dialog:visible');
				$dialog.find('.ui-icon').remove();
				$dialog.addClass('password-confirmation');
				if (config.error !== '') {
					var $error = $('<p></p>').addClass('msg warning').text(config.error);
				}
				$dialog.find('.oc-dialog-content').append($error);
				$dialog.find('.oc-dialog-buttonrow').addClass('aside');

				var $buttons = $dialog.find('button');
				$buttons.eq(0).hide();
				$buttons.eq(1).text(config.confirm);
			});
		}

		this.callback = callback;
	},

	_confirmPassword: function(password, config) {
		var self = this;

		$.ajax({
			url: OC.generateUrl('/login/confirm'),
			data: {
				password: password
			},
			type: 'POST',
			success: function(response) {
				nc_lastLogin = response.lastLogin;

				if (_.isFunction(self.callback)) {
					self.callback();
				}
			},
			error: function() {
				config.error = t('core', 'Failed to authenticate, try again');
				OC.PasswordConfirmation.requirePasswordConfirmation(self.callback, config);
			}
		});
	}
};

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
	else{
		relativeSize = parseFloat(relativeSize).toLocaleString(OC.getCanonicalLocale());
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
	return decodeURIComponent(
		(new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(
			location.search)||[,''])[1].replace(/\+/g, '%20')
		)||'';
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
	 * Returns a file size in bytes from a humanly readable string
	 * Makes 2kB to 2048.
	 * Inspired by computerFileSize in helper.php
	 * @param  {string} string file size in human readable format
	 * @return {number} or null if string could not be parsed
	 *
	 *
	 */
	computerFileSize: function (string) {
		if (typeof string !== 'string') {
			return null;
		}

		var s = string.toLowerCase().trim();
		var bytes = null;

		var bytesArray = {
			'b' : 1,
			'k' : 1024,
			'kb': 1024,
			'mb': 1024 * 1024,
			'm' : 1024 * 1024,
			'gb': 1024 * 1024 * 1024,
			'g' : 1024 * 1024 * 1024,
			'tb': 1024 * 1024 * 1024 * 1024,
			't' : 1024 * 1024 * 1024 * 1024,
			'pb': 1024 * 1024 * 1024 * 1024 * 1024,
			'p' : 1024 * 1024 * 1024 * 1024 * 1024
		};

		var matches = s.match(/^[\s+]?([0-9]*)(\.([0-9]+))?( +)?([kmgtp]?b?)$/i);
		if (matches !== null) {
			bytes = parseFloat(s);
			if (!isFinite(bytes)) {
				return null;
			}
		} else {
			return null;
		}
		if (matches[5]) {
			bytes = bytes * bytesArray[matches[5]];
		}

		bytes = Math.round(bytes);
		return bytes;
	},

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
	 * Returns whether this is IE
	 *
	 * @return {bool} true if this is IE, false otherwise
	 */
	isIE: function() {
		return $('html').hasClass('ie');
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
					// Note: This locale setting isn't supported by all browsers but for the ones
					// that do there will be more consistency between client-server sorting
					return aa[x].localeCompare(bb[x], OC.getLanguage());
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
	 * @param {Object|string} params to append to the URL, can be either a string
	 * or a map
	 * @param {string} [url] URL to be used, otherwise the current URL will be used,
	 * using the params as query string
	 * @param {boolean} [replace=false] whether to replace instead of pushing
	 */
	_pushState: function(params, url, replace) {
		var strParams;
		if (typeof(params) === 'string') {
			strParams = params;
		}
		else {
			strParams = OC.buildQueryString(params);
		}
		if (window.history.pushState) {
			url = url || location.pathname + '?' + strParams;
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
	 * @param {Object|string} params to append to the URL, can be either a string
	 * or a map
	 * @param {string} [url] URL to be used, otherwise the current URL will be used,
	 * using the params as query string
	 */
	pushState: function(params, url) {
		return this._pushState(params, url, false);
	},

	/**
	 * Push the current URL parameters to the history stack
	 * and change the visible URL.
	 * Note: this includes a workaround for IE8/IE9 that uses
	 * the hash part instead of the search part.
	 *
	 * @param {Object|string} params to append to the URL, can be either a string
	 * or a map
	 * @param {string} [url] URL to be used, otherwise the current URL will be used,
	 * using the params as query string
	 */
	replaceState: function(params, url) {
		return this._pushState(params, url, true);
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
			options.delay.show = argument.delayIn;
		}
		if(argument.delayOut) {
			options.delay.hide = argument.delayOut;
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
};
