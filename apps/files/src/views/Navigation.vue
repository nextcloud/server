<!--
  - @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
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
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<NcAppNavigation data-cy-files-navigation
		:aria-label="t('files', 'Files')">
		<template #list>
			<NcAppNavigationItem v-for="view in parentViews"
				:key="view.id"
				:allow-collapse="true"
				:data-cy-files-navigation-item="view.id"
				:exact="useExactRouteMatching(view)"
				:icon="view.iconClass"
				:name="view.name"
				:open="isExpanded(view)"
				:pinned="view.sticky"
				:to="generateToNavigation(view)"
				@update:open="onToggleExpand(view)">
				<!-- Sanitized icon as svg if provided -->
				<NcIconSvgWrapper v-if="view.icon" slot="icon" :svg="view.icon" />

				<!-- Child views if any -->
				<NcAppNavigationItem v-for="child in childViews[view.id]"
					:key="child.id"
					:data-cy-files-navigation-item="child.id"
					:exact-path="true"
					:icon="child.iconClass"
					:name="child.name"
					:to="generateToNavigation(child)">
					<!-- Sanitized icon as svg if provided -->
					<NcIconSvgWrapper v-if="child.icon" slot="icon" :svg="child.icon" />
				</NcAppNavigationItem>
			</NcAppNavigationItem>
		</template>

		<!-- Non-scrollable navigation bottom elements -->
		<template #footer>
			<ul class="app-navigation-entry__settings">
				<!-- User storage usage statistics -->
				<NavigationQuota />

				<!-- Files settings modal toggle-->
				<NcAppNavigationItem :aria-label="t('files', 'Open the files app settings')"
					:name="t('files', 'Files settings')"
					data-cy-files-navigation-settings-button
					@click.prevent.stop="openSettings">
					<Cog slot="icon" :size="20" />
				</NcAppNavigationItem>
			</ul>
		</template>

		<!-- Settings modal-->
		<SettingsModal :open="settingsOpened"
			data-cy-files-navigation-settings
			@close="onSettingsClose" />
	</NcAppNavigation>
</template>

<script lang="ts">
import type { View } from '@nextcloud/files'

import { emit } from '@nextcloud/event-bus'
import { translate } from '@nextcloud/l10n'
import Cog from 'vue-material-design-icons/Cog.vue'
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

import { useViewConfigStore } from '../store/viewConfig.ts'
import logger from '../logger.js'
import NavigationQuota from '../components/NavigationQuota.vue'
import SettingsModal from './Settings.vue'

export default {
	name: 'Navigation',

	components: {
		Cog,
		NavigationQuota,
		NcAppNavigation,
		NcAppNavigationItem,
		NcIconSvgWrapper,
		SettingsModal,
	},

	setup() {
		const viewConfigStore = useViewConfigStore()
		return {
			viewConfigStore,
		}
	},

	data() {
		return {
			settingsOpened: false,
		}
	},

	computed: {
		currentViewId() {
			return this.$route?.params?.view || 'files'
		},

		currentView(): View {
			return this.views.find(view => view.id === this.currentViewId)!
		},

		views(): View[] {
			return this.$navigation.views
		},

		parentViews(): View[] {
			return this.views
				// filter child views
				.filter(view => !view.parent)
				// sort views by order
				.sort((a, b) => {
					return a.order - b.order
				})
		},

		childViews(): Record<string, View[]> {
			return this.views
				// filter parent views
				.filter(view => !!view.parent)
				// create a map of parents and their children
				.reduce((list, view) => {
					list[view.parent!] = [...(list[view.parent!] || []), view]
					// Sort children by order
					list[view.parent!].sort((a, b) => {
						return a.order - b.order
					})
					return list
				}, {} as Record<string, View[]>)
		},
	},

	watch: {
		currentView(view, oldView) {
			if (view.id !== oldView?.id) {
				this.$navigation.setActive(view)
				logger.debug(`Navigation changed from ${oldView.id} to ${view.id}`, { from: oldView, to: view })

				this.showView(view)
			}
		},
	},

	beforeMount() {
		if (this.currentView) {
			logger.debug('Navigation mounted. Showing requested view', { view: this.currentView })
			this.showView(this.currentView)
		}
	},

	methods: {
		/**
		 * Only use exact route matching on routes with child views
		 * Because if a view does not have children (like the files view) then multiple routes might be matched for it
		 * Like for the 'files' view this does not work because of optional 'fileid' param so /files and /files/1234 are both in the 'files' view
		 * @param view The view to check
		 */
		useExactRouteMatching(view: View): boolean {
			return this.childViews[view.id]?.length > 0
		},

		showView(view: View) {
			// Closing any opened sidebar
			window?.OCA?.Files?.Sidebar?.close?.()
			this.$navigation.setActive(view)
			emit('files:navigation:changed', view)
		},

		/**
		 * Expand/collapse a a view with children and permanently
		 * save this setting in the server.
		 * @param view View to toggle
		 */
		onToggleExpand(view: View) {
			// Invert state
			const isExpanded = this.isExpanded(view)
			// Update the view expanded state, might not be necessary
			view.expanded = !isExpanded
			this.viewConfigStore.update(view.id, 'expanded', !isExpanded)
		},

		/**
		 * Check if a view is expanded by user config
		 * or fallback to the default value.
		 * @param view View to check if expanded
		 */
		isExpanded(view: View): boolean {
			return typeof this.viewConfigStore.getConfig(view.id)?.expanded === 'boolean'
				? this.viewConfigStore.getConfig(view.id).expanded === true
				: view.expanded === true
		},

		/**
		 * Generate the route to a view
		 * @param view View to generate "to" navigation for
		 */
		generateToNavigation(view: View) {
			if (view.params) {
				const { dir } = view.params
				return { name: 'filelist', params: view.params, query: { dir } }
			}
			return { name: 'filelist', params: { view: view.id } }
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

		t: translate,
	},
}
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
</style>
