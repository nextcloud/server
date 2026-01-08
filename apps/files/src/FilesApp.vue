<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<NcContent app-name="files">
		<FilesNavigation v-if="!isPublic" />
		<FilesList :is-public="isPublic" />
		<FilesSidebar v-if="!isPublic" />
	</NcContent>
</template>

<script setup lang="ts">
import { isPublicShare } from '@nextcloud/sharing/public'
import NcContent from '@nextcloud/vue/components/NcContent'
import FilesList from './views/FilesList.vue'
import FilesNavigation from './views/FilesNavigation.vue'
import FilesSidebar from './views/FilesSidebar.vue'
import { useHotKeys } from './composables/useHotKeys.ts'

useHotKeys()

const isPublic = isPublicShare()
</script>
