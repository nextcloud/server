<template>
	<NcSettingsSection :name="t('theming', 'Navigation bar settings')">
		<p>
			{{ t('theming', 'You can configure the app order used for the navigation bar. The first entry will be the default app, opened after login or when clicking on the logo.') }}
		</p>
		<NcNoteCard v-if="enforcedDefaultApp" :id="elementIdEnforcedDefaultApp" type="info">
			{{ t('theming', 'The default app can not be changed because it was configured by the administrator.') }}
		</NcNoteCard>
		<NcNoteCard v-if="hasAppOrderChanged" :id="elementIdAppOrderChanged" type="info">
			{{ t('theming', 'The app order was changed, to see it in action you have to reload the page.') }}
		</NcNoteCard>

		<AppOrderSelector class="user-app-menu-order"
			:aria-details="ariaDetailsAppOrder"
			:value="appOrder"
			@update:value="updateAppOrder" />

		<NcButton data-test-id="btn-apporder-reset"
			:disabled="!hasCustomAppOrder"
			type="tertiary"
			@click="resetAppOrder">
			<template #icon>
				<IconUndo :size="20" />
			</template>
			{{ t('theming', 'Reset default app order') }}
		</NcButton>
	</NcSettingsSection>
</template>

<script lang="ts">
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { computed, defineComponent, ref } from 'vue'

import axios from '@nextcloud/axios'
import AppOrderSelector, { IApp } from './AppOrderSelector.vue'
import IconUndo from 'vue-material-design-icons/Undo.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
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

/** The app order user setting */
type IAppOrder = Record<string, Record<number, number>>

/** OCS responses */
interface IOCSResponse<T> {
	ocs: {
		meta: unknown
		data: T
	}
}

export default defineComponent({
	name: 'UserAppMenuSection',
	components: {
		AppOrderSelector,
		IconUndo,
		NcButton,
		NcNoteCard,
		NcSettingsSection,
	},
	setup() {
		const {
			/** The app order currently defined by the user */
			userAppOrder,
			/** The enforced default app set by the administrator (if any) */
			enforcedDefaultApp,
		} = loadState<{ userAppOrder: IAppOrder, enforcedDefaultApp: string }>('theming', 'navigationBar')

		/**
		 * Array of all available apps, it is set by a core controller for the app menu, so it is always available
		 */
		 const initialAppOrder = Object.values(loadState<Record<string, INavigationEntry>>('core', 'apps'))
			.filter(({ type }) => type === 'link')
			.map((app) => ({ ...app, label: app.name, default: app.default && app.app === enforcedDefaultApp }))

		/**
		 * Check if a custom app order is used or the default is shown
		 */
		const hasCustomAppOrder = ref(!Array.isArray(userAppOrder) || Object.values(userAppOrder).length > 0)

		/**
		 * Track if the app order has changed, so the user can be informed to reload
		 */
		const hasAppOrderChanged = computed(() => initialAppOrder.some(({ id }, index) => id !== appOrder.value[index].id))

		/** ID of the "app order has changed" NcNodeCard, used for the aria-details of the apporder */
		const elementIdAppOrderChanged = 'theming-apporder-changed-infocard'

		/** ID of the "you can not change the default app" NcNodeCard, used for the aria-details of the apporder */
		const elementIdEnforcedDefaultApp = 'theming-apporder-changed-infocard'

		/**
		 * The aria-details value of the app order selector
		 * contains the space separated list of element ids of NcNoteCards
		 */
		const ariaDetailsAppOrder = computed(() => (hasAppOrderChanged.value ? `${elementIdAppOrderChanged} ` : '') + (enforcedDefaultApp ? elementIdEnforcedDefaultApp : ''))

		/**
		 * The current apporder (sorted by user)
		 */
		const appOrder = ref([...initialAppOrder])

		/**
		 * Update the app order, called when the user sorts entries
		 * @param value The new app order value
		 */
		const updateAppOrder = (value: IApp[]) => {
			const order: IAppOrder = {}
			value.forEach(({ app, key }, index) => {
				order[app] = { ...order[app], [key]: index }
			})

			saveSetting('apporder', order)
				.then(() => {
					appOrder.value = value as never
					hasCustomAppOrder.value = true
				})
				.catch((error) => {
					console.warn('Could not set the app order', error)
					showError(t('theming', 'Could not set the app order'))
				})
		}

		/**
		 * Reset the app order to the default
		 */
		const resetAppOrder = async () => {
			try {
				await saveSetting('apporder', [])
				hasCustomAppOrder.value = false

				// Reset our app order list
				const { data } = await axios.get<IOCSResponse<INavigationEntry[]>>(generateOcsUrl('/core/navigation/apps'), {
					headers: {
						'OCS-APIRequest': 'true',
					},
				})
				appOrder.value = data.ocs.data.map((app) => ({ ...app, label: app.name, default: app.default && app.app === enforcedDefaultApp }))
			} catch (error) {
				console.warn(error)
				showError(t('theming', 'Could not reset the app order'))
			}
		}

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
			updateAppOrder,
			resetAppOrder,

			enforcedDefaultApp,
			hasAppOrderChanged,
			hasCustomAppOrder,

			ariaDetailsAppOrder,
			elementIdAppOrderChanged,
			elementIdEnforcedDefaultApp,

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
