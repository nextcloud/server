/** @namespace OCP */
var OCP = Object.assign({}, window.OCP);

/**
 * @namespace OC
 */
Object.assign(window.OC, {
	/* jshint camelcase: false */

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

