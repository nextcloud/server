/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* eslint-disable @nextcloud/no-deprecations */
import ClipboardJS from 'clipboard'
import { dav } from 'davclient.js'
import Handlebars from 'handlebars'
import moment from 'moment'
import _ from 'underscore'
import { initCore } from './init.js'
import OC from './OC/index.js'
import { getRequestToken } from './OC/requesttoken.ts'
import OCA from './OCA/index.js'
import OCP from './OCP/index.js'

/**
 *
 */
function warnIfNotTesting() {
	if (window.TESTING === undefined) {
		// eslint-disable-next-line no-console
		OC.debug && console.warn.apply(console, arguments)
	}
}

/**
 *
 * @param global
 * @param cb
 * @param msg
 */
function setDeprecatedProp(global, cb, msg) {
	(Array.isArray(global) ? global : [global]).forEach((global) => {
		if (window[global] !== undefined) {
			delete window[global]
		}
		Object.defineProperty(window, global, {
			get: () => {
				if (msg) {
					warnIfNotTesting(`${global} is deprecated: ${msg}`)
				} else {
					warnIfNotTesting(`${global} is deprecated`)
				}

				return cb()
			},
		})
	})
}

setDeprecatedProp(['_'], () => _, 'The global underscore is deprecated. It will be removed in a later versions without another warning. Please ship your own.')
setDeprecatedProp(['Clipboard', 'ClipboardJS'], () => ClipboardJS, 'please ship your own, this will be removed in Nextcloud 20')
setDeprecatedProp(['dav'], () => dav, 'please ship your own. It will be removed in a later versions without another warning. Please ship your own.')
setDeprecatedProp('Handlebars', () => Handlebars, 'please ship your own, this will be removed in Nextcloud 20')
setDeprecatedProp('moment', () => moment, 'please ship your own, this will be removed in Nextcloud 20')

window.OC = OC
setDeprecatedProp('initCore', () => initCore, 'this is an internal function')
setDeprecatedProp('oc_appswebroots', () => OC.appswebroots, 'use OC.appswebroots instead, this will be removed in Nextcloud 20')
setDeprecatedProp('oc_config', () => OC.config, 'use OC.config instead, this will be removed in Nextcloud 20')
setDeprecatedProp('oc_current_user', () => OC.getCurrentUser().uid, 'use OC.getCurrentUser().uid instead, this will be removed in Nextcloud 20')
setDeprecatedProp('oc_debug', () => OC.debug, 'use OC.debug instead, this will be removed in Nextcloud 20')
setDeprecatedProp('oc_defaults', () => OC.theme, 'use OC.theme instead, this will be removed in Nextcloud 20')
setDeprecatedProp('oc_isadmin', OC.isUserAdmin, 'use OC.isUserAdmin() instead, this will be removed in Nextcloud 20')
setDeprecatedProp('oc_requesttoken', () => getRequestToken(), 'use OC.requestToken instead, this will be removed in Nextcloud 20')
setDeprecatedProp('oc_webroot', () => OC.webroot, 'use OC.getRootPath() instead, this will be removed in Nextcloud 20')
setDeprecatedProp('OCDialogs', () => OC.dialogs, 'use OC.dialogs instead, this will be removed in Nextcloud 20')
window.OCP = OCP
window.OCA = OCA

/**
 * translate a string
 *
 * @param {string} app the id of the app for which to translate the string
 * @param {string} text the string to translate
 * @param [vars] map of placeholder key to value
 * @param {number} [count] number to replace %n with
 * @return {string}
 */
window.t = _.bind(OC.L10N.translate, OC.L10N)

/**
 * translate a string
 *
 * @param {string} app the id of the app for which to translate the string
 * @param {string} text_singular the string to translate for exactly one object
 * @param {string} text_plural the string to translate for n objects
 * @param {number} count number to determine whether to use singular or plural
 * @param [vars] map of placeholder key to value
 * @return {string} Translated string
 */
window.n = _.bind(OC.L10N.translatePlural, OC.L10N)
