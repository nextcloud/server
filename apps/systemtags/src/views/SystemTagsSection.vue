<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { TagWithId } from '../types.ts'

import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { onBeforeMount, ref } from 'vue'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import SystemTagForm from '../components/SystemTagForm.vue'
import SystemTagsCreationControl from '../components/SystemTagsCreationControl.vue'
import logger from '../logger.ts'
import { fetchTags } from '../services/api.ts'

const loadingTags = ref(false)
const tags = ref<TagWithId[]>([])

onBeforeMount(async () => {
	loadingTags.value = true
	try {
		tags.value = await fetchTags()
	} catch (error) {
		showError(t('systemtags', 'Failed to load tags'))
		logger.error('Failed to load tags', { error })
	}
	loadingTags.value = false
})

/**
 * Handle tag creation
 *
 * @param tag - The created tag
 */
function handleCreate(tag: TagWithId) {
	tags.value.unshift(tag)
}

/**
 * Handle tag update
 *
 * @param tag - The updated tag
 */
function handleUpdate(tag: TagWithId) {
	const tagIndex = tags.value.findIndex((currTag) => currTag.id === tag.id)
	tags.value.splice(tagIndex, 1)
	tags.value.unshift(tag)
}

/**
 * Handle tag deletion
 *
 * @param tag - The deleted tag
 */
function handleDelete(tag: TagWithId) {
	const tagIndex = tags.value.findIndex((currTag) => currTag.id === tag.id)
	tags.value.splice(tagIndex, 1)
}
</script>

<template>
	<NcSettingsSection
		:name="t('systemtags', 'Collaborative tags')"
		:description="t('systemtags', 'Collaborative tags are available for all users. Restricted tags are visible to users but cannot be assigned by them. Invisible tags are for internal use, since users cannot see or assign them.')">
		<SystemTagsCreationControl />
		<NcLoadingIcon
			v-if="loadingTags"
			:name="t('systemtags', 'Loading collaborative tags …')"
			:size="32" />
		<SystemTagForm
			v-else
			:tags="tags"
			@tag:created="handleCreate"
			@tag:updated="handleUpdate"
			@tag:deleted="handleDelete" />
	</NcSettingsSection>
</template>
