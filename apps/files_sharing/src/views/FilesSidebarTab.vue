<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IFolder, INode, IView } from '@nextcloud/files'
import type { PropType } from 'vue'

import { computed } from 'vue'
import SharingTab from './SharingTab.vue'
import FileInfo from '../services/FileInfo.ts'

// Runtime prop types are required: production builds reduce props declared only by an
// interface type to `null`, which the web component wrapper cannot handle for props left
// unset, like folder and view outside of the files app.
const props = defineProps({
	node: {
		type: Object as PropType<INode>,
		default: undefined,
	},

	// eslint-disable-next-line vue/no-unused-properties -- Required on the web component interface
	active: {
		type: Boolean,
		default: false,
	},
	// eslint-disable-next-line vue/no-unused-properties -- Required on the web component interface
	folder: {
		type: Object as PropType<IFolder>,
		default: undefined,
	},
	// eslint-disable-next-line vue/no-unused-properties -- Required on the web component interface
	view: {
		type: Object as PropType<IView>,
		default: undefined,
	},
})

const fileInfo = computed(() => props.node && FileInfo(props.node))
</script>

<template>
	<SharingTab v-if="fileInfo" :file-info="fileInfo" />
</template>
