/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
	 * @param {Object<string, string>} bundle bundle
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
