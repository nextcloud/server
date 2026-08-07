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

const TOAST_TIMEOUT_15S = 15_000
const TOAST_TIMEOUT_30S = 30_000

type ThemingCapabilities = {
	theming?: {
		toastTimeout?: number
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

const options = computed(() => [
	{
		value: TOAST_DEFAULT_TIMEOUT,
		label: t('theming', 'Default ({time} seconds)', { time: TOAST_DEFAULT_TIMEOUT / 1000 }),
	},
	{
		value: TOAST_TIMEOUT_15S,
		label: t('theming', '15 seconds'),
	},
	{
		value: TOAST_TIMEOUT_30S,
		label: t('theming', '30 seconds'),
	},
	{
		value: TOAST_PERMANENT_TIMEOUT,
		label: t('theming', 'Never dismiss'),
	},
])

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
		if (nextValue === TOAST_DEFAULT_TIMEOUT) {
			await axios.delete(url)
		} else {
			await axios.post(url, {
				configValue: String(nextValue),
			})
		}
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
