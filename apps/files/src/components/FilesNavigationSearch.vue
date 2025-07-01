<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiMagnify, mdiSearchWeb } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcAppNavigationSearch from '@nextcloud/vue/components/NcAppNavigationSearch'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { onBeforeNavigation } from '../composables/useBeforeNavigation.ts'
import { useNavigation } from '../composables/useNavigation.ts'
import { useRouteParameters } from '../composables/useRouteParameters.ts'
import { useFilesStore } from '../store/files.ts'
import { useSearchStore } from '../store/search.ts'
import { VIEW_ID } from '../views/search.ts'

const { currentView } = useNavigation(true)
const { directory } = useRouteParameters()

const filesStore = useFilesStore()
const searchStore = useSearchStore()

/**
 * When the route is changed from search view to something different
 * we need to clear the search box.
 */
onBeforeNavigation((to, from, next) => {
	if (to.params.view !== VIEW_ID && from.params.view === VIEW_ID) {
		// we are leaving the search view so unset the query
		searchStore.query = ''
		searchStore.scope = 'filter'
	} else if (to.params.view === VIEW_ID && from.params.view === VIEW_ID) {
		// fix the query if the user refreshed the view
		if (searchStore.query && !to.query.query) {
			// @ts-expect-error This is a weird issue with vue-router v4 and will be fixed in v5 (vue 3)
			return next({
				...to,
				query: {
					...to.query,
					query: searchStore.query,
				},
			})
		}
	}
	next()
})

/**
 * Are we currently on the search view.
 * Needed to disable the action menu (we cannot change the search mode there)
 */
const isSearchView = computed(() => currentView.value.id === VIEW_ID)

/**
 * Local search is only possible on real DAV resources within the files root
 */
const canSearchLocally = computed(() => {
	if (searchStore.base) {
		return true
	}

	const folder = filesStore.getDirectoryByPath(currentView.value.id, directory.value)
	return folder?.isDavResource && folder?.root?.startsWith('/files/')
})

/**
 * Different searchbox label depending if filtering or searching
 */
const searchLabel = computed(() => {
	if (searchStore.scope === 'globally') {
		return t('files', 'Search globally by filename …')
	} else if (searchStore.scope === 'locally') {
		return t('files', 'Search here by filename …')
	}
	return t('files', 'Filter file names …')
})

/**
 * Update the search value and set the base if needed
 * @param value - The new value
 */
function onUpdateSearch(value: string) {
	if (searchStore.scope === 'locally' && currentView.value.id !== VIEW_ID) {
		searchStore.base = filesStore.getDirectoryByPath(currentView.value.id, directory.value)
	}
	searchStore.query = value
}
</script>

<template>
	<NcAppNavigationSearch :label="searchLabel" :model-value="searchStore.query" @update:modelValue="onUpdateSearch">
		<template #actions>
			<NcActions :aria-label="t('files', 'Search scope options')" :disabled="isSearchView">
				<template #icon>
					<NcIconSvgWrapper :path="searchStore.scope === 'globally' ? mdiSearchWeb : mdiMagnify" />
				</template>
				<NcActionButton close-after-click @click="searchStore.scope = 'filter'">
					<template #icon>
						<NcIconSvgWrapper :path="mdiMagnify" />
					</template>
					{{ t('files', 'Filter in current view') }}
				</NcActionButton>
				<NcActionButton v-if="canSearchLocally" close-after-click @click="searchStore.scope = 'locally'">
					<template #icon>
						<NcIconSvgWrapper :path="mdiMagnify" />
					</template>
					{{ t('files', 'Search from this location') }}
				</NcActionButton>
				<NcActionButton close-after-click @click="searchStore.scope = 'globally'">
					<template #icon>
						<NcIconSvgWrapper :path="mdiSearchWeb" />
					</template>
					{{ t('files', 'Search globally') }}
				</NcActionButton>
			</NcActions>
		</template>
	</NcAppNavigationSearch>
</template>
