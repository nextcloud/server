<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<NcContent app-name="files">
		<FilesNavigation v-if="!isPublic" />
		<FilesList :is-public="isPublic" />
	</NcContent>
</template>

<script lang="ts">
import { isPublicShare } from '@nextcloud/sharing/public'
import { defineComponent } from 'vue'
import NcContent from '@nextcloud/vue/components/NcContent'
import FilesList from './views/FilesList.vue'
import FilesNavigation from './views/FilesNavigation.vue'
import { useHotKeys } from './composables/useHotKeys.ts'

export default defineComponent({
	name: 'FilesApp',

	components: {
		NcContent,
		FilesList,
		FilesNavigation,
	},

	setup() {
		// Register global hotkeys
		useHotKeys()

		const isPublic = isPublicShare()

		return {
			isPublic,
		}
	},
})
</script>
