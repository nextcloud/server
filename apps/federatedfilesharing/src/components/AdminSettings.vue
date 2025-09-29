<!--
 - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSettingsSection :name="t('federatedfilesharing', 'Federated Cloud Sharing')"
		:description="t('federatedfilesharing', 'Adjust how people can share between servers. This includes shares between people on this server as well if they are using federated sharing.')"
		:doc-url="sharingFederatedDocUrl">
		<NcCheckboxRadioSwitch type="switch"
			:checked.sync="outgoingServer2serverShareEnabled"
			@update:checked="update('outgoing_server2server_share_enabled', outgoingServer2serverShareEnabled)">
			{{ t('federatedfilesharing', 'Allow people on this server to send shares to other servers (this option also allows WebDAV access to public shares)') }}
		</NcCheckboxRadioSwitch>

		<NcCheckboxRadioSwitch type="switch"
			:checked.sync="incomingServer2serverShareEnabled"
			@update:checked="update('incoming_server2server_share_enabled', incomingServer2serverShareEnabled)">
			{{ t('federatedfilesharing', 'Allow people on this server to receive shares from other servers') }}
		</NcCheckboxRadioSwitch>

		<NcCheckboxRadioSwitch v-if="federatedGroupSharingSupported"
			type="switch"
			:checked.sync="outgoingServer2serverGroupShareEnabled"
			@update:checked="update('outgoing_server2server_group_share_enabled', outgoingServer2serverGroupShareEnabled)">
			{{ t('federatedfilesharing', 'Allow people on this server to send shares to groups on other servers') }}
		</NcCheckboxRadioSwitch>

		<NcCheckboxRadioSwitch v-if="federatedGroupSharingSupported"
			type="switch"
			:checked.sync="incomingServer2serverGroupShareEnabled"
			@update:checked="update('incoming_server2server_group_share_enabled', incomingServer2serverGroupShareEnabled)">
			{{ t('federatedfilesharing', 'Allow people on this server to receive group shares from other servers') }}
		</NcCheckboxRadioSwitch>

		<fieldset>
			<legend>{{ t('federatedfilesharing', 'The lookup server is only available for global scale.') }}</legend>

			<NcCheckboxRadioSwitch type="switch"
				:checked="lookupServerEnabled"
				disabled
				@update:checked="showLookupServerConfirmation">
				{{ t('federatedfilesharing', 'Search global and public address book for people') }}
			</NcCheckboxRadioSwitch>

			<NcCheckboxRadioSwitch type="switch"
				:checked="lookupServerUploadEnabled"
				disabled
				@update:checked="showLookupServerUploadConfirmation">
				{{ t('federatedfilesharing', 'Allow people to publish their data to a global and public address book') }}
			</NcCheckboxRadioSwitch>
		</fieldset>

		<!-- Trusted server handling -->
		<div class="settings-subsection">
			<h3 class="settings-subsection__name">
				{{ t('federatedfilesharing', 'Trusted federation') }}
			</h3>
			<NcCheckboxRadioSwitch type="switch"
				:checked.sync="federatedTrustedShareAutoAccept"
				@update:checked="update('federatedTrustedShareAutoAccept', federatedTrustedShareAutoAccept)">
				{{ t('federatedfilesharing', 'Automatically accept shares from trusted federated accounts and groups by default') }}
			</NcCheckboxRadioSwitch>
		</div>
	</NcSettingsSection>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { DialogBuilder, DialogSeverity, showError } from '@nextcloud/dialogs'
import { generateOcsUrl } from '@nextcloud/router'
import { confirmPassword } from '@nextcloud/password-confirmation'
import axios from '@nextcloud/axios'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'

import '@nextcloud/password-confirmation/dist/style.css'

export default {
	name: 'AdminSettings',

	components: {
		NcCheckboxRadioSwitch,
		NcSettingsSection,
	},

	data() {
		return {
			outgoingServer2serverShareEnabled: loadState('federatedfilesharing', 'outgoingServer2serverShareEnabled'),
			incomingServer2serverShareEnabled: loadState('federatedfilesharing', 'incomingServer2serverShareEnabled'),
			outgoingServer2serverGroupShareEnabled: loadState('federatedfilesharing', 'outgoingServer2serverGroupShareEnabled'),
			incomingServer2serverGroupShareEnabled: loadState('federatedfilesharing', 'incomingServer2serverGroupShareEnabled'),
			federatedGroupSharingSupported: loadState('federatedfilesharing', 'federatedGroupSharingSupported'),
			lookupServerEnabled: loadState('federatedfilesharing', 'lookupServerEnabled'),
			lookupServerUploadEnabled: loadState('federatedfilesharing', 'lookupServerUploadEnabled'),
			federatedTrustedShareAutoAccept: loadState('federatedfilesharing', 'federatedTrustedShareAutoAccept'),
			internalOnly: loadState('federatedfilesharing', 'internalOnly'),
			sharingFederatedDocUrl: loadState('federatedfilesharing', 'sharingFederatedDocUrl'),
		}
	},
	methods: {
		setLookupServerUploadEnabled(state) {
			if (state === this.lookupServerUploadEnabled) {
				return
			}
			this.lookupServerUploadEnabled = state
			this.update('lookupServerUploadEnabled', state)
		},

		async showLookupServerUploadConfirmation(state) {
			// No confirmation needed for disabling
			if (state === false) {
				return this.setLookupServerUploadEnabled(false)
			}

			const dialog = new DialogBuilder(t('federatedfilesharing', 'Confirm data upload to lookup server'))
			await dialog
				.setSeverity(DialogSeverity.Warning)
				.setText(
					t('federatedfilesharing', 'When enabled, all account properties (e.g. email address) with scope visibility set to "published", will be automatically synced and transmitted to an external system and made available in a public, global address book.'),
				)
				.addButton({
					callback: () => this.setLookupServerUploadEnabled(false),
					label: t('federatedfilesharing', 'Disable upload'),
				})
				.addButton({
					callback: () => this.setLookupServerUploadEnabled(true),
					label: t('federatedfilesharing', 'Enable data upload'),
					type: 'error',
				})
				.build()
				.show()
		},

		setLookupServerEnabled(state) {
			if (state === this.lookupServerEnabled) {
				return
			}
			this.lookupServerEnabled = state
			this.update('lookupServerEnabled', state)
		},

		async showLookupServerConfirmation(state) {
			// No confirmation needed for disabling
			if (state === false) {
				return this.setLookupServerEnabled(false)
			}

			const dialog = new DialogBuilder(t('federatedfilesharing', 'Confirm querying lookup server'))
			await dialog
				.setSeverity(DialogSeverity.Warning)
				.setText(
					t('federatedfilesharing', 'When enabled, the search input when creating shares will be sent to an external system that provides a public and global address book.')
					+ t('federatedfilesharing', 'This is used to retrieve the federated cloud ID to make federated sharing easier.')
					+ t('federatedfilesharing', 'Moreover, email addresses of users might be sent to that system in order to verify them.'),
				)
				.addButton({
					callback: () => this.setLookupServerEnabled(false),
					label: t('federatedfilesharing', 'Disable querying'),
				})
				.addButton({
					callback: () => this.setLookupServerEnabled(true),
					label: t('federatedfilesharing', 'Enable querying'),
					type: 'error',
				})
				.build()
				.show()
		},

		async update(key, value) {
			await confirmPassword()

			const url = generateOcsUrl('/apps/provisioning_api/api/v1/config/apps/{appId}/{key}', {
				appId: 'files_sharing',
				key,
			})

			const stringValue = value ? 'yes' : 'no'
			try {
				const { data } = await axios.post(url, {
					value: stringValue,
				})
				this.handleResponse({
					status: data.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('federatedfilesharing', 'Unable to update federated files sharing config'),
					error: e,
				})
			}
		},
		async handleResponse({ status, errorMessage, error }) {
			if (status !== 'ok') {
				showError(errorMessage)
				console.error(errorMessage, error)
			}
		},
	},
}
</script>
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
