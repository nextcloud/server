<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcAppNavigation data-cy-files-navigation
		class="files-navigation"
		:aria-label="t('files', 'Files')">
		<template #search>
			<NcAppNavigationSearch v-model="searchQuery" :label="t('files', 'Filter filenames…')" />
		</template>
		<template #default>
			<NcAppNavigationList :aria-label="t('files', 'Views')">
				<FilesNavigationItem :views="viewMap" />
			</NcAppNavigationList>

			<!-- Settings modal-->
			<SettingsModal :open="settingsOpened"
				data-cy-files-navigation-settings
				@close="onSettingsClose" />
		</template>

		<!-- Non-scrollable navigation bottom elements -->
		<template #footer>
			<ul class="app-navigation-entry__settings">
				<!-- User storage usage statistics -->
				<NavigationQuota />

				<!-- Files settings modal toggle-->
				<NcAppNavigationItem :name="t('files', 'Files settings')"
					data-cy-files-navigation-settings-button
					@click.prevent.stop="openSettings">
					<IconCog slot="icon" :size="20" />
				</NcAppNavigationItem>
			</ul>
		</template>
	</NcAppNavigation>
</template>

<script lang="ts">
import type { View } from '@nextcloud/files'

import { emit } from '@nextcloud/event-bus'
import { translate as t, getCanonicalLocale, getLanguage } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import IconCog from 'vue-material-design-icons/Cog.vue'
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcAppNavigationList from '@nextcloud/vue/dist/Components/NcAppNavigationList.js'
import NcAppNavigationSearch from '@nextcloud/vue/dist/Components/NcAppNavigationSearch.js'
import NavigationQuota from '../components/NavigationQuota.vue'
import SettingsModal from './Settings.vue'
import FilesNavigationItem from '../components/FilesNavigationItem.vue'

import { useNavigation } from '../composables/useNavigation'
import { useFilenameFilter } from '../composables/useFilenameFilter'
import { useFiltersStore } from '../store/filters.ts'
import { useViewConfigStore } from '../store/viewConfig.ts'
import logger from '../logger.ts'

const collator = Intl.Collator(
	[getLanguage(), getCanonicalLocale()],
	{
		numeric: true,
		usage: 'sort',
	},
)

export default defineComponent({
	name: 'Navigation',

	components: {
		IconCog,
		FilesNavigationItem,

		NavigationQuota,
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationList,
		NcAppNavigationSearch,
		SettingsModal,
	},

	setup() {
		const filtersStore = useFiltersStore()
		const viewConfigStore = useViewConfigStore()
		const { currentView, views } = useNavigation()
		const { searchQuery } = useFilenameFilter()

		return {
			currentView,
			searchQuery,
			t,
			views,

			filtersStore,
			viewConfigStore,
		}
	},

	data() {
		return {
			settingsOpened: false,
		}
	},

	computed: {
		/**
		 * The current view ID from the route params
		 */
		currentViewId() {
			return this.$route?.params?.view || 'files'
		},

		/**
		 * Map of parent ids to views
		 */
		viewMap(): Record<string, View[]> {
			return this.views
				.reduce((map, view) => {
					map[view.parent!] = [...(map[view.parent!] || []), view]
					// TODO Allow undefined order for natural sort
					map[view.parent!].sort((a, b) => {
						if (typeof a.order === 'number' || typeof b.order === 'number') {
							return (a.order ?? 0) - (b.order ?? 0)
						}
						return collator.compare(a.name, b.name)
					})
					return map
				}, {} as Record<string, View[]>)
		},
	},

	watch: {
		currentViewId(newView, oldView) {
			if (this.currentViewId !== this.currentView?.id) {
				// This is guaranteed to be a view because `currentViewId` falls back to the default 'files' view
				const view = this.views.find(({ id }) => id === this.currentViewId)!
				// The new view as active
				this.showView(view)
				logger.debug(`Navigation changed from ${oldView} to ${newView}`, { to: view })
			}
		},
	},

	beforeMount() {
		// This is guaranteed to be a view because `currentViewId` falls back to the default 'files' view
		const view = this.views.find(({ id }) => id === this.currentViewId)!
		this.showView(view)
		logger.debug('Navigation mounted. Showing requested view', { view })
	},

	methods: {
		/**
		 * Set the view as active on the navigation and handle internal state
		 * @param view View to set active
		 */
		showView(view: View) {
			// Closing any opened sidebar
			window.OCA?.Files?.Sidebar?.close?.()
			this.$navigation.setActive(view)
			emit('files:navigation:changed', view)
		},

		/**
		 * Open the settings modal
		 */
		openSettings() {
			this.settingsOpened = true
		},

		/**
		 * Close the settings modal
		 */
		onSettingsClose() {
			this.settingsOpened = false
		},
	},
})
</script>

<style scoped lang="scss">
// TODO: remove when https://github.com/nextcloud/nextcloud-vue/pull/3539 is in
.app-navigation::v-deep .app-navigation-entry-icon {
	background-repeat: no-repeat;
	background-position: center;
}

.app-navigation::v-deep .app-navigation-entry.active .button-vue.icon-collapse:not(:hover) {
	color: var(--color-primary-element-text);
}

.app-navigation > ul.app-navigation__list {
	// Use flex gap value for more elegant spacing
	padding-bottom: var(--default-grid-baseline, 4px);
}

.app-navigation-entry__settings {
	height: auto !important;
	overflow: hidden !important;
	padding-top: 0 !important;
	// Prevent shrinking or growing
	flex: 0 0 auto;
}

.files-navigation {
	:deep(.app-navigation__content > ul.app-navigation__list) {
		will-change: scroll-position;
	}
}
</style>
