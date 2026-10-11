/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { OCSResponse } from '@nextcloud/typings/ocs'
import type { PreviewLimits, PreviewSettings } from '../types.ts'

import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { addPasswordConfirmationInterceptors, PwdConfirmationMode } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import logger from '../../logger.ts'

addPasswordConfirmationInterceptors(axios)

const SETTINGS_URL = generateOcsUrl('/apps/settings/api/admin/previews/settings')
const PROVIDERS_URL = generateOcsUrl('/apps/settings/api/admin/previews/providers')

export const usePreviewSettingsStore = defineStore('preview-settings', () => {
	const settings = ref(loadState<PreviewSettings>('settings', 'previewSettings'))
	const saving = ref(false)

	const readOnly = computed(() => settings.value.configIsReadOnly)
	const enabledProviders = computed(() => settings.value.providers.filter((provider) => provider.enabled))

	/**
	 * Send a change and take the state returned by the server
	 *
	 * @param request The request returning the new settings
	 */
	async function save(request: () => Promise<{ data: OCSResponse<PreviewSettings> }>): Promise<void> {
		saving.value = true
		try {
			const { data } = await request()
			settings.value = data.ocs.data
		} catch (error) {
			logger.error('Could not save the preview settings', { error })
			showError(t('settings', 'Could not save the preview settings'))
		} finally {
			saving.value = false
		}
	}

	/**
	 * Store the switch and the limits
	 *
	 * @param changes The values to change
	 */
	async function updateSettings(changes: Partial<PreviewLimits & { enabled: boolean }>): Promise<void> {
		const { enabled, maxX, maxY, maxMemory, maxFilesizeImage, jpegQuality, webpQuality, concurrencyNew, concurrencyAll, expirationDays } = { ...settings.value, ...changes }
		await save(() => axios.put(SETTINGS_URL, {
			enabled,
			maxX,
			maxY,
			maxMemory,
			maxFilesizeImage,
			jpegQuality,
			webpQuality,
			concurrencyNew,
			concurrencyAll,
			expirationDays,
		}, { confirmPassword: PwdConfirmationMode.Lax }))
	}

	/**
	 * Store the enabled providers in their current order
	 *
	 * @param classes The provider classes, in try-order
	 */
	async function saveProviders(classes: string[]): Promise<void> {
		await save(() => axios.put(PROVIDERS_URL, { providers: classes }, { confirmPassword: PwdConfirmationMode.Lax }))
	}

	/**
	 * Enable or disable a provider, a newly enabled one is tried last
	 *
	 * @param providerClass The provider class
	 * @param enabled Whether to enable it
	 */
	async function toggleProvider(providerClass: string, enabled: boolean): Promise<void> {
		const classes = enabledProviders.value
			.map((provider) => provider.class)
			.filter((name) => name !== providerClass)
		if (enabled) {
			classes.push(providerClass)
		}
		await saveProviders(classes)
	}

	/**
	 * Move an enabled provider up or down in the try-order
	 *
	 * @param providerClass The provider class
	 * @param offset -1 to try it earlier, 1 to try it later
	 */
	async function moveProvider(providerClass: string, offset: -1 | 1): Promise<void> {
		const classes = enabledProviders.value.map((provider) => provider.class)
		const index = classes.indexOf(providerClass)
		const target = index + offset
		if (index === -1 || target < 0 || target >= classes.length) {
			return
		}
		[classes[index], classes[target]] = [classes[target]!, classes[index]!]
		await saveProviders(classes)
	}

	/**
	 * Go back to the default provider list
	 */
	async function resetProviders(): Promise<void> {
		await save(() => axios.delete(PROVIDERS_URL, { confirmPassword: PwdConfirmationMode.Lax }))
	}

	return {
		settings,
		saving,
		readOnly,
		enabledProviders,
		updateSettings,
		toggleProvider,
		moveProvider,
		resetProviders,
	}
})
