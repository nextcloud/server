<!--
	- @copyright 2022 Carl Schwan <carl@carlschwan.eu>
	-
	- @author Carl Schwan <carl@carlschwan.eu>
	-
	- @license GNU AGPL version 3 or any later version
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<NcSettingsSection :name="t('settings', 'Server-side encryption')"
		:description="t('settings', 'Server-side encryption makes it possible to encrypt files which are uploaded to this server. This comes with limitations like a performance penalty, so enable this only if needed.')"
		:doc-url="encryptionAdminDoc">
		<NcCheckboxRadioSwitch :checked="encryptionEnabled || shouldDisplayWarning"
			:disabled="encryptionEnabled"
			type="switch"
			@update:checked="displayWarning">
			{{ t('settings', 'Enable server-side encryption') }}
		</NcCheckboxRadioSwitch>

		<div v-if="shouldDisplayWarning && !encryptionEnabled" class="notecard warning" role="alert">
			<p>{{ t('settings', 'Please read carefully before activating server-side encryption:') }}</p>
			<ul>
				<li>{{ t('settings', 'Once encryption is enabled, all files uploaded to the server from that point forward will be encrypted at rest on the server. It will only be possible to disable encryption at a later date if the active encryption module supports that function, and all pre-conditions (e.g. setting a recover key) are met.') }}</li>
				<li>{{ t('settings', 'Encryption alone does not guarantee security of the system. Please see documentation for more information about how the encryption app works, and the supported use cases.') }}</li>
				<li>{{ t('settings', 'Be aware that encryption always increases the file size.') }}</li>
				<li>{{ t('settings', 'It is always good to create regular backups of your data, in case of encryption make sure to backup the encryption keys along with your data.') }}</li>
			</ul>

			<p class="margin-bottom">
				{{ t('settings', 'This is the final warning: Do you really want to enable encryption?') }}
			</p>
			<NcButton type="primary"
				@click="enableEncryption()">
				{{ t('settings', "Enable encryption") }}
			</NcButton>
		</div>

		<div v-if="encryptionEnabled">
			<div v-if="encryptionReady">
				<p v-if="encryptionModules.length === 0">
					{{ t('settings', 'No encryption module loaded, please enable an encryption module in the app menu.') }}
				</p>
				<template v-else>
					<h3>{{ t('settings', 'Select default encryption module:') }}</h3>
					<fieldset>
						<NcCheckboxRadioSwitch v-for="(module, id) in encryptionModules"
							:key="id"
							:checked.sync="defaultCheckedModule"
							:value="id"
							type="radio"
							name="default_encryption_module"
							@update:checked="checkDefaultModule">
							{{ module.displayName }}
						</NcCheckboxRadioSwitch>
					</fieldset>
				</template>
			</div>

			<div v-else-if="externalBackendsEnabled" v-html="migrationMessage" />
		</div>
	</NcSettingsSection>
</template>

<script>
import axios from '@nextcloud/axios'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'

import { loadState } from '@nextcloud/initial-state'
import { generateOcsUrl } from '@nextcloud/router'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { showError } from '@nextcloud/dialogs'

import { logger } from '../utils/logger.ts'

export default {
	name: 'Encryption',
	components: {
		NcCheckboxRadioSwitch,
		NcSettingsSection,
		NcButton,
	},
	data() {
		const encryptionModules = loadState('settings', 'encryption-modules')
		return {
			encryptionReady: loadState('settings', 'encryption-ready'),
			encryptionEnabled: loadState('settings', 'encryption-enabled'),
			externalBackendsEnabled: loadState('settings', 'external-backends-enabled'),
			encryptionAdminDoc: loadState('settings', 'encryption-admin-doc'),
			encryptionModules,
			shouldDisplayWarning: false,
			migrating: false,
			defaultCheckedModule: Object.entries(encryptionModules).find((module) => module[1].default)[0],
		}
	},
	computed: {
		migrationMessage() {
			return t('settings', 'You need to migrate your encryption keys from the old encryption (ownCloud <= 8.0) to the new one. Please enable the "Default encryption module" and run {command}', {
				command: '"occ encryption:migrate"',
			})
		},
	},
	methods: {
		displayWarning() {
			if (!this.encryptionEnabled) {
				this.shouldDisplayWarning = !this.shouldDisplayWarning
			} else {
				this.encryptionEnabled = false
				this.shouldDisplayWarning = false
			}
		},
		async update(key, value) {
			await confirmPassword()

			const url = generateOcsUrl('/apps/provisioning_api/api/v1/config/apps/{appId}/{key}', {
				appId: 'core',
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
					errorMessage: t('settings', 'Unable to update server side encryption config'),
					error: e,
				})
			}
		},
		async checkDefaultModule() {
			await this.update('default_encryption_module', this.defaultCheckedModule)
		},
		async enableEncryption() {
			this.encryptionEnabled = true
			await this.update('encryption_enabled', true)
		},
		async handleResponse({ status, errorMessage, error }) {
			if (status !== 'ok') {
				showError(errorMessage)
				logger.error(errorMessage, { error })
			}
		},
	},
}
</script>

<style lang="scss" scoped>

.notecard.success {
	--note-background: rgba(var(--color-success-rgb), 0.2);
	--note-theme: var(--color-success);
}

.notecard.error {
	--note-background: rgba(var(--color-error-rgb), 0.2);
	--note-theme: var(--color-error);
}

.notecard.warning {
	--note-background: rgba(var(--color-warning-rgb), 0.2);
	--note-theme: var(--color-warning);
}

#body-settings .notecard {
	color: var(--color-text-light);
	background-color: var(--note-background);
	border: 1px solid var(--color-border);
	border-left: 4px solid var(--note-theme);
	border-radius: var(--border-radius);
	box-shadow: rgba(43, 42, 51, 0.05) 0px 1px 2px 0px;
	margin: 1rem 0;
	margin-top: 1rem;
	padding: 1rem;
}

li {
	list-style-type: initial;
	margin-left: 1rem;
	padding: 0.25rem 0;
}

.margin-bottom {
	margin-bottom: 0.75rem;
}
</style>
