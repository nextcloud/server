<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Fragment>
		<NcAppNavigationItem v-for="view in currentViews"
			:key="view.id"
			class="files-navigation__item"
			allow-collapse
			:loading="view.loading"
			:data-cy-files-navigation-item="view.id"
			:exact="useExactRouteMatching(view)"
			:icon="view.iconClass"
			:name="view.name"
			:open="isExpanded(view)"
			:pinned="view.sticky"
			:to="generateToNavigation(view)"
			:style="style"
			@update:open="(open) => onOpen(open, view)">
			<template v-if="view.icon" #icon>
				<NcIconSvgWrapper :svg="view.icon" />
			</template>

			<!-- Hack to force the collapse icon to be displayed -->
			<li v-if="view.loadChildViews && !view.loaded" style="display: none" />

			<!-- Recursively nest child views -->
			<FilesNavigationItem v-if="hasChildViews(view)"
				:parent="view"
				:level="level + 1"
				:views="filterView(views, parent.id)" />
		</NcAppNavigationItem>
	</Fragment>
</template>

<script lang="ts">
import type { PropType } from 'vue'
import type { View } from '@nextcloud/files'

import { defineComponent } from 'vue'
import { Fragment } from 'vue-frag'

import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

import { useNavigation } from '../composables/useNavigation.js'
import { useViewConfigStore } from '../store/viewConfig.js'

const maxLevel = 7 // Limit nesting to not exceed max call stack size

export default defineComponent({
	name: 'FilesNavigationItem',

	components: {
		Fragment,
		NcAppNavigationItem,
		NcIconSvgWrapper,
	},

	props: {
		parent: {
			type: Object as PropType<View>,
			default: () => ({}),
		},
		level: {
			type: Number,
			default: 0,
		},
		views: {
			type: Object as PropType<Record<string, View[]>>,
			default: () => ({}),
		},
	},

	setup() {
		const { currentView } = useNavigation()
		const viewConfigStore = useViewConfigStore()
		return {
			currentView,
			viewConfigStore,
		}
	},

	computed: {
		currentViews(): View[] {
			if (this.level >= maxLevel) { // Filter for all remaining decendants beyond the max level
				return (Object.values(this.views).reduce((acc, views) => [...acc, ...views], []) as View[])
					.filter(view => view.params?.dir.startsWith(this.parent.params?.dir))
			}
			return this.views[this.parent.id] ?? [] // Root level views have `undefined` parent ids
		},

		style() {
			if (this.level === 0 || this.level === 1 || this.level > maxLevel) { // Left-align deepest entry with center of app navigation, do not add any more visual indentation after this level
				return null
			}
			return {
				'padding-left': '16px',
			}
		},
	},

	methods: {
		hasChildViews(view: View): boolean {
			if (this.level >= maxLevel) {
				return false
			}
			return this.views[view.id]?.length > 0
		},

		/**
		 * Only use exact route matching on routes with child views
		 * Because if a view does not have children (like the files view) then multiple routes might be matched for it
		 * Like for the 'files' view this does not work because of optional 'fileid' param so /files and /files/1234 are both in the 'files' view
		 * @param view The view to check
		 */
		useExactRouteMatching(view: View): boolean {
			return this.hasChildViews(view)
		},

		/**
		 * Generate the route to a view
		 * @param view View to generate "to" navigation for
		 */
		generateToNavigation(view: View) {
			if (view.params) {
				const { dir } = view.params
				return { name: 'filelist', params: { ...view.params }, query: { dir } }
			}
			return { name: 'filelist', params: { view: view.id } }
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
		 * Expand/collapse a a view with children and permanently
		 * save this setting in the server.
		 * @param open True if open
		 * @param view View
		 */
		async onOpen(open: boolean, view: View) {
			// Invert state
			const isExpanded = this.isExpanded(view)
			// Update the view expanded state, might not be necessary
			view.expanded = !isExpanded
			this.viewConfigStore.update(view.id, 'expanded', !isExpanded)
			if (open && view.loadChildViews) {
				await view.loadChildViews(view)
			}
		},

		/**
		 * Return the view map with the specified view id removed
		 *
		 * @param viewMap Map of views
		 * @param id View id
		 */
		filterView(viewMap: Record<string, View[]>, id: string): Record<string, View[]> {
			return Object.fromEntries(
				Object.entries(viewMap)
					// eslint-disable-next-line @typescript-eslint/no-unused-vars
					.filter(([viewId, _views]) => viewId !== id),
			)
		},
	},
})
</script>
