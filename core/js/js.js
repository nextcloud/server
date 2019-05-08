var oc_current_user = document.getElementsByTagName('head')[0].getAttribute('data-user');
var oc_requesttoken = document.getElementsByTagName('head')[0].getAttribute('data-requesttoken');

/** @namespace OCP */
var OCP = Object.assign({}, window.OCP);

/**
 * @namespace OC
 */
Object.assign(window.OC, {
	/* jshint camelcase: false */
	/**
	 * Capabilities
	 *
	 * @type array
	 */
	_capabilities: window.oc_capabilities || null,

	/**
	 * Currently logged in user or null if none
	 *
	 * @type String
	 * @deprecated use {@link OC.getCurrentUser} instead
	 */
	currentUser:(typeof oc_current_user!=='undefined')?oc_current_user:false,
	theme: window.oc_defaults || {},
	requestToken: oc_requesttoken,

	/**
	 * Check if a user file is allowed to be handled.
	 * @param {string} file to check
	 */
	fileIsBlacklisted: function(file) {
		return !!(file.match(OC.config.blacklist_files_regex));
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
	 * @deprecated 16.0.0 Use OCP.Loader.loadScript
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
	 * @deprecated 16.0.0 Use OCP.Loader.loadStylesheet
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
});

OC.addStyle.loaded=[];
OC.addScript.loaded=[];

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
	if (msie > 0 || trident > 0 || edge > 0) {
		console.info('Legacy browser detected, applying css vars polyfill')
		cssVars({
			watch: true,
			//  set edge < 16 as incompatible
			onlyLegacy: !(/Edge\/([0-9]{2})\./i.test(navigator.userAgent)
				&& parseInt(/Edge\/([0-9]{2})\./i.exec(navigator.userAgent)[1]) < 16)
		});
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
		if (OC.config.session_lifetime) {
			interval = Math.floor(OC.config.session_lifetime / 2);
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
	if (typeof(OC.config.session_keepalive) === 'undefined' ||
		!!OC.config.session_keepalive) {

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

		// show loading feedback on more apps list
		$navigation.delegate('a', 'click', function(event) {
			var $app = $(event.target);
			if(!$app.is('a')) {
				$app = $app.closest('a');
			}
			if(event.which === 1 && !event.ctrlKey && !event.metaKey) {
				$app.find('svg').remove();
				$app.find('div').remove(); // prevent odd double-clicks
				// no need for theming, loader is already inverted on dark mode
				// but we need it over the primary colour
				$app.prepend($('<div/>').addClass('icon-loading-small'));
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

		// show loading feedback on visible apps list
		$appmenu.delegate('li:not(#more-apps) > a', 'click', function(event) {
			var $app = $(event.target);
			if(!$app.is('a')) {
				$app = $app.closest('a');
			}
			if(event.which === 1 && !event.ctrlKey && !event.metaKey && $app.parent('#more-apps').length === 0) {
				$app.find('svg').remove();
				$app.find('div').remove(); // prevent odd double-clicks
				$app.prepend($('<div/>').addClass(
					OCA.Theming && OCA.Theming.inverted
						? 'icon-loading-small'
						: 'icon-loading-small-dark'
				));
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
	// and if the app doesn't handle the nav slider itself
	if($('#app-navigation').length && !$('html').hasClass('lte9')
	    && !$('#app-content').hasClass('no-snapper')) {

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

$(document).ready(initCore);

/**
// fallback to hashchange when no history support
if (window.history.pushState) {
	window.onpopstate = _.bind(OC.Util.History._onPopState, OC.Util.History);
}
else {
	$(window).on('hashchange', _.bind(OC.Util.History._onPopState, OC.Util.History));
}
 */
