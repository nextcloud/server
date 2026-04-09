<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
  -->
<script setup lang="ts">
import type { IBackend } from '../types.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { ref, watch } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

const userMounting = loadState<{
	allowUserMounting: boolean
	allowedBackends: string[]
}>('files_external', 'user-mounting')

const availableBackends = loadState<IBackend[]>('files_external', 'backends')
	.filter((backend: IBackend) => backend.identifier !== 'local')
const allowUserMounting = ref(userMounting.allowUserMounting)
const allowedBackends = ref<string[]>(userMounting.allowedBackends)

/**
 * When changing the enabled state of the user-mounting settings then also change this on the server
 */
watch(allowUserMounting, () => {
	const backupValue = !allowUserMounting.value
	window.OCP.AppConfig.setValue(
		'files_external',
		'allow_user_mounting',
		allowUserMounting.value ? 'yes' : 'no',
		{
			success: () => showSuccess(t('files_external', 'Saved')),
			error: () => {
				allowUserMounting.value = backupValue
				showError(t('files_external', 'Error while saving'))
			},
		},
	)
})

/**
 * Save list of allowed backends on the server
 *
 * @param newValue - The new changed value
 * @param oldValue - The old value for resetting on failure
 */
watch(allowedBackends, (newValue, oldValue) => {
	// save to server
	window.OCP.AppConfig.setValue(
		'files_external',
		'user_mounting_backends',
		newValue.join(','),
		{
			success: () => showSuccess(t('files_external', 'Saved allowed backends')),
			error: () => {
				showError(t('files_external', 'Failed to save allowed backends'))
				allowedBackends.value = oldValue
			},
		},
	)
})
</script>

<template>
	<form>
		<h3 :class="$style.userMountSettings__heading">
			{{ t('files_external', 'Advanced options for external storage mounts') }}
		</h3>

		<NcCheckboxRadioSwitch v-model="allowUserMounting" type="switch">
			{{ t('files_external', 'Allow people to mount external storage') }}
		</NcCheckboxRadioSwitch>

		<fieldset v-show="allowUserMounting" :class="$style.userMountSettings__backends">
			<legend>
				{{ t('files_external', 'External storage backends people are allowed to mount') }}
			</legend>
			<NcCheckboxRadioSwitch
				v-for="backend of availableBackends"
				:key="backend.identifier"
				v-model="allowedBackends"
				:value="backend.identifier"
				name="allowUserMountingBackends[]">
				{{ backend.name }}
			</NcCheckboxRadioSwitch>
		</fieldset>
	</form>
</template>

<style module>
.userMountSettings__heading {
	font-weight: bold;
	font-size: 1.2rem;

	margin-block-start: var(--default-clickable-area);
}

.userMountSettings__backends {
	--padding: calc((var(--default-clickable-area) - 20px) / 2 + var(--default-grid-baseline));
	margin-block-start: var(--padding);
	margin-inline-start: var(--padding);

	legend {
		font-weight: bold;
	}
}
</style>
