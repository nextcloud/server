/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { MaybeRef, MaybeRefOrGetter, Ref } from 'vue'

import axios, { isAxiosError } from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { watchDebounced } from '@vueuse/core'
import { isReadonly, isRef, readonly, ref, toValue } from 'vue'
import { logger } from '../utils/logger.ts'

/**
 * @param name - The property name
 * @param modelValue - The model value
 * @param defaultValue - The default value
 */
export function useAdminThemingValue<T>(name: MaybeRefOrGetter<string>, modelValue: Ref<T>, defaultValue: MaybeRef<T>) {
	let resetted = false
	const isSaving = ref(false)
	const isSaved = ref(false)

	watchDebounced(modelValue, async () => {
		if (isSaving.value) {
			return
		}

		if (resetted) {
			resetted = false
			return
		}

		isSaving.value = true
		isSaved.value = false
		try {
			await setValue(toValue(name), toValue(modelValue))
			isSaved.value = true
			window.setTimeout(() => {
				isSaved.value = false
			}, 2000)
		} finally {
			isSaving.value = false
		}
	}, { debounce: 800, flush: 'sync' })

	/**
	 * Reset to default value
	 */
	async function reset() {
		isSaving.value = true
		isSaved.value = false
		try {
			const result = await resetValue(toValue(name))
			if (result && isRef(defaultValue) && !isReadonly(defaultValue)) {
				defaultValue.value = result as T
			}
			resetted = true
			modelValue.value = toValue(defaultValue)
		} finally {
			isSaving.value = false
		}
	}

	return {
		isSaving: readonly(isSaving),
		isSaved: readonly(isSaved),
		reset,
	}
}

/**
 * @param setting - The setting name
 * @param value - The setting value
 */
async function setValue(setting: string, value: unknown) {
	const url = generateUrl('/apps/theming/ajax/updateStylesheet')
	try {
		await axios.post(url, {
			setting,
			value: String(value),
		})
	} catch (error) {
		logger.error('Failed to save changes', { error, setting, value })
		if (isAxiosError(error) && error.response?.data?.data?.message) {
			showError(error.response.data.data.message)
		}
		throw error
	}
}

/**
 * Reset theming value for a given setting
 *
 * @param setting - The setting name
 */
async function resetValue(setting: string) {
	const url = generateUrl('/apps/theming/ajax/undoChanges')
	try {
		const { data } = await axios.post<{ data: { value?: string } }>(url, { setting })
		return data.data.value
	} catch (error) {
		logger.error('Failed to reset theming value', { error, setting })
		if (isAxiosError(error) && error.response?.data?.data?.message) {
			showError(error.response.data.data.message)
			return false
		}
		throw error
	}
}
