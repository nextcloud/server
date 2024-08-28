/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* eslint-disable */
 import { getValue, setValue, getApps, getKeys, deleteKey } from '../OCP/appconfig.js'

export const appConfig = window.oc_appconfig || {}

/**
 * @namespace
 * @deprecated 16.0.0 Use OCP.AppConfig instead
 */
export const AppConfig = {
	/**
	 * @deprecated Use OCP.AppConfig.getValue() instead
	 */
	getValue: function(app, key, defaultValue, callback) {
		getValue(app, key, defaultValue, {
			success: callback
		})
	},

	/**
	 * @deprecated Use OCP.AppConfig.setValue() instead
	 */
	setValue: function(app, key, value) {
		setValue(app, key, value)
	},

	/**
	 * @deprecated Use OCP.AppConfig.getApps() instead
	 */
	getApps: function(callback) {
		getApps({
			success: callback
		})
	},

	/**
	 * @deprecated Use OCP.AppConfig.getKeys() instead
	 */
	getKeys: function(app, callback) {
		getKeys(app, {
			success: callback
		})
	},

	/**
	 * @deprecated Use OCP.AppConfig.deleteKey() instead
	 */
	deleteKey: function(app, key) {
		deleteKey(app, key)
	}

}
