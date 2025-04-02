<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { OCSResponse } from '@nextcloud/typings/ocs'
import { showError, spawnDialog } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'
import { ref } from 'vue'
import { textExistingFilesNotEncrypted } from './sharedTexts.ts'

import axios from '@nextcloud/axios'
import logger from '../../logger.ts'

import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import EncryptionWarningDialog from './EncryptionWarningDialog.vue'

interface EncryptionModule {
	default?: boolean
	displayName: string
}

const allEncryptionModules = loadState<never[]|Record<string, EncryptionModule>>('settings', 'encryption-modules')
/** Available encryption modules on the backend */
const encryptionModules = Array.isArray(allEncryptionModules) ? [] : Object.entries(allEncryptionModules).map(([id, module]) => ({ ...module, id }))
/** ID of the default encryption module */
const defaultCheckedModule = encryptionModules.find((module) => module.default)?.id

/** Is the server side encryptio ready to be enabled */
const encryptionReady = loadState<boolean>('settings', 'encryption-ready')
/** Are external backends enabled (legacy ownCloud stuff) */
const externalBackendsEnabled = loadState<boolean>('settings', 'external-backends-enabled')
/** URL to the admin docs */
const encryptionAdminDoc = loadState<string>('settings', 'encryption-admin-doc')

/** Is the encryption enabled */
const encryptionEnabled = ref(loadState<boolean>('settings', 'encryption-enabled'))

/** Loading state while enabling encryption (e.g. because the confirmation dialog is open) */
const loadingEncryptionState = ref(false)

/**
 * Open the encryption-enabling warning (spawns a dialog)
 * @param enabled The enabled state of encryption
 */
function displayWarning(enabled: boolean) {
	if (loadingEncryptionState.value || enabled === false) {
		return
	}

	loadingEncryptionState.value = true
	spawnDialog(EncryptionWarningDialog, {}, async (confirmed) => {
		try {
			if (confirmed) {
				await enableEncryption()
			}
		} finally {
			loadingEncryptionState.value = false
		}
	})
}

/**
 * Update an encryption setting on the backend
 * @param key The setting to update
 * @param value The new value
 */
async function update(key: string, value: string) {
	await confirmPassword()

	const url = generateOcsUrl('/apps/provisioning_api/api/v1/config/apps/{appId}/{key}', {
		appId: 'core',
		key,
	})

	try {
		const { data } = await axios.post<OCSResponse>(url, {
			value,
		})
		if (data.ocs.meta.status !== 'ok') {
			throw new Error('Unsuccessful OCS response', { cause: data.ocs })
		}
	} catch (error) {
		showError(t('settings', 'Unable to update server side encryption config'))
		logger.error('Unable to update server side encryption config', { error })
		return false
	}
	return true
}

/**
 * Choose the default encryption module
 */
async function checkDefaultModule(): Promise<void> {
	if (defaultCheckedModule) {
		await update('default_encryption_module', defaultCheckedModule)
	}
}

/**
 * Enable encryption - sends an async POST request
 */
async function enableEncryption(): Promise<void> {
	encryptionEnabled.value = await update('encryption_enabled', 'yes')
}
</script>

<template>
	<NcSettingsSection :name="t('settings', 'Server-side encryption')"
		:description="t('settings', 'Server-side encryption makes it possible to encrypt files which are uploaded to this server. This comes with limitations like a performance penalty, so enable this only if needed.')"
		:doc-url="encryptionAdminDoc">
		<NcNoteCard v-if="encryptionEnabled" type="info">
			<p>
				{{ textExistingFilesNotEncrypted }}
				{{ t('settings', 'To encrypt all existing files run this OCC command:') }}
			</p>
			<code>
				<pre>occ encryption:encrypt-all</pre>
			</code>
		</NcNoteCard>

		<NcCheckboxRadioSwitch :class="{ disabled: encryptionEnabled }"
			:checked="encryptionEnabled"
			:aria-disabled="encryptionEnabled ? 'true' : undefined"
			:aria-describedby="encryptionEnabled ? 'server-side-encryption-disable-hint' : undefined"
			:loading="loadingEncryptionState"
			type="switch"
			@update:checked="displayWarning">
			{{ t('settings', 'Enable server-side encryption') }}
		</NcCheckboxRadioSwitch>
		<p v-if="encryptionEnabled" id="server-side-encryption-disable-hint" class="disable-hint">
			{{ t('settings', 'Disabling server side encryption is only possible using OCC, please refer to the documentation.') }}
		</p>

		<NcNoteCard v-if="encryptionModules.length === 0"
			type="warning"
			:text="t('settings', 'No encryption module loaded, please enable an encryption module in the app menu.')" />

		<template v-else-if="encryptionEnabled">
			<div v-if="encryptionReady && encryptionModules.length > 0">
				<h3>{{ t('settings', 'Select default encryption module:') }}</h3>
				<fieldset>
					<NcCheckboxRadioSwitch v-for="module in encryptionModules"
						:key="module.id"
						:checked.sync="defaultCheckedModule"
						:value="module.id"
						type="radio"
						name="default_encryption_module"
						@update:checked="checkDefaultModule">
						{{ module.displayName }}
					</NcCheckboxRadioSwitch>
				</fieldset>
			</div>

			<div v-else-if="externalBackendsEnabled">
				{{
					t(
						'settings',
						'You need to migrate your encryption keys from the old encryption (ownCloud <= 8.0) to the new one. Please enable the "Default encryption module" and run {command}',
						{ command: '"occ encryption:migrate"' },
					)
				}}
			</div>
		</template>
	</NcSettingsSection>
</template>

<style scoped>
code {
	background-color: var(--color-background-dark);
	color: var(--color-main-text);

	display: block;
	margin-block-start: 0.5rem;
	padding: .25lh .5lh;
	width: fit-content;
}

.disabled {
	opacity: .75;
}

.disabled :deep(*) {
	cursor: not-allowed !important;
}

.disable-hint {
	color: var(--color-text-maxcontrast);
	padding-inline-start: 10px;
}
</style>
