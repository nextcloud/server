<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiMagnify, mdiSearchWeb } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcAppNavigationSearch from '@nextcloud/vue/components/NcAppNavigationSearch'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { onBeforeNavigation } from '../composables/useBeforeNavigation.ts'
import { useActiveStore } from '../store/active.ts'
import { useSearchStore } from '../store/search.ts'
import { VIEW_ID } from '../views/search.ts'

const activeStore = useActiveStore()
const searchStore = useSearchStore()

/**
 * When the route is changed from search view to something different
 * we need to clear the search box.
 */
onBeforeNavigation((to, from, next) => {
	if (to.params.view !== VIEW_ID
		&& (from.params.view === VIEW_ID || from.query.dir !== to.query.dir)) {
		// we are leaving the search view or navigate to another directory -> unset the query
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
const isSearchView = computed(() => activeStore.activeView?.id === VIEW_ID)

/**
 * Different searchbox label depending if filtering or searching
 */
const searchLabel = computed(() => {
	if (searchStore.scope === 'globally') {
		return t('files', 'Search everywhere …')
	}
	return t('files', 'Search here …')
})
</script>

<template>
	<NcAppNavigationSearch v-model="searchStore.query" :label="searchLabel">
		<template #actions>
			<NcActions :aria-label="t('files', 'Search scope options')" :disabled="isSearchView">
				<template #icon>
					<NcIconSvgWrapper :path="searchStore.scope === 'globally' ? mdiSearchWeb : mdiMagnify" />
				</template>
				<NcActionButton close-after-click @click="searchStore.scope = 'filter'">
					<template #icon>
						<NcIconSvgWrapper :path="mdiMagnify" />
					</template>
					{{ t('files', 'Search here') }}
				</NcActionButton>
				<NcActionButton close-after-click @click="searchStore.scope = 'globally'">
					<template #icon>
						<NcIconSvgWrapper :path="mdiSearchWeb" />
					</template>
					{{ t('files', 'Search everywhere') }}
				</NcActionButton>
			</NcActions>
		</template>
	</NcAppNavigationSearch>
</template>
