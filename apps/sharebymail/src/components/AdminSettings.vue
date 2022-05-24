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
	<SettingsSection :title="t('sharebymail', 'Share by mail')"
		:description="t('sharebymail', 'Allows users to share a personalized link to a file or folder by putting in an email address.')">
		<CheckboxRadioSwitch type="switch"
			:checked.sync="sendPasswordMail"
			@update:checked="update('sendpasswordmail', sendPasswordMail)">
			{{ t('sharebymail', 'Send password by mail') }}
		</CheckboxRadioSwitch>

		<CheckboxRadioSwitch type="switch"
			:checked.sync="replyToInitiator"
			@update:checked="update('replyToInitiator', replyToInitiator)">
			{{ t('sharebymail', 'Reply to initiator') }}
		</CheckboxRadioSwitch>
	</SettingsSection>
</template>

<script>
import CheckboxRadioSwitch from '@nextcloud/vue/dist/Components/CheckboxRadioSwitch'
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection'
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import confirmPassword from '@nextcloud/password-confirmation'

export default {
	name: 'AdminSettings',
	components: {
		CheckboxRadioSwitch,
		SettingsSection,
	},
	data() {
		return {
			sendPasswordMail: loadState('sharebymail', 'sendPasswordMail'),
			replyToInitiator: loadState('sharebymail', 'replyToInitiator'),
		}
	},
	methods: {
		async update(key, value) {
			await confirmPassword()
			const url = generateOcsUrl('/apps/provisioning_api/api/v1/config/apps/{appId}/{key}', {
				appId: 'sharebymail',
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
					errorMessage: t('sharebymail', 'Unable to update share by mail config'),
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
