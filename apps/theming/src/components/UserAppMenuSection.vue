<template>
	<NcSettingsSection :name="t('theming', 'Navigation bar settings')">
		<p>
			{{ t('theming', 'You can configure the app order used for the navigation bar. The first entry will be the default app, opened after login or when clicking on the logo.') }}
		</p>
		<NcNoteCard v-if="!!appOrder[0]?.default" type="info">
			{{ t('theming', 'The default app can not be changed because it was configured by the administrator.') }}
		</NcNoteCard>
		<NcNoteCard v-if="hasAppOrderChanged" type="info">
			{{ t('theming', 'The app order was changed, to see it in action you have to reload the page.') }}
		</NcNoteCard>
		<AppOrderSelector class="user-app-menu-order" :value.sync="appOrder" />
	</NcSettingsSection>
</template>

<script lang="ts">
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { computed, defineComponent, ref } from 'vue'

import axios from '@nextcloud/axios'
import AppOrderSelector from './AppOrderSelector.vue'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'

/** See NavigationManager */
interface INavigationEntry {
	/** Navigation id */
	id: string
	/** Order where this entry should be shown */
	order: number
	/** Target of the navigation entry */
	href: string
	/** The icon used for the naviation entry */
	icon: string
	/** Type of the navigation entry ('link' vs 'settings') */
	type: 'link' | 'settings'
	/** Localized name of the navigation entry */
	name: string
	/** Whether this is the default app */
	default?: boolean
	/** App that registered this navigation entry (not necessarly the same as the id) */
	app: string
	/** The key used to identify this entry in the navigations entries */
	key: number
}

export default defineComponent({
	name: 'UserAppMenuSection',
	components: {
		AppOrderSelector,
		NcNoteCard,
		NcSettingsSection,
	},
	setup() {
		/**
		 * Track if the app order has changed, so the user can be informed to reload
		 */
		const hasAppOrderChanged = ref(false)

		/** The enforced default app set by the administrator (if any) */
		const enforcedDefaultApp = loadState<string|null>('theming', 'enforcedDefaultApp', null)

		/**
		 * Array of all available apps, it is set by a core controller for the app menu, so it is always available
		 */
		const allApps = ref(
			Object.values(loadState<Record<string, INavigationEntry>>('core', 'apps'))
				.filter(({ type }) => type === 'link')
				.map((app) => ({ ...app, label: app.name, default: app.default && app.app === enforcedDefaultApp })),
		)

		/**
		 * Wrapper around the sortedApps list with a setter for saving any changes
		 */
		const appOrder = computed({
			get: () => allApps.value,
			set: (value) => {
				const order = {} as Record<string, Record<number, number>>
				value.forEach(({ app, key }, index) => {
					order[app] = { ...order[app], [key]: index }
				})

				saveSetting('apporder', order)
					.then(() => {
						allApps.value = value
						hasAppOrderChanged.value = true
					})
					.catch((error) => {
						console.warn('Could not set the app order', error)
						showError(t('theming', 'Could not set the app order'))
					})
			},
		})

		const saveSetting = async (key: string, value: unknown) => {
			const url = generateOcsUrl('apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
				appId: 'core',
				configKey: key,
			})
			return await axios.post(url, {
				configValue: JSON.stringify(value),
			})
		}

		return {
			appOrder,
			hasAppOrderChanged,

			t,
		}
	},
})
</script>

<style scoped lang="scss">
.user-app-menu-order {
	margin-block: 12px;
}
</style>
