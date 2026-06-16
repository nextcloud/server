<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { INode } from '@nextcloud/files'
import type { Tag, TagWithId } from '../types.ts'

import { showError } from '@nextcloud/dialogs'
import { emit, subscribe } from '@nextcloud/event-bus'
import { getSidebar } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { onBeforeMount, onMounted, ref, watch } from 'vue'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcSelectTags from '@nextcloud/vue/components/NcSelectTags'
import logger from '../logger.ts'
import { fetchLastUsedTagIds, fetchTags } from '../services/api.ts'
import { fetchNode } from '../services/davClient.ts'
import {
	createTagForFile,
	deleteTagForFile,
	fetchTagsForFile,
	setTagForFile,
} from '../services/files.ts'
import { defaultBaseTag } from '../utils.ts'

const props = defineProps<{
	fileId: number
	disabled?: boolean
}>()

const sortedTags = ref<TagWithId[]>([])
const selectedTags = ref<TagWithId[]>([])
const loadingTags = ref(false)
const loading = ref(false)

watch(() => props.fileId, async () => {
	loadingTags.value = true
	try {
		selectedTags.value = await fetchTagsForFile(props.fileId)
	} catch (error) {
		showError(t('systemtags', 'Failed to load selected tags'))
		logger.error('Failed to load selected tags', { error })
	} finally {
		loadingTags.value = false
	}
}, { immediate: true })

onBeforeMount(async () => {
	try {
		const tags = await fetchTags()
		const lastUsedOrder = await fetchLastUsedTagIds()

		const lastUsedTags: TagWithId[] = []
		const remainingTags: TagWithId[] = []

		for (const tag of tags) {
			if (lastUsedOrder.includes(tag.id)) {
				lastUsedTags.push(tag)
				continue
			}
			remainingTags.push(tag)
		}

		const sortByLastUsed = (a: TagWithId, b: TagWithId) => {
			return lastUsedOrder.indexOf(a.id) - lastUsedOrder.indexOf(b.id)
		}
		lastUsedTags.sort(sortByLastUsed)

		sortedTags.value = [...lastUsedTags, ...remainingTags]
	} catch (error) {
		showError(t('systemtags', 'Failed to load tags'))
		logger.error('Failed to load tags', { error })
	}
})

onMounted(() => {
	subscribe('systemtags:node:updated', onTagUpdated)
})

/**
 * Create a new tag
 *
 * @param newDisplayName - The display name of the tag to create
 */
function createOption(newDisplayName: string): Tag {
	for (const tag of sortedTags.value) {
		const { displayName, ...baseTag } = tag
		if (
			displayName === newDisplayName
			&& Object.entries(baseTag)
				.every(([key, value]) => defaultBaseTag[key] === value)
		) {
			// Return existing tag to prevent vue-select from thinking the tags are different and showing duplicate options
			return tag
		}
	}
	return {
		...defaultBaseTag,
		displayName: newDisplayName,
	}
}

/**
 * Filter out tags with no id to prevent duplicate selected options
 *
 * Created tags are added programmatically by `handleCreate()` with
 * their respective ids returned from the server.
 *
 * @param currentTags - The selected tags
 */
function handleInput(currentTags: Tag[]) {
	selectedTags.value = currentTags.filter((selectedTag) => Boolean(selectedTag.id)) as TagWithId[]
}

/**
 * Handle tag selection
 *
 * @param tags - The selected tags
 */
async function handleSelect(tags: Tag[]) {
	const lastTag = tags[tags.length - 1]!
	if (!lastTag.id) {
		// Ignore created tags handled by `handleCreate()`
		return
	}
	const selectedTag = lastTag as TagWithId
	loading.value = true
	try {
		await setTagForFile(selectedTag, props.fileId)
		const sortToFront = (a: TagWithId, b: TagWithId) => {
			if (a.id === selectedTag.id) {
				return -1
			} else if (b.id === selectedTag.id) {
				return 1
			}
			return 0
		}
		sortedTags.value.sort(sortToFront)
	} catch (error) {
		showError(t('systemtags', 'Failed to select tag'))
		logger.error('Failed to select tag', { error })
	}
	loading.value = false

	updateAndDispatchNodeTagsEvent(props.fileId)
}

/**
 * Handle tag creation
 *
 * @param tag - The created tag
 */
async function handleCreate(tag: Tag) {
	loading.value = true
	try {
		const id = await createTagForFile(tag, props.fileId)
		const createdTag = { ...tag, id }
		sortedTags.value.unshift(createdTag)
		selectedTags.value.push(createdTag)
	} catch (error) {
		const systemTagsCreationRestrictedToAdmin = loadState<true | false>('settings', 'restrictSystemTagsCreationToAdmin', false) === true
		logger.error('Failed to create tag', { error })
		if (systemTagsCreationRestrictedToAdmin) {
			showError(t('systemtags', 'System admin disabled tag creation. You can only use existing ones.'))
			return
		}
		showError(t('systemtags', 'Failed to create tag'))
	}
	loading.value = false

	updateAndDispatchNodeTagsEvent(props.fileId)
}

/**
 * Handle tag deselection
 *
 * @param tag - The deselected tag
 */
async function handleDeselect(tag: TagWithId) {
	loading.value = true
	try {
		await deleteTagForFile(tag, props.fileId)
	} catch (error) {
		showError(t('systemtags', 'Failed to delete tag'))
		logger.error('Failed to delete tag', { error })
	}
	loading.value = false

	updateAndDispatchNodeTagsEvent(props.fileId)
}

/**
 * Handle node updated event
 *
 * @param node - The updated node
 */
async function onTagUpdated(node: INode) {
	if (node.fileid !== props.fileId) {
		return
	}

	loadingTags.value = true
	try {
		selectedTags.value = await fetchTagsForFile(props.fileId)
	} catch (error) {
		showError(t('systemtags', 'Failed to load selected tags'))
		logger.error('Failed to load selected tags', { error })
	}

	loadingTags.value = false
}

/**
 * Update and dispatch system tags node updated event
 *
 * @param fileId - The file ID
 */
async function updateAndDispatchNodeTagsEvent(fileId: number) {
	const sidebar = getSidebar()
	const path = sidebar.node?.path ?? ''
	try {
		const node = await fetchNode(path)
		if (node) {
			emit('systemtags:node:updated', node)
		}
	} catch (error) {
		logger.error('Failed to fetch node for system tags update', { error, fileId })
	}
}
</script>

<template>
	<div class="system-tags">
		<NcLoadingIcon
			v-if="loadingTags"
			:name="t('systemtags', 'Loading collaborative tags …')"
			:size="32" />

		<NcSelectTags
			v-show="!loadingTags"
			class="system-tags__select"
			:inputLabel="t('systemtags', 'Search or create collaborative tags')"
			:placeholder="t('systemtags', 'Collaborative tags …')"
			:options="sortedTags"
			:modelValue="selectedTags"
			:createOption="createOption"
			:disabled="disabled"
			:taggable="true"
			:passthru="true"
			:fetchTags="false"
			:loading="loading"
			@input="handleInput"
			@option:selected="handleSelect"
			@option:created="handleCreate"
			@option:deselected="handleDeselect">
			<template #no-options>
				{{ t('systemtags', 'No tags to select, type to create a new tag') }}
			</template>
		</NcSelectTags>
	</div>
</template>

<style lang="scss" scoped>
.system-tags {
	display: flex;
	flex-direction: column;

	// Fix issue with AppSidebar styles overwriting NcSelect styles
	&__select {
		width: 100%;
		:deep {
			.vs__deselect {
				padding: 0;
			}
		}
	}
}
</style>
