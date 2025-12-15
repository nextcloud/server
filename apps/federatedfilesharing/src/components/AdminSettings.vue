<!--
 - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { OCSResponse } from '@nextcloud/typings/ocs'

import axios from '@nextcloud/axios'
import { showConfirmation, showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'
import { reactive } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import logger from '../services/logger.ts'

const sharingFederatedDocUrl = loadState<string>('federatedfilesharing', 'sharingFederatedDocUrl')

const internalState = new Proxy({
	outgoingServer2serverShareEnabled: [
		loadState<boolean>('federatedfilesharing', 'outgoingServer2serverShareEnabled'),
		'outgoing_server2server_share_enabled',
	],
	incomingServer2serverShareEnabled: [
		loadState<boolean>('federatedfilesharing', 'incomingServer2serverShareEnabled'),
		'incoming_server2server_share_enabled',
	],
	outgoingServer2serverGroupShareEnabled: [
		loadState<boolean>('federatedfilesharing', 'outgoingServer2serverGroupShareEnabled'),
		'outgoing_server2server_group_share_enabled',
	],
	incomingServer2serverGroupShareEnabled: [
		loadState<boolean>('federatedfilesharing', 'incomingServer2serverGroupShareEnabled'),
		'incoming_server2server_group_share_enabled',
	],
	federatedGroupSharingSupported: [
		loadState<boolean>('federatedfilesharing', 'federatedGroupSharingSupported'),
		'federated_group_sharing_supported',
	],
	federatedTrustedShareAutoAccept: [
		loadState<boolean>('federatedfilesharing', 'federatedTrustedShareAutoAccept'),
		'federatedTrustedShareAutoAccept',
	],
	lookupServerEnabled: [
		loadState<boolean>('federatedfilesharing', 'lookupServerEnabled'),
		'lookupServerEnabled',
	],
	lookupServerUploadEnabled: [
		loadState<boolean>('federatedfilesharing', 'lookupServerUploadEnabled'),
		'lookupServerUploadEnabled',
	],
}, {
	get(target, prop) {
		return target[prop]?.[0]
	},
	set(target, prop, value) {
		if (prop in target) {
			target[prop][0] = value
			updateAppConfig(target[prop][1], value)
			return true
		}
		return false
	},
})

const state = reactive<Record<string, boolean>>(internalState as never)

/**
 * Show confirmation dialog for enabling lookup server upload
 *
 * @param value - The new state
 */
async function showLookupServerUploadConfirmation(value: boolean) {
	// No confirmation needed for disabling
	if (value === false) {
		return state.lookupServerUploadEnabled = false
	}

	await showConfirmation({
		name: t('federatedfilesharing', 'Confirm data upload to lookup server'),
		text: t('federatedfilesharing', 'When enabled, all account properties (e.g. email address) with scope visibility set to "published", will be automatically synced and transmitted to an external system and made available in a public, global address book.'),
		labelConfirm: t('federatedfilesharing', 'Enable data upload'),
		labelReject: t('federatedfilesharing', 'Disable upload'),
		severity: 'warning',
	}).then(() => {
		state.lookupServerUploadEnabled = true
	}).catch(() => {
		state.lookupServerUploadEnabled = false
	})
}

/**
 * Show confirmation dialog for enabling lookup server
 *
 * @param value - The new state
 */
async function showLookupServerConfirmation(value: boolean) {
	// No confirmation needed for disabling
	if (value === false) {
		return state.lookupServerEnabled = false
	}

	await showConfirmation({
		name: t('federatedfilesharing', 'Confirm querying lookup server'),
		text: t('federatedfilesharing', 'When enabled, the search input when creating shares will be sent to an external system that provides a public and global address book.')
			+ t('federatedfilesharing', 'This is used to retrieve the federated cloud ID to make federated sharing easier.')
			+ t('federatedfilesharing', 'Moreover, email addresses of users might be sent to that system in order to verify them.'),
		labelConfirm: t('federatedfilesharing', 'Enable querying'),
		labelReject: t('federatedfilesharing', 'Disable querying'),
		severity: 'warning',
	}).then(() => {
		state.lookupServerEnabled = true
	}).catch(() => {
		state.lookupServerEnabled = false
	})
}

/**
 * Update the app config
 *
 * @param key - The config key
 * @param value - The config value
 */
async function updateAppConfig(key: string, value: boolean) {
	await confirmPassword()

	const url = generateOcsUrl('/apps/provisioning_api/api/v1/config/apps/{appId}/{key}', {
		appId: 'files_sharing',
		key,
	})

	const stringValue = value ? 'yes' : 'no'
	try {
		const { data } = await axios.post<OCSResponse>(url, {
			value: stringValue,
		})
		if (data.ocs.meta.status !== 'ok') {
			if (data.ocs.meta.message) {
				showError(data.ocs.meta.message)
				logger.error('Error updating federated files sharing config', { error: data.ocs })
			} else {
				throw new Error(`Failed to update federatedfilesharing config, ${data.ocs.meta.statuscode}`)
			}
		}
	} catch (error) {
		logger.error('Error updating federated files sharing config', { error })
		showError(t('federatedfilesharing', 'Unable to update federated files sharing config'))
	}
}
</script>

<template>
	<NcSettingsSection
		:name="t('federatedfilesharing', 'Federated Cloud Sharing')"
		:description="t('federatedfilesharing', 'Adjust how people can share between servers. This includes shares between people on this server as well if they are using federated sharing.')"
		:doc-url="sharingFederatedDocUrl">
		<NcCheckboxRadioSwitch
			v-model="state.outgoingServer2serverShareEnabled"
			type="switch">
			{{ t('federatedfilesharing', 'Allow people on this server to send shares to other servers (this option also allows WebDAV access to public shares)') }}
		</NcCheckboxRadioSwitch>

		<NcCheckboxRadioSwitch
			v-model="state.incomingServer2serverShareEnabled"
			type="switch">
			{{ t('federatedfilesharing', 'Allow people on this server to receive shares from other servers') }}
		</NcCheckboxRadioSwitch>

		<NcCheckboxRadioSwitch
			v-if="state.federatedGroupSharingSupported"
			v-model="state.outgoingServer2serverGroupShareEnabled"
			type="switch">
			{{ t('federatedfilesharing', 'Allow people on this server to send shares to groups on other servers') }}
		</NcCheckboxRadioSwitch>

		<NcCheckboxRadioSwitch
			v-if="state.federatedGroupSharingSupported"
			v-model="state.incomingServer2serverGroupShareEnabled"
			type="switch">
			{{ t('federatedfilesharing', 'Allow people on this server to receive group shares from other servers') }}
		</NcCheckboxRadioSwitch>

		<fieldset>
			<legend>{{ t('federatedfilesharing', 'The lookup server is only available for global scale.') }}</legend>

			<NcCheckboxRadioSwitch
				type="switch"
				:model-value="state.lookupServerEnabled"
				disabled
				@update:model-value="showLookupServerConfirmation">
				{{ t('federatedfilesharing', 'Search global and public address book for people') }}
			</NcCheckboxRadioSwitch>

			<NcCheckboxRadioSwitch
				type="switch"
				:model-value="state.lookupServerUploadEnabled"
				disabled
				@update:model-value="showLookupServerUploadConfirmation">
				{{ t('federatedfilesharing', 'Allow people to publish their data to a global and public address book') }}
			</NcCheckboxRadioSwitch>
		</fieldset>

		<!-- Trusted server handling -->
		<div class="settings-subsection">
			<h3 class="settings-subsection__name">
				{{ t('federatedfilesharing', 'Trusted federation') }}
			</h3>
			<NcCheckboxRadioSwitch
				v-model="state.federatedTrustedShareAutoAccept"
				type="switch">
				{{ t('federatedfilesharing', 'Automatically accept shares from trusted federated accounts and groups by default') }}
			</NcCheckboxRadioSwitch>
		</div>
	</NcSettingsSection>
</template>

<style scoped>
.settings-subsection {
	margin-top: 20px;
}

.settings-subsection__name {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	font-size: 16px;
	font-weight: bold;
	max-width: 900px;
	margin-top: 0;
}
</style>
