<script setup lang="ts">
import type { INode } from '@nextcloud/files'

import { mdiMagnify, mdiMagnifyClose } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import debounce from 'debounce'
import PQueue from 'p-queue'
import { ref, shallowRef, watch } from 'vue'
import { searchNodes } from '../services/WebDAVSearch'

import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import FilesListTable from './FilesListTable.vue'

const props = defineProps<{
	query: string
}>()

const loading = ref(false)
const nodes = shallowRef<INode[]>([])
watch(() => props.query, debounce(scheduleSearch, 250))

const queue = new PQueue({ concurrency: 1 })
const signal = new AbortController()

queue.addListener('idle', () => { loading.value = false })
queue.addListener('active', () => { loading.value = true })

/**
 * Schedule the search.
 * Abort current search and trigger a new one.
 */
function scheduleSearch() {
	const query = props.query.trim()
	if (query === '') {
		queue.clear()
		signal.abort()
		nodes.value = []
	} else if (query.length >= 2) {
		// only trigger search if the query is at least 2 characters long
		// otherwise there will be no search results (backend limitation).
		queue.add(async () => {
			const result = await searchNodes(query, { signal: signal.signal })
			nodes.value = result
		})
	}
}
</script>

<template>
	<div>
		<h2 class="files-list-search-results__heading">
			{{ t('files', 'Results from other locations') }}
		</h2>
		<NcEmptyContent v-if="loading"
			:name="t('files', 'Searching in other locations …')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiMagnify" />
			</template>
		</NcEmptyContent>
		<NcEmptyContent v-else-if="nodes.length === 0"
			:name="t('files', 'No other results found.')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiMagnifyClose" />
			</template>
		</NcEmptyContent>
		<FilesListTable v-else
			:nodes="nodes"
			summary=""
			:current-folder="nodes[0]" />
	</div>
</template>

<style scoped>
.files-list-search-results__heading {
	margin-block: 0 1rem;
	font-size: 1.25rem;
	padding-inline-start: calc((var(--row-height) - 18px) / 2);
}
</style>
