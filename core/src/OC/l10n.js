/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

import _ from 'underscore'
import $ from 'jquery'
import Handlebars from 'handlebars'

import OC from './index'
import {
	getAppTranslations,
	hasAppTranslations,
	registerAppTranslations,
	unregisterAppTranslations
} from './l10n-registry'

/**
 * L10N namespace with localization functions.
 *
 * @namespace OC.L10n
 */
const L10n = {

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
		if (hasAppTranslations(appName) || OC.getLocale() === 'en') {
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
	 */
	register: function(appName, bundle, pluralForm) {
		registerAppTranslations(appName, bundle, this._getPlural)
	},

	/**
	 * @private do not use this
	 */
	_unregister: unregisterAppTranslations,

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
							return DOMPurify.sanitize(escapeHTML(r));
						} else {
							return DOMPurify.sanitize(r);
						}
					} else {
						return DOMPurify.sanitize(a);
					}
				}
			);
		};
		var translation = text;
		var bundle = getAppTranslations(app);
		var value = bundle.translations[text];
		if( typeof(value) !== 'undefined' ){
			translation = value;
		}

		if(typeof vars === 'object' || count !== undefined ) {
			return DOMPurify.sanitize(_build(translation, vars, count));
		} else {
			return DOMPurify.sanitize(translation);
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
		const identifier = '_' + textSingular + '_::_' + textPlural + '_';
		const bundle = getAppTranslations(app);
		const value = bundle.translations[identifier];
		if( typeof(value) !== 'undefined' ){
			var translation = value;
			if ($.isArray(translation)) {
				var plural = bundle.pluralFunction(count);
				return this.translate(app, translation[plural], vars, count, options);
			}
		}

		if (count === 1) {
			return this.translate(app, textSingular, vars, count, options);
		} else {
			return this.translate(app, textPlural, vars, count, options);
		}
	},

	/**
	 * The plural function taken from symfony
	 *
	 * @param {number} number
	 * @returns {number}
	 * @private
	 */
	_getPlural: function(number) {
		var language = OC.getLanguage();
		if ('pt_BR' === language) {
			// temporary set a locale for brazilian
			language = 'xbr';
		}

		if (typeof language === 'undefined' || language === '') {
			return (1 == number) ? 0 : 1;
		}

		if (language.length > 3) {
			language = language.substring(0, language.lastIndexOf('_'));
		}

		/*
		 * The plural rules are derived from code of the Zend Framework (2010-09-25),
		 * which is subject to the new BSD license (http://framework.zend.com/license/new-bsd).
		 * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
		 */
		switch (language) {
			case 'az':
			case 'bo':
			case 'dz':
			case 'id':
			case 'ja':
			case 'jv':
			case 'ka':
			case 'km':
			case 'kn':
			case 'ko':
			case 'ms':
			case 'th':
			case 'tr':
			case 'vi':
			case 'zh':
				return 0;

			case 'af':
			case 'bn':
			case 'bg':
			case 'ca':
			case 'da':
			case 'de':
			case 'el':
			case 'en':
			case 'eo':
			case 'es':
			case 'et':
			case 'eu':
			case 'fa':
			case 'fi':
			case 'fo':
			case 'fur':
			case 'fy':
			case 'gl':
			case 'gu':
			case 'ha':
			case 'he':
			case 'hu':
			case 'is':
			case 'it':
			case 'ku':
			case 'lb':
			case 'ml':
			case 'mn':
			case 'mr':
			case 'nah':
			case 'nb':
			case 'ne':
			case 'nl':
			case 'nn':
			case 'no':
			case 'oc':
			case 'om':
			case 'or':
			case 'pa':
			case 'pap':
			case 'ps':
			case 'pt':
			case 'so':
			case 'sq':
			case 'sv':
			case 'sw':
			case 'ta':
			case 'te':
			case 'tk':
			case 'ur':
			case 'zu':
				return (1 == number) ? 0 : 1;

			case 'am':
			case 'bh':
			case 'fil':
			case 'fr':
			case 'gun':
			case 'hi':
			case 'hy':
			case 'ln':
			case 'mg':
			case 'nso':
			case 'xbr':
			case 'ti':
			case 'wa':
				return ((0 == number) || (1 == number)) ? 0 : 1;

			case 'be':
			case 'bs':
			case 'hr':
			case 'ru':
			case 'sh':
			case 'sr':
			case 'uk':
				return ((1 == number % 10) && (11 != number % 100)) ? 0 : (((number % 10 >= 2) && (number % 10 <= 4) && ((number % 100 < 10) || (number % 100 >= 20))) ? 1 : 2);

			case 'cs':
			case 'sk':
				return (1 == number) ? 0 : (((number >= 2) && (number <= 4)) ? 1 : 2);

			case 'ga':
				return (1 == number) ? 0 : ((2 == number) ? 1 : 2);

			case 'lt':
				return ((1 == number % 10) && (11 != number % 100)) ? 0 : (((number % 10 >= 2) && ((number % 100 < 10) || (number % 100 >= 20))) ? 1 : 2);

			case 'sl':
				return (1 == number % 100) ? 0 : ((2 == number % 100) ? 1 : (((3 == number % 100) || (4 == number % 100)) ? 2 : 3));

			case 'mk':
				return (1 == number % 10) ? 0 : 1;

			case 'mt':
				return (1 == number) ? 0 : (((0 == number) || ((number % 100 > 1) && (number % 100 < 11))) ? 1 : (((number % 100 > 10) && (number % 100 < 20)) ? 2 : 3));

			case 'lv':
				return (0 == number) ? 0 : (((1 == number % 10) && (11 != number % 100)) ? 1 : 2);

			case 'pl':
				return (1 == number) ? 0 : (((number % 10 >= 2) && (number % 10 <= 4) && ((number % 100 < 12) || (number % 100 > 14))) ? 1 : 2);

			case 'cy':
				return (1 == number) ? 0 : ((2 == number) ? 1 : (((8 == number) || (11 == number)) ? 2 : 3));

			case 'ro':
				return (1 == number) ? 0 : (((0 == number) || ((number % 100 > 0) && (number % 100 < 20))) ? 1 : 2);

			case 'ar':
				return (0 == number) ? 0 : ((1 == number) ? 1 : ((2 == number) ? 2 : (((number % 100 >= 3) && (number % 100 <= 10)) ? 3 : (((number % 100 >= 11) && (number % 100 <= 99)) ? 4 : 5))));

			default:
				return 0;
		}
	}
};

export default L10n;

/**
 * Returns the user's locale as a BCP 47 compliant language tag
 *
 * @return {String} locale string
 */
export const getCanonicalLocale = () => {
	const locale = getLocale()
	return typeof locale === 'string' ? locale.replace(/_/g, '-') : locale
}

/**
 * Returns the user's locale
 *
 * @return {String} locale string
 */
export const getLocale = () => $('html').data('locale')

/**
 * Returns the user's language
 *
 * @returns {String} language string
 */
export const getLanguage = () => $('html').prop('lang')

Handlebars.registerHelper('t', function(app, text) {
	return L10n.translate(app, text);
});

