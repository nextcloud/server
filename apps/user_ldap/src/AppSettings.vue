<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection :name="t('user_ldap', 'Configuration-independent settings')">
		<ul class="user_ldap__app-settings-list">
			<li>
				<NcCheckboxRadioSwitch :checked="config.backend_mark_remnants_as_disabled"
					type="switch"
					@update:checked="updateBoolSetting('backend_mark_remnants_as_disabled')">
					{{ t('user_ldap', 'Disable users missing from LDAP') }}
				</NcCheckboxRadioSwitch>
			</li>
		</ul>
	</NcSettingsSection>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'

import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'

export default {
	name: 'AppSettings',
	components: {
		NcCheckboxRadioSwitch,
		NcSettingsSection,
	},

	data() {
		return {
			config: loadState('user_ldap', 'config'),
		}
	},

	methods: {
		async updateBoolSetting(setting) {
			OCP.AppConfig.setValue('user_ldap', setting, this.config[setting] ? '0' : '1', {
				success: () => {
					showSuccess(t('user_ldap', 'Settings saved'))
					this.config[setting] = !this.config[setting]
				},
				error: () => showError(t('user_ldap', 'Error while saving settings')),
			})
		},
	},
}
</script>
