<!--
  - @copyright Copyright (c) 2019 Gary Kim <gary@garykim.dev>
  -
  - @author Gary Kim <gary@garykim.dev>
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
	<NcAppNavigation>
		<template #list>
			<NcAppNavigationItem v-for="view in parentViews"
				:key="view.id"
				:allow-collapse="true"
				:to="{name: 'filelist', params: { view: view.id }}"
				:icon="view.iconClass"
				:open="view.expanded"
				:pinned="view.sticky"
				:title="view.name"
				@update:open="onToggleExpand(view)">
				<NcAppNavigationItem v-for="child in childViews[view.id]"
					:key="child.id"
					:to="{name: 'filelist', params: { view: child.id }}"
					:icon="child.iconClass"
					:title="child.name" />
			</NcAppNavigationItem>
		</template>

		<!-- Settings toggle -->
		<template #footer>
			<ul class="app-navigation-entry__settings">
				<NcAppNavigationItem :aria-label="t('files', 'Open the files app settings')"
					:title="t('files', 'Files settings')"
					@click.prevent.stop="openSettings">
					<Cog slot="icon" :size="20" />
				</NcAppNavigationItem>
			</ul>
		</template>

		<!-- Settings modal-->
		<SettingsModal :open="settingsOpened"
			@close="onSettingsClose" />
	</NcAppNavigation>
</template>

<script>
import { emit, subscribe } from '@nextcloud/event-bus'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import Cog from 'vue-material-design-icons/Cog.vue'

import SettingsModal from './Settings.vue'
import Navigation from '../services/Navigation.ts'
import logger from '../logger.js'

export default {
	name: 'Navigation',

	components: {
		Cog,
		NcAppNavigation,
		NcAppNavigationItem,
		SettingsModal,
	},

	props: {
		// eslint-disable-next-line vue/prop-name-casing
		Navigation: {
			type: Navigation,
			required: true,
		},
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
		currentView() {
			return this.views.find(view => view.id === this.currentViewId)
		},

		/** @return {Navigation[]} */
		views() {
			return this.Navigation.views
		},
		parentViews() {
			return this.views
				// filter child views
				.filter(view => !view.parent)
				// sort views by order
				.sort((a, b) => {
					return a.order - b.order
				})
		},
		childViews() {
			return this.views
				// filter parent views
				.filter(view => !!view.parent)
				// create a map of parents and their children
				.reduce((list, view) => {
					list[view.parent] = [...(list[view.parent] || []), view]
					// Sort children by order
					list[view.parent].sort((a, b) => {
						return a.order - b.order
					})
					return list
				}, {})
		},
	},

	watch: {
		currentView(view, oldView) {
			logger.debug('View changed', { id: view.id, view })
			this.showView(view, oldView)
		},
	},

	beforeMount() {
		if (this.currentView) {
			logger.debug('Navigation mounted. Showing requested view', { view: this.currentView })
			this.showView(this.currentView)
		}

		subscribe('files:legacy-navigation:changed', this.onLegacyNavigationChanged)
	},

	methods: {
		/**
		 * @param {Navigation} view the new active view
		 * @param {Navigation} oldView the old active view
		 */
		showView(view, oldView) {
			// Closing any opened sidebar
			OCA.Files?.Sidebar?.close?.()

			if (view.legacy) {
				const newAppContent = document.querySelector('#app-content #app-content-' + this.currentView.id + '.viewcontainer')
				document.querySelectorAll('#app-content .viewcontainer').forEach(el => {
					el.classList.add('hidden')
				})
				newAppContent.classList.remove('hidden')

				// Legacy event
				console.debug('F2V', $(newAppContent))

				// Trigger init if not already done
				window.jQuery(newAppContent).trigger(new window.jQuery.Event('show'))

				// After show, properly send the right data
				this.$nextTick(() => {
					const { dir = '/' } = OC.Util.History.parseUrlQuery()
					const params = { itemId: view.id, dir }
					console.debug('F2V showView events', params, newAppContent);
					window.jQuery(newAppContent).trigger(new window.jQuery.Event('show', params))
					window.jQuery(newAppContent).trigger(new window.jQuery.Event('urlChanged', params))
				})

			}

			this.Navigation.setActive(view)
			emit('files:navigation:changed', view)
		},

		/**
		 * Coming from the legacy files app.
		 * TODO: remove when all views are migrated.
		 *
		 * @param {Navigation} view the new active view
		 */
		onLegacyNavigationChanged({ id } = { id: 'files' }) {
			const view = this.Navigation.views.find(view => view.id === id)
			if (view && view.legacy && view.id !== this.currentView.id) {
				// Force update the current route as the request comes
				// from the legacy files app router
				this.$router.replace({ ...this.$route, params: { view: view.id } })
				this.Navigation.setActive(view)
				this.showView(view)
			}
		},

		/**
		 * Expand/collapse a a view with children and permanently
		 * save this setting in the server.
		 *
		 * @param {Navigation} view the view to toggle
		 */
		onToggleExpand(view) {
			// Invert state
			view.expanded = !view.expanded
			axios.post(generateUrl(`/apps/files/api/v1/toggleShowFolder/${view.id}`), { show: view.expanded })
		},

		/**
		 * Open the settings modal and update the settings API entries
		 */
		openSettings() {
			this.settingsOpened = true
			OCA.Files.Settings.settings.forEach(setting => setting.open())
		},

		/**
		 * Close the settings modal and update the settings API entries
		 */
		onSettingsClose() {
			this.settingsOpened = false
			OCA.Files.Settings.settings.forEach(setting => setting.close())
		},
	},
}
</script>

<style scoped lang="scss">
// TODO: remove when https://github.com/nextcloud/nextcloud-vue/pull/3539 is in
.app-navigation::v-deep .app-navigation-entry-icon {
	background-repeat: no-repeat;
	background-position: center;
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
