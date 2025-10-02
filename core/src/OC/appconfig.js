/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { deleteKey, getApps, getKeys, getValue, setValue } from '../OCP/appconfig.js'

export const appConfig = window.oc_appconfig || {}

/**
 * @namespace
 * @deprecated 16.0.0 Use OCP.AppConfig instead
 */
export const AppConfig = {
	/**
	 * @param app
	 * @param key
	 * @param defaultValue
	 * @param callback
	 * @deprecated Use OCP.AppConfig.getValue() instead
	 */
	getValue: function(app, key, defaultValue, callback) {
		getValue(app, key, defaultValue, {
			success: callback,
		})
	},

	/**
	 * @param app
	 * @param key
	 * @param value
	 * @deprecated Use OCP.AppConfig.setValue() instead
	 */
	setValue: function(app, key, value) {
		setValue(app, key, value)
	},

	/**
	 * @param callback
	 * @deprecated Use OCP.AppConfig.getApps() instead
	 */
	getApps: function(callback) {
		getApps({
			success: callback,
		})
	},

	/**
	 * @param app
	 * @param callback
	 * @deprecated Use OCP.AppConfig.getKeys() instead
	 */
	getKeys: function(app, callback) {
		getKeys(app, {
			success: callback,
		})
	},

	/**
	 * @param app
	 * @param key
	 * @deprecated Use OCP.AppConfig.deleteKey() instead
	 */
	deleteKey: function(app, key) {
		deleteKey(app, key)
	},

}
