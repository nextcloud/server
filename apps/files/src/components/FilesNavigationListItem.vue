<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IView } from '@nextcloud/files'

import { getCanonicalLocale, getLanguage } from '@nextcloud/l10n'
import { computed, onMounted, ref } from 'vue'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { useVisibleViews } from '../composables/useViews.ts'
import { folderTreeId } from '../services/FolderTree.ts'
import { useViewConfigStore } from '../store/viewConfig.ts'

const props = withDefaults(defineProps<{
	view: IView
	level?: number
}>(), {
	level: 0,
})

/**
 * Load child views on mount if the view is expanded by default
 * but has no child views loaded yet.
 */
onMounted(() => {
	if (isExpanded.value && !hasChildViews.value) {
		loadChildViews()
	}
})

const maxLevel = 6 // Limit nesting to not exceed max call stack size
const viewConfigStore = useViewConfigStore()
const viewConfig = computed(() => viewConfigStore.viewConfigs[props.view.id])
const isExpanded = computed(() => viewConfig.value
	? (viewConfig.value.expanded === true)
	: (props.view.expanded === true))

const views = useVisibleViews()
const childViews = computed(() => {
	if (props.level < maxLevel) {
		return views.value.filter((v) => v.parent === props.view.id)
	} else {
		return views.value.filter((v) => isDescendant(v, props.view.id))
	}

	/**
	 * Check if a view is a descendant of another view by recursively traversing up the parent chain.
	 *
	 * @param view - The view to check
	 * @param parent - The parent view id to check against
	 */
	function isDescendant(view: IView, parent: string): boolean {
		if (!view.parent) {
			return false
		} else if (view.parent === parent) {
			return true
		}

		const parentView = views.value.find((v) => v.id === view.parent)
		return !!parentView && isDescendant(parentView, parent)
	}
})
const sortedChildViews = computed(() => childViews.value.slice().sort((a, b) => {
	if (a.order !== undefined && b.order === undefined) {
		return -1
	} else if (a.order === undefined && b.order !== undefined) {
		return 1
	}
	return collator.compare(a.name, b.name)
}))
const hasChildViews = computed(() => childViews.value.length > 0)

const navigationRoute = computed(() => {
	if (props.view.params) {
		const { dir } = props.view.params
		return { name: 'filelist', params: { ...props.view.params }, query: { dir } }
	}
	return { name: 'filelist', params: { view: props.view.id } }
})

const isLoading = ref(false)
const childViewsLoaded = ref(false)

/**
 * Handle expanding/collapsing the navigation item.
 *
 * @param expanded - The expanded state
 */
async function onExpandCollapse(expanded: boolean) {
	if (viewConfig.value) {
		viewConfig.value.expanded = expanded
	} else if (expanded) {
		viewConfigStore.viewConfigs[props.view.id] = { expanded: true }
	}

	// folder tree should only show current directory by default,
	// so we don't want to persist the expanded state in the store for its views
	if (!props.view.id.startsWith(`${folderTreeId}::`)) {
		viewConfigStore.update(props.view.id, 'expanded', expanded)
	}

	if (expanded) {
		await loadChildViews()
	}
}

/**
 * Load child views if a loader function is provided and child views haven't been loaded yet.
 */
async function loadChildViews() {
	if (props.view.loadChildViews && !childViewsLoaded.value) {
		isLoading.value = true
		try {
			await props.view.loadChildViews(props.view)
			childViewsLoaded.value = true
		} finally {
			isLoading.value = false
		}
	}
}
</script>

<script lang="ts">
const collator = Intl.Collator(
	[getLanguage(), getCanonicalLocale()],
	{ numeric: true, usage: 'sort' },
)
</script>

<template>
	<NcAppNavigationItem
		class="files-navigation__item"
		allow-collapse
		:loading="isLoading"
		:data-cy-files-navigation-item="view.id"
		:exact="hasChildViews /* eslint-disable-line @nextcloud/vue/no-deprecated-props */"
		:name="view.name"
		:open="isExpanded"
		:pinned="view.sticky"
		:to="navigationRoute"
		@update:open="onExpandCollapse">
		<template v-if="view.icon" #icon>
			<NcIconSvgWrapper :svg="view.icon" />
		</template>

		<!-- Hack to force the collapse icon to be displayed -->
		<li
			v-if="!hasChildViews && !childViewsLoaded && view.loadChildViews"
			v-show="false"
			role="presentation" />

		<!-- Recursively nest child views -->
		<FilesNavigationListItem
			v-for="childView in sortedChildViews"
			:key="childView.id"
			:level="level + 1"
			:view="childView" />
	</NcAppNavigationItem>
</template>
