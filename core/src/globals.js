/*!
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { n, t } from '@nextcloud/l10n'
import { initCore } from './init.js'
import OC from './OC/index.js'
import OCA from './OCA/index.js'
import OCP from './OCP/index.js'

window.OC = OC
setDeprecatedProp('initCore', () => initCore, 'this is an internal function')
window.OCP = OCP
window.OCA = OCA

// Expose translation functions to the global scope for legacy code
window.t = t
window.n = n

/**
 * If not in a testing environment, log a warning to the console if debugging is enabled.
 *
 * @param {...unknown} args - the arguments to log to the console
 * @private
 */
function warnIfNotTesting(...args) {
	if (window.TESTING === undefined) {
		// eslint-disable-next-line no-console
		OC.debug && console.warn.apply(console, args)
	}
}

/**
 * @param {string|string[]} global - a string or array of strings with the name of the global variable(s) to deprecate
 * @param {() => unknown} cb - a callback that returns the value of the global variable when accessed
 * @param {string} msg - an optional message to show in the warning
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
