<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<NcTextField
			v-if="availableTags.length > 5"
			v-model="searchQuery"
			type="search"
			:label="t('systemtags', 'Search tags')" />
		<NcButton
			v-for="tag of shownTags"
			:key="tag.id"
			alignment="start"
			:pressed="isSelected(tag)"
			variant="tertiary"
			wide
			@update:pressed="toggleTag(tag, $event)">
			<template #icon>
				<NcIconSvgWrapper :path="mdiTagOutline" />
			</template>
			{{ tag.displayName }}
		</NcButton>
		<span v-if="shownTags.length === 0 && !loading">
			{{ t('systemtags', 'No tags available') }}
		</span>
	</div>
</template>

<script setup lang="ts">
import type { TagsFilter } from '../files_filters/TagsFilter.ts'
import type { TagWithId } from '../types.ts'

import { mdiTagOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { fetchTags } from '../services/api.ts'

const props = defineProps<{
	filter: TagsFilter
}>()

const loading = ref(true)
const searchQuery = ref('')
const availableTags = ref<TagWithId[]>([])
const selectedTags = ref<TagWithId[]>([...props.filter.selectedTags])

watch(selectedTags, () => {
	props.filter.setTags(selectedTags.value.length > 0 ? [...selectedTags.value] : undefined)
})

onMounted(async () => {
	try {
		const tags = await fetchTags()
		availableTags.value = tags.filter((tag) => tag.userVisible)
	} finally {
		loading.value = false
	}
	props.filter.addEventListener('reset', resetFilter)
	props.filter.addEventListener('deselect', onDeselect)
})

onUnmounted(() => {
	props.filter.removeEventListener('reset', resetFilter)
	props.filter.removeEventListener('deselect', onDeselect)
})

const shownTags = computed(() => {
	if (!searchQuery.value) {
		return availableTags.value
	}
	const query = searchQuery.value.toLocaleLowerCase()
	return availableTags.value.filter((tag) => tag.displayName.toLocaleLowerCase().includes(query))
})

/**
 * Check if a tag is currently selected
 *
 * @param tag The tag to check
 */
function isSelected(tag: TagWithId): boolean {
	return selectedTags.value.some((t) => t.id === tag.id)
}

/**
 * Toggle a tag from the selected list
 *
 * @param tag The tag to toggle
 * @param selected Whether the tag should be selected
 */
function toggleTag(tag: TagWithId, selected: boolean) {
	selectedTags.value = selectedTags.value.filter((t) => t.id !== tag.id)
	if (selected) {
		selectedTags.value = [...selectedTags.value, tag]
	}
}

/**
 * Reset selected tags (triggered by filter reset event)
 */
function resetFilter() {
	selectedTags.value = []
}

/**
 * Remove a single tag from selected (triggered by chip removal)
 *
 * @param event The deselect custom event carrying the tag ID
 */
function onDeselect(event: CustomEvent<number>) {
	selectedTags.value = selectedTags.value.filter((t) => t.id !== event.detail)
}
</script>
