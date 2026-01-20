<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IFolder, INode, IView } from '@nextcloud/files'

import { computed } from 'vue'
import Comments from './Comments.vue'

const props = defineProps<{
	node?: INode

	// eslint-disable-next-line vue/no-unused-properties -- Required on the web component interface
	active?: boolean
	// eslint-disable-next-line vue/no-unused-properties -- Required on the web component interface
	folder?: IFolder
	// eslint-disable-next-line vue/no-unused-properties -- Required on the web component interface
	view?: IView
}>()

const resourceId = computed(() => props.node?.fileid)
</script>

<template>
	<Comments
		v-if="resourceId !== undefined"
		:key="resourceId"
		:resource-id="resourceId"
		resource-type="files" />
</template>
