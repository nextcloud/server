/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as AppConfig from '../../../../../../core/src/OCP/appconfig.ts'

/**
 * The app config API of core, it asks for the password confirmation if needed.
 */
function getAppConfig(): typeof AppConfig {
	return (window.OCP as unknown as { AppConfig: typeof AppConfig }).AppConfig
}

/**
 * Store a value in the app config of core.
 *
 * @param key - The config key
 * @param value - The new value
 */
export function setCoreConfigValue(key: string, value: string): Promise<void> {
	return new Promise((resolve, reject) => {
		getAppConfig().setValue('core', key, value, { success: () => resolve(), error: reject })
	})
}

/**
 * Remove a value from the app config of core.
 *
 * @param key - The config key
 */
export function deleteCoreConfigValue(key: string): Promise<void> {
	return new Promise((resolve, reject) => {
		getAppConfig().deleteKey('core', key, { success: () => resolve(), error: reject })
	})
}
