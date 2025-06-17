<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiMagnify, mdiSearchWeb } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, onBeforeMount } from 'vue'
import { onBeforeRouteUpdate, useRoute } from 'vue-router/composables'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcAppNavigationSearch from '@nextcloud/vue/components/NcAppNavigationSearch'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { useNavigation } from '../composables/useNavigation.ts'
import { useRouteParameters } from '../composables/useRouteParameters.ts'
import { useFilesStore } from '../store/files.ts'
import { useSearchStore } from '../store/search.ts'
import { VIEW_ID } from '../views/search.ts'

const { currentView } = useNavigation(true)
const { directory } = useRouteParameters()

const filesStore = useFilesStore()
const searchStore = useSearchStore()

const route = useRoute()

/**
 * Restore search from URL if mounted
 */
onBeforeMount(() => {
	if (searchStore.query === '') {
		const query = [route.query.query].flat()[0] ?? ''
		if (query) {
			searchStore.scope = 'globally'
			searchStore.query = query
		}
	}
})

/**
 * When the route is changed from search view to something different
 * we need to clear the search box.
 */
onBeforeRouteUpdate((to, from, next) => {
	if (from.params.view === VIEW_ID && to.params.view !== VIEW_ID) {
		searchStore.query = ''
	}
	next()
})

/**
 * Local search is only possible on real DAV resources within the files root
 */
const canSearchLocally = computed(() => {
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
</script>

<template>
	<NcAppNavigationSearch v-model="searchStore.query" :label="searchLabel">
		<template #actions>
			<NcActions>
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
