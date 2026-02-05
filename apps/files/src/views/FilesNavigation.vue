<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcAppNavigation
		data-cy-files-navigation
		class="files-navigation"
		:aria-label="t('files', 'Files')">
		<template #search>
			<FilesNavigationSearch />
		</template>
		<template #default>
			<NcAppNavigationList
				class="files-navigation__list"
				:aria-label="t('files', 'Views')">
				<FilesNavigationItem :views="viewMap" />
			</NcAppNavigationList>

			<!-- Settings modal-->
			<FilesAppSettings
				:open.sync="settingsOpened"
				data-cy-files-navigation-settings
				@close="onSettingsClose" />
		</template>

		<!-- Non-scrollable navigation bottom elements -->
		<template #footer>
			<ul class="app-navigation-entry__settings">
				<!-- User storage usage statistics -->
				<NavigationQuota />

				<!-- Files settings modal toggle-->
				<NcAppNavigationItem
					:name="t('files', 'Files settings')"
					data-cy-files-navigation-settings-button
					@click.prevent.stop="openSettings">
					<IconCog slot="icon" :size="20" />
				</NcAppNavigationItem>
			</ul>
		</template>
	</NcAppNavigation>
</template>

<script lang="ts">
import type { IView, View } from '@nextcloud/files'

import { emit } from '@nextcloud/event-bus'
import { getNavigation } from '@nextcloud/files'
import { getCanonicalLocale, getLanguage, t } from '@nextcloud/l10n'
import { watchDebounced } from '@vueuse/core'
import { defineComponent } from 'vue'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import IconCog from 'vue-material-design-icons/CogOutline.vue'
import FilesNavigationItem from '../components/FilesNavigationItem.vue'
import FilesNavigationSearch from '../components/FilesNavigationSearch.vue'
import NavigationQuota from '../components/NavigationQuota.vue'
import FilesAppSettings from './FilesAppSettings.vue'
import { useViews } from '../composables/useViews.ts'
import logger from '../logger.ts'
import { useActiveStore } from '../store/active.ts'
import { useFiltersStore } from '../store/filters.ts'
import { useSidebarStore } from '../store/sidebar.ts'
import { useViewConfigStore } from '../store/viewConfig.ts'

const collator = Intl.Collator(
	[getLanguage(), getCanonicalLocale()],
	{
		numeric: true,
		usage: 'sort',
	},
)

export default defineComponent({
	name: 'FilesNavigation',

	components: {
		IconCog,
		FilesAppSettings,
		FilesNavigationItem,
		FilesNavigationSearch,

		NavigationQuota,
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationList,
	},

	setup() {
		const sidebar = useSidebarStore()
		const activeStore = useActiveStore()
		const filtersStore = useFiltersStore()
		const viewConfigStore = useViewConfigStore()

		const views = useViews()
		watchDebounced(views, () => {
			const expandedViews = Object.entries(viewConfigStore.viewConfigs)
				.filter(([, config]) => config.expanded)
				.map(([id]) => id)
			const expandedViewsWithChildView = views.value
				.filter((view) => 'loadChildViews' in view && view.loadChildViews)
				.filter((view) => expandedViews.includes(view.id)) as (View & Pick<Required<IView>, 'loadChildViews'>)[]
			for (const view of expandedViewsWithChildView) {
				view.loadChildViews(view)
			}
		}, { debounce: 100 })

		return {
			t,

			sidebar,
			activeStore,
			filtersStore,
			viewConfigStore,

			views,
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
		viewMap(): Record<string, IView[]> {
			return this.views
				.reduce((map, view) => {
					map[view.parent!] = [...(map[view.parent!] || []), view]
					map[view.parent!]!.sort((a, b) => {
						if (typeof a.order === 'number' || typeof b.order === 'number') {
							return (a.order ?? 0) - (b.order ?? 0)
						}
						return collator.compare(a.name, b.name)
					})
					return map
				}, {} as Record<string, IView[]>)
		},
	},

	watch: {
		currentViewId(newView, oldView) {
			if (this.currentViewId !== this.activeStore.activeView?.id) {
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
		 *
		 * @param view View to set active
		 */
		showView(view: IView) {
			this.sidebar.close()
			getNavigation().setActive(view.id)
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
.app-navigation {
	:deep(.app-navigation-entry.active .button-vue.icon-collapse:not(:hover)) {
		color: var(--color-primary-element-text);
	}

	> ul.app-navigation__list {
		// Use flex gap value for more elegant spacing
		padding-bottom: var(--default-grid-baseline, 4px);
	}
}

.app-navigation-entry__settings {
	height: auto !important;
	overflow: hidden !important;
	padding-top: 0 !important;
	// Prevent shrinking or growing
	flex: 0 0 auto;
}

.files-navigation {
	&__list {
		height: 100%; // Fill all available space for sticky views
	}

	:deep(.app-navigation__content > ul.app-navigation__list) {
		will-change: scroll-position;
	}
}
</style>
