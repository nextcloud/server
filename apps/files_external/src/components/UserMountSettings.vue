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
	<form>
		<h3>{{ t('files_external', 'Advanced options for external storage mounts') }}</h3>

		<NcCheckboxRadioSwitch type="switch" :checked.sync="allowUserMounting">
			{{ t('files_external', 'Allow people to mount external storage') }}
		</NcCheckboxRadioSwitch>

		<fieldset v-show="allowUserMounting" class="files-external__user-backends">
			<legend>
				{{ t('files_external', 'External storage backends people are allowed to mount') }}
			</legend>
			<template v-for="backend of availableBackends">
				<NcCheckboxRadioSwitch v-if="!backend.deprecated"
					:key="backend.id"
					:checked.sync="allowedBackends"
					:value="backend.id"
					name="allowUserMountingBackends[]">
					{{ backend.displayName }}
				</NcCheckboxRadioSwitch>
				<input v-else-if="backend.id in allowedBackends"
					:key="`${backend.id}-deprecated`"
					:data-deprecate-to="backend.deprecated"
					:value="backend.id"
					name="allowUserMountingBackends[]"
					type="hidden">
			</template>
		</fieldset>
	</form>
</template>

<script lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import { showError, showSuccess } from '@nextcloud/dialogs'

const userMounting = loadState<{
	allowUserMounting: boolean,
	allowedBackends: string[],
	backends: {
		id: string
		displayName: string
		deprecated?: string
	}[]
}>('files_external', 'user-mounting')

export default defineComponent({
	name: 'UserMountSettings',

	components: {
		NcCheckboxRadioSwitch,
	},

	setup() {
		// non reactive props
		return {
			availableBackends: userMounting.backends,
		}
	},

	data() {
		return {
			allowUserMounting: userMounting.allowUserMounting,
			allowedBackends: userMounting.allowedBackends,
		}
	},

	watch: {
		/**
		 * When changing the enabled state of the user-mounting settings then also change this on the server
		 */
		allowUserMounting() {
			const backupValue = !this.allowUserMounting
			window.OCP.AppConfig.setValue(
				'files_external',
				'allow_user_mounting',
				this.allowUserMounting ? 'yes' : 'no',
				{
					success: () => showSuccess(t('files_external', 'Saved')),
					error: () => {
						this.allowUserMounting = backupValue
						showError(t('files_external', 'Error while saving'))
					},
				},
			)
		},

		/**
		 * Save list of allowed backends on the server
		 * @param newValue The new changed value
		 * @param oldValue The old value for resetting on failure
		 */
		allowedBackends(newValue, oldValue) {
			// save to server
			window.OCP.AppConfig.setValue(
				'files_external',
				'user_mounting_backends',
				newValue.join(','),
				{
					success: () => showSuccess(t('files_external', 'Saved allowed backends')),
					error: () => {
						showError(t('files_external', 'Failed to save allowed backends'))
						this.allowedBackends = oldValue
					},
				},
			)
		},
	},

	methods: {
		t,
	},
})
</script>

<style scoped lang="scss">
h3 {
	font-weight: bold;
	font-size: 17px;

	margin-block-start: 44px;
}

.files-external__user-backends {
	margin-block-start: 14px;
	margin-inline-start: 14px;

	legend {
		font-weight: bold;
	}
}
</style>
