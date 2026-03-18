<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<FileEntryImageGroup
		v-if="isGroup"
		:source="source"
		:isMimeAvailable="isMimeAvailable"
		:isSizeAvailable="isSizeAvailable"
		:isMtimeAvailable="isMtimeAvailable"
		@toggle="onToggleGroup?.($event)" />

	<component
		:is="entryComponent"
		v-else
		:source="source"
		:class="{ 'files-list__row--group-child': isGroupChild }"
		v-bind="$attrs" />
</template>

<script lang="ts">
import type { PropType } from 'vue'
import type { GroupedNode } from '../composables/useImageGrouping.ts'

import { defineComponent } from 'vue'
import FileEntry from './FileEntry.vue'
import FileEntryGrid from './FileEntryGrid.vue'
import FileEntryImageGroup from './FileEntryImageGroup.vue'
import { isImageGroup } from '../composables/useImageGrouping.ts'

export default defineComponent({
	name: 'FileEntryWrapper',

	components: {
		FileEntry,
		FileEntryGrid,
		FileEntryImageGroup,
	},

	inheritAttrs: false,

	props: {
		source: {
			type: Object as PropType<GroupedNode>,
			required: true,
		},

		gridMode: {
			type: Boolean,
			default: false,
		},

		isMimeAvailable: {
			type: Boolean,
			default: false,
		},

		isSizeAvailable: {
			type: Boolean,
			default: false,
		},

		isMtimeAvailable: {
			type: Boolean,
			default: false,
		},

		onToggleGroup: {
			type: Function,
			default: null,
		},
	},

	emits: ['toggle-group'],

	computed: {
		isGroup(): boolean {
			return isImageGroup(this.source)
		},

		isGroupChild(): boolean {
			return '_isGroupChild' in this.source
		},

		entryComponent() {
			return this.gridMode ? FileEntryGrid : FileEntry
		},
	},
})
</script>
