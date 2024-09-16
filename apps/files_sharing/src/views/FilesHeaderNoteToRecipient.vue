<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcNoteCard v-if="note.length > 0"
		class="note-to-recipient"
		type="info">
		<p v-if="user" class="note-to-recipient__heading">
			{{ t('files_sharing', 'Note from') }}
			<NcUserBubble :user="user.id" :display-name="user.displayName" />
		</p>
		<p v-else class="note-to-recipient__heading">
			{{ t('files_sharing', 'Note:') }}
		</p>
		<p class="note-to-recipient__text" v-text="note" />
	</NcNoteCard>
</template>

<script setup lang="ts">
import type { Folder } from '@nextcloud/files'
import { getCurrentUser } from '@nextcloud/auth'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'

import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcUserBubble from '@nextcloud/vue/dist/Components/NcUserBubble.js'

const folder = ref<Folder>()
const note = computed<string>(() => folder.value?.attributes.note ?? '')
const user = computed(() => {
	const id = folder.value?.owner
	const displayName = folder.value?.attributes?.['owner-display-name']
	if (id !== getCurrentUser()?.uid) {
		return {
			id,
			displayName,
		}
	}
	return null
})

/**
 * Update the current folder
 * @param newFolder the new folder to show note for
 */
function updateFolder(newFolder: Folder) {
	folder.value = newFolder
}

defineExpose({ updateFolder })
</script>

<style scoped>
.note-to-recipient {
	margin-inline: var(--row-height)
}

.note-to-recipient__text {
	/* respect new lines */
	white-space: pre-line;
}

.note-to-recipient__heading {
	font-weight: bold;
}

@media screen and (max-width: 512px) {
	.note-to-recipient {
		margin-inline: var(--default-grid-baseline);
	}
}
</style>
