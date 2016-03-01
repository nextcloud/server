/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/**
 * L10N namespace with localization functions.
 *
 * @namespace
 */
OC.L10N = {
	/**
	 * String bundles with app name as key.
	 * @type {Object.<String,String>}
	 */
	_bundles: {},

	/**
	 * Plural functions, key is app name and value is function.
	 * @type {Object.<String,Function>}
	 */
	_pluralFunctions: {},

	/**
	 * Load an app's translation bundle if not loaded already.
	 *
	 * @param {String} appName name of the app
	 * @param {Function} callback callback to be called when
	 * the translations are loaded
	 * @return {Promise} promise
	 */
	load: function(appName, callback) {
		// already available ?
		if (this._bundles[appName] || OC.getLocale() === 'en') {
			var deferred = $.Deferred();
			var promise = deferred.promise();
			promise.then(callback);
			deferred.resolve();
			return promise;
		}

		var self = this;
		var url = OC.filePath(appName, 'l10n', OC.getLocale() + '.json');

		// load JSON translation bundle per AJAX
		return $.get(url)
			.then(
				function(result) {
					if (result.translations) {
						self.register(appName, result.translations, result.pluralForm);
					}
				})
			.then(callback);
	},

	/**
	 * Register an app's translation bundle.
	 *
	 * @param {String} appName name of the app
	 * @param {Object<String,String>} bundle
	 * @param {Function|String} [pluralForm] optional plural function or plural string
	 */
	register: function(appName, bundle, pluralForm) {
		var self = this;
		if (_.isUndefined(this._bundles[appName])) {
			this._bundles[appName] = bundle || {};

			if (_.isFunction(pluralForm)) {
				this._pluralFunctions[appName] = pluralForm;
			} else {
				// generate plural function based on form
				this._pluralFunctions[appName] = this._generatePluralFunction(pluralForm);
			}
		} else {
			// Theme overwriting the default language
			_.extend(self._bundles[appName], bundle);
		}
	},

	/**
	 * Generates a plural function based on the given plural form.
	 * If an invalid form has been given, returns a default function.
	 *
	 * @param {String} pluralForm plural form
	 */
	_generatePluralFunction: function(pluralForm) {
		// default func
		var func = function (n) {
			var p = (n !== 1) ? 1 : 0;
			return { 'nplural' : 2, 'plural' : p };
		};

		if (!pluralForm) {
			console.warn('Missing plural form in language file');
			return func;
		}

		/**
		 * code below has been taken from jsgettext - which is LGPL licensed
		 * https://developer.berlios.de/projects/jsgettext/
		 * http://cvs.berlios.de/cgi-bin/viewcvs.cgi/jsgettext/jsgettext/lib/Gettext.js
		 */
		var pf_re = new RegExp('^(\\s*nplurals\\s*=\\s*[0-9]+\\s*;\\s*plural\\s*=\\s*(?:\\s|[-\\?\\|&=!<>+*/%:;a-zA-Z0-9_\\(\\)])+)', 'm');
		if (pf_re.test(pluralForm)) {
			//ex english: "Plural-Forms: nplurals=2; plural=(n != 1);\n"
			//pf = "nplurals=2; plural=(n != 1);";
			//ex russian: nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10< =4 && (n%100<10 or n%100>=20) ? 1 : 2)
			//pf = "nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)";
			var pf = pluralForm;
			if (! /;\s*$/.test(pf)) {
				pf = pf.concat(';');
			}
			/* We used to use eval, but it seems IE has issues with it.
			 * We now use "new Function", though it carries a slightly
			 * bigger performance hit.
			var code = 'function (n) { var plural; var nplurals; '+pf+' return { "nplural" : nplurals, "plural" : (plural === true ? 1 : plural ? plural : 0) }; };';
			Gettext._locale_data[domain].head.plural_func = eval("("+code+")");
			 */
			var code = 'var plural; var nplurals; '+pf+' return { "nplural" : nplurals, "plural" : (plural === true ? 1 : plural ? plural : 0) };';
			func = new Function("n", code);
		} else {
			console.warn('Invalid plural form in language file: "' + pluralForm + '"');
		}
		return func;
	},

	/**
	 * Translate a string
	 * @param {string} app the id of the app for which to translate the string
	 * @param {string} text the string to translate
	 * @param [vars] map of placeholder key to value
	 * @param {number} [count] number to replace %n with
	 * @param {array} [options] options array
	 * @param {bool} [options.escape=true] enable/disable auto escape of placeholders (by default enabled)
	 * @return {string}
	 */
	translate: function(app, text, vars, count, options) {
		var defaultOptions = {
				escape: true
			},
			allOptions = options || {};
		_.defaults(allOptions, defaultOptions);

		// TODO: cache this function to avoid inline recreation
		// of the same function over and over again in case
		// translate() is used in a loop
		var _build = function (text, vars, count) {
			return text.replace(/%n/g, count).replace(/{([^{}]*)}/g,
				function (a, b) {
					var r = vars[b];
					if(typeof r === 'string' || typeof r === 'number') {
						if(allOptions.escape) {
							return escapeHTML(r);
						} else {
							return r;
						}
					} else {
						return a;
					}
				}
			);
		};
		var translation = text;
		var bundle = this._bundles[app] || {};
		var value = bundle[text];
		if( typeof(value) !== 'undefined' ){
			translation = value;
		}

		if(typeof vars === 'object' || count !== undefined ) {
			return _build(translation, vars, count);
		} else {
			return translation;
		}
	},

	/**
	 * Translate a plural string
	 * @param {string} app the id of the app for which to translate the string
	 * @param {string} textSingular the string to translate for exactly one object
	 * @param {string} textPlural the string to translate for n objects
	 * @param {number} count number to determine whether to use singular or plural
	 * @param [vars] map of placeholder key to value
	 * @param {array} [options] options array
	 * @param {bool} [options.escape=true] enable/disable auto escape of placeholders (by default enabled)
	 * @return {string} Translated string
	 */
	translatePlural: function(app, textSingular, textPlural, count, vars, options) {
		var identifier = '_' + textSingular + '_::_' + textPlural + '_';
		var bundle = this._bundles[app] || {};
		var value = bundle[identifier];
		if( typeof(value) !== 'undefined' ){
			var translation = value;
			if ($.isArray(translation)) {
				var plural = this._pluralFunctions[app](count);
				return this.translate(app, translation[plural.plural], vars, count, options);
			}
		}

		if(count === 1) {
			return this.translate(app, textSingular, vars, count, options);
		}
		else{
			return this.translate(app, textPlural, vars, count, options);
		}
	}
};

/**
 * translate a string
 * @param {string} app the id of the app for which to translate the string
 * @param {string} text the string to translate
 * @param [vars] map of placeholder key to value
 * @param {number} [count] number to replace %n with
 * @return {string}
 */
window.t = _.bind(OC.L10N.translate, OC.L10N);

/**
 * translate a string
 * @param {string} app the id of the app for which to translate the string
 * @param {string} text_singular the string to translate for exactly one object
 * @param {string} text_plural the string to translate for n objects
 * @param {number} count number to determine whether to use singular or plural
 * @param [vars] map of placeholder key to value
 * @return {string} Translated string
 */
window.n = _.bind(OC.L10N.translatePlural, OC.L10N);

Handlebars.registerHelper('t', function(app, text) {
	return OC.L10N.translate(app, text);
});

