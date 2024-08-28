/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import $ from 'jquery'
import { generateOcsUrl } from '@nextcloud/router'

import OC from '../OC/index.js'

/**
 * @param {string} method 'post' or 'delete'
 * @param {string} endpoint endpoint
 * @param {object} [options] destructuring object
 * @param {object} [options.data] option data
 * @param {Function} [options.success] success callback
 * @param {Function} [options.error] error callback
 */
function call(method, endpoint, options) {
	if ((method === 'post' || method === 'delete') && OC.PasswordConfirmation.requiresPasswordConfirmation()) {
		OC.PasswordConfirmation.requirePasswordConfirmation(_.bind(call, this, method, endpoint, options))
		return
	}

	options = options || {}
	$.ajax({
		type: method.toUpperCase(),
		url: generateOcsUrl('apps/provisioning_api/api/v1/config/apps') + endpoint,
		data: options.data || {},
		success: options.success,
		error: options.error,
	})
}

/**
 * @param {object} [options] destructuring object
 * @param {Function} [options.success] success callback
 * @since 11.0.0
 */
export function getApps(options) {
	call('get', '', options)
}

/**
 * @param {string} app app id
 * @param {object} [options] destructuring object
 * @param {Function} [options.success] success callback
 * @param {Function} [options.error] error callback
 * @since 11.0.0
 */
export function getKeys(app, options) {
	call('get', '/' + app, options)
}

/**
 * @param {string} app app id
 * @param {string} key key
 * @param {string | Function} defaultValue default value
 * @param {object} [options] destructuring object
 * @param {Function} [options.success] success callback
 * @param {Function} [options.error] error callback
 * @since 11.0.0
 */
export function getValue(app, key, defaultValue, options) {
	options = options || {}
	options.data = {
		defaultValue,
	}

	call('get', '/' + app + '/' + key, options)
}

/**
 * @param {string} app app id
 * @param {string} key key
 * @param {string} value value
 * @param {object} [options] destructuring object
 * @param {Function} [options.success] success callback
 * @param {Function} [options.error] error callback
 * @since 11.0.0
 */
export function setValue(app, key, value, options) {
	options = options || {}
	options.data = {
		value,
	}

	call('post', '/' + app + '/' + key, options)
}

/**
 * @param {string} app app id
 * @param {string} key key
 * @param {object} [options] destructuring object
 * @param {Function} [options.success] success callback
 * @param {Function} [options.error] error callback
 * @since 11.0.0
 */
export function deleteKey(app, key, options) {
	call('delete', '/' + app + '/' + key, options)
}
