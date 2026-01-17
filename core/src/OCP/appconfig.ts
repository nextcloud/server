/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { confirmPassword, isPasswordConfirmationRequired, PwdConfirmationMode } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'

/**
 * @param method - 'post' or 'delete'
 * @param endpoint - endpoint endpoint
 * @param options - destructuring object
 * @param options.data - option data
 * @param options.success - success callback
 * @param options.error - error callback
 */
async function call(method: string, endpoint: string, options: { data?: unknown, success?: (data: unknown) => void, error?: (e: unknown) => void } = {}) {
	if ((method === 'post' || method === 'delete') && isPasswordConfirmationRequired(PwdConfirmationMode.Lax)) {
		await confirmPassword()
	}

	try {
		const { data } = await axios.request({
			method: method.toLowerCase(),
			url: generateOcsUrl('apps/provisioning_api/api/v1/config/apps') + endpoint,
			data: options.data || {},
		})
		options.success?.(data.ocs.data)
	} catch (error) {
		options.error?.(error)
	}
}

/**
 * @param [options] destructuring object
 * @param [options.success] success callback
 * @since 11.0.0
 */
export function getApps(options) {
	call('get', '', options)
}

/**
 * @param app app id
 * @param [options] destructuring object
 * @param [options.success] success callback
 * @param [options.error] error callback
 * @since 11.0.0
 */
export function getKeys(app, options) {
	call('get', '/' + app, options)
}

/**
 * @param app app id
 * @param key key
 * @param defaultValue default value
 * @param [options] destructuring object
 * @param [options.success] success callback
 * @param [options.error] error callback
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
 * @param app app id
 * @param key key
 * @param value value
 * @param [options] destructuring object
 * @param [options.success] success callback
 * @param [options.error] error callback
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
 * @param app app id
 * @param key key
 * @param [options] destructuring object
 * @param [options.success] success callback
 * @param [options.error] error callback
 * @since 11.0.0
 */
export function deleteKey(app, key, options) {
	call('delete', '/' + app + '/' + key, options)
}
