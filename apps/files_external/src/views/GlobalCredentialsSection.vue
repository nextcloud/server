<!--
  - @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
  -
  - @author Ferdinand Thiessen <opensource@fthiessen.de>
  -
  - @license AGPL-3.0-or-later
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
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<NcSettingsSection :name="t('files_external', 'Global credentials')"
		:description="t('files_external', 'Global credentials can be used to authenticate with multiple external storages that have the same credentials.')">
		<form id="global_credentials"
			autocomplete="false"
			class="files-external__global-credentials"
			@submit.prevent="onSubmit">
			<NcTextField name="username"
				autocomplete="false"
				:value.sync="username"
				:label="t('files_external', 'Login')" />
			<NcPasswordField name="password"
				autocomplete="false"
				:value.sync="password"
				:label="t('files_external', 'Password')" />
			<NcButton class="files-external__global-credentials-submit"
				:disabled="loading"
				type="primary"
				native-type="submit">
				{{ loading ? t('files_external', 'Saving …') : t('files_external', 'Save') }}
			</NcButton>
		</form>
	</NcSettingsSection>
</template>

<script lang="ts">
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { defineComponent } from 'vue'

import axios from '@nextcloud/axios'
import logger from '../utils/logger'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import { loadState } from '@nextcloud/initial-state'

const globalCredentials = loadState<{
	uid: string
	user: string
	password: string
}>('files_external', 'global-credentials')

export default defineComponent({
	name: 'GlobalCredentialsSection',

	components: {
		NcButton,
		NcPasswordField,
		NcSettingsSection,
		NcTextField,
	},

	data() {
		return {
			loading: false,

			username: globalCredentials.user,
			password: globalCredentials.password,
		}
	},

	methods: {
		t,

		async onSubmit() {
			try {
				this.loading = true
				const { data } = await axios.post<boolean>(generateUrl('apps/files_external/globalcredentials'), {
					// This is the UID of the user to save the credentials (admins can set that also for other users)
					uid: globalCredentials.uid,
					user: this.username,
					password: this.password,
				})
				if (data) {
					showSuccess(t('files_external', 'Global credentials saved'))
					return
				}
			} catch (e) {
				logger.error(e as Error)
				// Error is handled below
			} finally {
				this.loading = false
			}
			// result was false so show an error
			showError(t('files_external', 'Could not save global credentials'))
			this.username = globalCredentials.user
			this.password = globalCredentials.password
		},
	},
})
</script>

<style scoped lang="scss">
.files-external__global-credentials {
	max-width: 400px;
	display: flex;
	flex-direction: column;
	align-items: end;
	gap: 15px;

	#{&} &-submit {
		min-width: max(40%, 44px);
	}
}
</style>
