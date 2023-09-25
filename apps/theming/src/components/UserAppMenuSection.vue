<template>
	<NcSettingsSection :name="t('theming', 'App menu settings')">
		<p>
			{{ t('theming', 'You can configure the default app, the default app is the app that is opened after login or when clicking on the logo.') }}
		</p>
		<h3>
			{{ t('theming', 'Default app') }}
		</h3>
		<NcSelect v-model="defaultApp"
			:close-on-select="false"
			:placeholder="t('theming', 'Global default apps')"
			:options="allApps"
			:multiple="false" />
	</NcSettingsSection>
</template>

<script lang="ts">
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, defineComponent, ref } from 'vue'

import axios from '@nextcloud/axios'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'

export default defineComponent({
	name: 'UserAppMenuSection',
	components: {
		NcSelect,
		NcSettingsSection,
	},
	setup() {
		/**
		 * Array of all available apps, it is set by a core controller for the app menu, so it is always available
		 */
		const allApps = Object.values(
			loadState<Record<string, { id: string, name?: string, icon: string }>>('core', 'apps'),
		).map(({ id, name, icon }) => ({ label: name, id, icon }))

		/**
		 * The currently selected default app
		 */
		const currentDefaultApp = ref<string|undefined>(loadState<string>('theming', 'userDefaultApp'))
		/**
		 * The currently selected default app, wrapping a setter that saves the changes to the backend
		 */
		const defaultApp = computed<typeof allApps[number]|null>({
			get: () => allApps.filter(({ id }) => id === currentDefaultApp.value)[0],
			set: (value) => saveSetting('userDefaultApp', value?.id)
				.then(() => { currentDefaultApp.value = value?.id })
				.catch((error) => {
					console.warn('Could not set the default app', error)
					showError(t('theming', 'Could not set the default app'))
				}),
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
			defaultApp,

			t,
		}
	},
})
</script>
