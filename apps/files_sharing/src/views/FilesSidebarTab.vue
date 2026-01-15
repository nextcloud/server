<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IFolder, INode, IView } from '@nextcloud/files'

import { computed } from 'vue'
import SharingTab from './SharingTab.vue'
import FileInfo from '../services/FileInfo.ts'

const props = defineProps<{
	node?: INode
	// eslint-disable-next-line vue/no-unused-properties -- Required on the web component interface
	folder?: IFolder
	// eslint-disable-next-line vue/no-unused-properties -- Required on the web component interface
	view?: IView
}>()

const fileInfo = computed(() => props.node && FileInfo(props.node))
</script>

<template>
	<SharingTab v-if="fileInfo" :file-info="fileInfo" />
</template>
