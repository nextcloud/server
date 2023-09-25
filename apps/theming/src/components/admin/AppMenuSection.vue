<template>
	<NcSettingsSection :name="t('theming', 'App menu settings')">
		<h3>{{ t('theming', 'Default app') }}</h3>
		<p class="info-note">
			{{ t('theming', 'The default app is the app that is e.g. opened after login or when the logo in the menu is clicked.') }}
		</p>

		<h4>{{ t('theming', 'Global default app') }}</h4>
		<NcSelect v-model="selectedApps"
			:close-on-select="false"
			:placeholder="t('theming', 'Global default apps')"
			:options="allApps"
			:multiple="true" />
		<h5>{{ t('theming', 'Default app priority') }}</h5>
		<p class="info-note">
			{{ t('theming', 'If an app is not enabled for a user, the next app with lower priority is used.') }}
		</p>
		<AppOrderSelector :value.sync="selectedApps" />

		<h4>{{ t('theming', 'User defined app order') }}</h4>
		<NcCheckboxRadioSwitch type="switch"
			:checked.sync="isUserDefaultAppEnabled">
			{{ t('theming', 'Enable a per-user default app') }}
		</NcCheckboxRadioSwitch>
		<p class="info-note">
			{{ t('theming', 'Toggle this on to allow users to set their own default app.') }}
		</p>
	</NcSettingsSection>
</template>

<script lang="ts">
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, defineComponent } from 'vue'

import axios from '@nextcloud/axios'

import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import AppOrderSelector from '../AppOrderSelector.vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

export default defineComponent({
	name: 'AppMenuSection',
	components: {
		AppOrderSelector,
		NcCheckboxRadioSwitch,
		NcSelect,
		NcSettingsSection,
	},
	props: {
		defaultApps: {
			type: Array,
			required: true,
		},
		userDefaultAppEnabled: {
			type: Boolean,
			default: false,
		},
	},
	emits: {
		'update:defaultApps': (value: string[]) => Array.isArray(value) && value.every((id) => typeof id === 'string'),
		'update:userDefaultAppEnabled': (value: boolean) => typeof value === 'boolean',
	},
	setup(props, { emit }) {
		/**
		 * Is a per user default app enabled, wrapping a setter that calls the API
		 */
		const isUserDefaultAppEnabled = computed({
			get: () => props.userDefaultAppEnabled,
			set: (value) => saveSetting('userDefaultAppEnabled', value)
				.then(() => emit('update:userDefaultAppEnabled', value))
				.catch(() => showError(t('theming', 'Could not set app menu setting'))),
		})

		/**
		 * All enabled apps which can be navigated
		 */
		const allApps = Object.values(
			loadState<Record<string, { id: string, name?: string, icon: string }>>('core', 'apps'),
		).map(({ id, name, icon }) => ({ label: name, id, icon }))

		/**
		 * Currently selected app, wrapps the setter
		 */
		const selectedApps = computed({
			get: () => props.defaultApps.map((id) => allApps.filter(app => app.id === id)[0]),
			set(value) {
				saveSetting('defaultApps', value.map(app => app.id))
					.then(() => emit('update:defaultApps', value.map(app => app.id)))
					.catch(() => showError(t('theming', 'Could not set global default apps')))
			},
		})

		const saveSetting = async (key: string, value: any) => {
			const url = generateUrl('/apps/theming/ajax/updateAppMenu')
			return await axios.put(url, {
				setting: key,
				value,
			})
		}

		return {
			allApps,
			selectedApps,

			isUserDefaultAppEnabled,

			t,
		}
	},
})
</script>

<style scoped lang="scss">
h3, h4 {
	font-weight: bold;
}
h4, h5 {
	margin-block-start: 12px;
}

.info-note {
	color: var(--color-text-maxcontrast);
}
</style>
