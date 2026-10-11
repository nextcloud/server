<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { getCapabilities } from '@nextcloud/capabilities'
import {
	showError,
	TOAST_DEFAULT_TIMEOUT,
	TOAST_PERMANENT_TIMEOUT,
} from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { computed, ref } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import { logger } from '../utils/logger.ts'

type ThemingCapabilities = {
	theming?: {
		toastTimeout?: number
		toastTimeoutValues?: number[]
	}
}

/**
 * Read the effective toast timeout from theming capabilities.
 */
function readToastTimeout(): number {
	const timeout = (getCapabilities() as ThemingCapabilities)?.theming?.toastTimeout
	if (typeof timeout === 'number' && (timeout === TOAST_PERMANENT_TIMEOUT || timeout > 0)) {
		return timeout
	}
	return TOAST_DEFAULT_TIMEOUT
}

/**
 * Allowed toast timeout values in milliseconds, from theming capabilities.
 */
function readToastTimeoutValues(): number[] {
	const values = (getCapabilities() as ThemingCapabilities)?.theming?.toastTimeoutValues
	if (Array.isArray(values) && values.length > 0 && values.every((value) => typeof value === 'number')) {
		return values
	}
	return [TOAST_DEFAULT_TIMEOUT, TOAST_PERMANENT_TIMEOUT]
}

/**
 * Human-readable label for a toast timeout value.
 *
 * @param value - Timeout in milliseconds
 */
function labelForTimeout(value: number): string {
	if (value === TOAST_PERMANENT_TIMEOUT) {
		return t('theming', 'Never dismiss')
	}
	if (value === TOAST_DEFAULT_TIMEOUT) {
		return t('theming', 'Default ({time} seconds)', { time: value / 1000 })
	}
	return t('theming', '{time} seconds', { time: value / 1000 })
}

/**
 * Update the in-memory theming capability so subsequent toasts use the new timeout
 * without requiring a page reload.
 *
 * @param timeout - Timeout in milliseconds
 */
function applyToastTimeoutCapability(timeout: number): void {
	const capabilities = getCapabilities() as ThemingCapabilities
	if (capabilities.theming) {
		capabilities.theming.toastTimeout = timeout
	}
}

const toastTimeout = ref(readToastTimeout())

const options = computed(() => readToastTimeoutValues().map((value) => ({
	value,
	label: labelForTimeout(value),
})))

/**
 * Persist and apply the selected toast timeout
 *
 * @param value - Selected timeout preference value
 */
async function updateToastTimeout(value: string | number | boolean) {
	const nextValue = Number(value)
	const previous = toastTimeout.value
	toastTimeout.value = nextValue
	applyToastTimeoutCapability(nextValue)

	const url = generateOcsUrl('apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
		appId: 'theming',
		configKey: 'toast_timeout',
	})

	try {
		await axios.post(url, {
			configValue: String(nextValue),
		})
	} catch (error) {
		toastTimeout.value = previous
		applyToastTimeoutCapability(previous)
		logger.error('Could not update toast timeout', { error })
		showError(t('theming', 'Could not update toast timeout'))
	}
}
</script>

<template>
	<NcSettingsSection
		:name="t('theming', 'Toast notifications')"
		:description="t('theming', 'Set how long toast messages stay visible. Choose a longer duration if you need more time to read them.')">
		<fieldset class="toast-timeout">
			<legend class="hidden-visually">
				{{ t('theming', 'Toast timeout') }}
			</legend>
			<NcCheckboxRadioSwitch
				v-for="option in options"
				:key="option.value"
				:modelValue="toastTimeout"
				type="radio"
				name="toast_timeout"
				:value="option.value"
				@update:modelValue="updateToastTimeout">
				{{ option.label }}
			</NcCheckboxRadioSwitch>
		</fieldset>
	</NcSettingsSection>
</template>

<style scoped lang="scss">
.toast-timeout {
	display: flex;
	flex-direction: column;
	gap: 4px;
	border: 0;
	margin: 0;
	padding: 0;
}
</style>
