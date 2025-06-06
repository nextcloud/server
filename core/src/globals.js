/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* eslint-disable @nextcloud/no-deprecations */
import { initCore } from './init.js'

import _ from 'underscore'
import $ from 'jquery'
// TODO: switch to `jquery-ui` package and import widgets and effects individually
//       `jquery-ui-dist` is used as a workaround for the issue of missing effects
import 'jquery-ui-dist/jquery-ui.js'
import 'jquery-ui-dist/jquery-ui.css'
import 'jquery-ui-dist/jquery-ui.theme.css'
// END TODO
import Backbone from 'backbone'
import ClipboardJS from 'clipboard'
import { dav } from 'davclient.js'
import Handlebars from 'handlebars'
import md5 from 'blueimp-md5'
import moment from 'moment'
import 'select2'
import 'select2/select2.css'
import 'snap.js/dist/snap.js'
import 'strengthify'
import 'strengthify/strengthify.css'

import OC from './OC/index.js'
import OCP from './OCP/index.js'
import OCA from './OCA/index.js'
import { getRequestToken } from './OC/requesttoken.ts'

const warnIfNotTesting = function() {
	if (window.TESTING === undefined) {
		OC.debug && console.warn.apply(console, arguments)
	}
}

/**
 * Mark a function as deprecated and automatically
 * warn if used!
 *
 * @param {Function} func the library to deprecate
 * @param {string} funcName the name of the library
 * @param {number} version the version this gets removed
 * @return {Function}
 */
const deprecate = (func, funcName, version) => {
	const oldFunc = func
	const newFunc = function() {
		warnIfNotTesting(`The ${funcName} library is deprecated! It will be removed in nextcloud ${version}.`)
		return oldFunc.apply(this, arguments)
	}
	Object.assign(newFunc, oldFunc)
	return newFunc
}

const setDeprecatedProp = (global, cb, msg) => {
	(Array.isArray(global) ? global : [global]).forEach(global => {
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

window._ = _
setDeprecatedProp(['$', 'jQuery'], () => $, 'The global jQuery is deprecated. It will be removed in a later versions without another warning. Please ship your own.')
setDeprecatedProp('Backbone', () => Backbone, 'please ship your own, this will be removed in Nextcloud 20')
setDeprecatedProp(['Clipboard', 'ClipboardJS'], () => ClipboardJS, 'please ship your own, this will be removed in Nextcloud 20')
window.dav = dav
setDeprecatedProp('Handlebars', () => Handlebars, 'please ship your own, this will be removed in Nextcloud 20')
// Global md5 only required for: apps/files/js/file-upload.js
setDeprecatedProp('md5', () => md5, 'please ship your own, this will be removed in Nextcloud 20')
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
$.fn.select2 = deprecate($.fn.select2, 'select2', 19)

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
