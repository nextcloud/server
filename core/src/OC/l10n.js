/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014 ownCloud, Inc.
 * SPDX-FileCopyrightText: 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Handlebars from 'handlebars'
import {
	loadTranslations,
	translate,
	translatePlural,
	register,
	unregister,
} from '@nextcloud/l10n'

/**
 * L10N namespace with localization functions.
 *
 * @namespace OC.L10n
 * @deprecated 26.0.0 use https://www.npmjs.com/package/@nextcloud/l10n
 */
const L10n = {

	/**
	 * Load an app's translation bundle if not loaded already.
	 *
	 * @deprecated 26.0.0 use `loadTranslations` from https://www.npmjs.com/package/@nextcloud/l10n
	 *
	 * @param {string} appName name of the app
	 * @param {Function} callback callback to be called when
	 * the translations are loaded
	 * @return {Promise} promise
	 */
	load: loadTranslations,

	/**
	 * Register an app's translation bundle.
	 *
	 * @deprecated 26.0.0 use `register` from https://www.npmjs.com/package/@nextcloud/l10
	 *
	 * @param {string} appName name of the app
	 * @param {Record<string, string>} bundle bundle
	 */
	register,

	/**
	 * @private
	 * @deprecated 26.0.0 use `unregister` from https://www.npmjs.com/package/@nextcloud/l10n
	 */
	_unregister: unregister,

	/**
	 * Translate a string
	 *
	 * @deprecated 26.0.0 use `translate` from https://www.npmjs.com/package/@nextcloud/l10n
	 *
	 * @param {string} app the id of the app for which to translate the string
	 * @param {string} text the string to translate
	 * @param {object} [vars] map of placeholder key to value
	 * @param {number} [count] number to replace %n with
	 * @param {Array} [options] options array
	 * @param {boolean} [options.escape=true] enable/disable auto escape of placeholders (by default enabled)
	 * @param {boolean} [options.sanitize=true] enable/disable sanitization (by default enabled)
	 * @return {string}
	 */
	translate,

	/**
	 * Translate a plural string
	 *
	 * @deprecated 26.0.0 use `translatePlural` from https://www.npmjs.com/package/@nextcloud/l10n
	 *
	 * @param {string} app the id of the app for which to translate the string
	 * @param {string} textSingular the string to translate for exactly one object
	 * @param {string} textPlural the string to translate for n objects
	 * @param {number} count number to determine whether to use singular or plural
	 * @param {object} [vars] map of placeholder key to value
	 * @param {Array} [options] options array
	 * @param {boolean} [options.escape=true] enable/disable auto escape of placeholders (by default enabled)
	 * @return {string} Translated string
	 */
	translatePlural,
}

export default L10n

Handlebars.registerHelper('t', function(app, text) {
	return translate(app, text)
})
