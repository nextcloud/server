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
import { useRouteParameters } from '../composables/useRouteParameters.ts'
import { useFilesStore } from '../store/files.ts'
import { useSearchStore } from '../store/search.ts'

const filesStore = useFilesStore()
const searchStore = useSearchStore()

const { directory } = useRouteParameters()
const canSearchLocally = computed(() => {
	const folder = filesStore.getDirectoryByPath(directory.value)
	return folder?.isDavResource && folder?.root?.includes('/files/')
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
