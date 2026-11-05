/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ISharingSettings } from '../types.ts'

import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { reactive } from 'vue'
import logger from '../../../logger.ts'
import { setCoreConfigValue } from '../services/appConfig.ts'

/**
 * Get the admin sharing settings as reactive object.
 *
 * Assigning a setting stores it in the app config of core as
 * `shareapi_<setting in snake case>`, e.g. `allowLinks` as `shareapi_allow_links`.
 */
export function useSharingSettings(): ISharingSettings {
	const settings = reactive(loadState<ISharingSettings>('settings', 'sharingSettings'))

	return new Proxy(settings, {
		set(target, property, value) {
			if (typeof property === 'string') {
				saveSetting(getConfigKey(property), serializeValue(value))
			}
			return Reflect.set(target, property, value)
		},
	})
}

/**
 * Store a sharing setting in the app config of core.
 *
 * @param key - The config key
 * @param value - The serialized value
 */
async function saveSetting(key: string, value: string): Promise<void> {
	try {
		await setCoreConfigValue(key, value)
	} catch (error) {
		logger.error('Could not save the sharing setting', { key, error })
		showError(t('settings', 'Could not save the sharing setting'))
	}
}

/**
 * Get the app config key of a sharing setting.
 *
 * @param setting - The name of the setting
 */
function getConfigKey(setting: string): string {
	const snakeCase = setting
		.replace(/([a-z0-9])([A-Z])/g, '$1_$2')
		// split a single upper case letter word like the "N" of "AfterNDays"
		.replace(/([A-Z])([A-Z][a-z])/g, '$1_$2')
		.toLowerCase()
	return `shareapi_${snakeCase}`
}

/**
 * Serialize a setting as app config value, booleans are stored as `yes` or `no`.
 *
 * @param value - The value of the setting
 */
function serializeValue(value: unknown): string {
	if (typeof value === 'boolean') {
		return value ? 'yes' : 'no'
	}
	return typeof value === 'string' ? value : JSON.stringify(value)
}
