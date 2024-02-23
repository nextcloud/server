<!--
  - @copyright 2022 Carl Schwan <carl@carlschwan.eu>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->
<template>
	<NcSettingsSection :name="t('settings', 'Password')">
		<form id="passwordform" method="POST" @submit.prevent="changePassword">
			<NcPasswordField id="old-pass"
				:label="t('settings', 'Current password')"
				name="oldpassword"
				:value.sync="oldPass"
				autocomplete="current-password"
				autocapitalize="none"
				spellcheck="false" />

			<NcPasswordField id="new-pass"
				:label="t('settings', 'New password')"
				:value.sync="newPass"
				:maxlength="469"
				autocomplete="new-password"
				autocapitalize="none"
				spellcheck="false"
				:check-password-strength="true" />

			<NcButton type="primary"
				native-type="submit"
				:disabled="newPass.length === 0 || oldPass.length === 0">
				{{ t('settings', 'Change password') }}
			</NcButton>
		</form>
	</NcSettingsSection>
</template>

<script>
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'PasswordSection',
	components: {
		NcSettingsSection,
		NcButton,
		NcPasswordField,
	},
	data() {
		return {
			oldPass: '',
			newPass: '',
		}
	},
	methods: {
		changePassword() {
			axios.post(generateUrl('/settings/personal/changepassword'), {
				oldpassword: this.oldPass,
				newpassword: this.newPass,
			})
				.then(res => res.data)
				.then(data => {
					if (data.status === 'error') {
						this.errorMessage = data.data.message
						showError(data.data.message)
					} else {
						showSuccess(data.data.message)
					}
				})
		},
	},
}
</script>

<style>
	#passwordform {
		display: flex;
		flex-direction: column;
		gap: 1rem;
		max-width: 400px;
	}
</style>
