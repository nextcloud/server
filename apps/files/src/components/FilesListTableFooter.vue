<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<tr>
		<th class="files-list__row-checkbox">
			<!-- TRANSLATORS Label for a table footer which summarizes the columns of the table -->
			<span class="hidden-visually">{{ t('files', 'Total rows summary') }}</span>
		</th>

		<!-- Link to file -->
		<td class="files-list__row-name">
			<!-- Icon or preview -->
			<span class="files-list__row-icon" />

			<!-- Summary -->
			<span>{{ summary }}</span>
		</td>

		<!-- Actions -->
		<td class="files-list__row-actions" />

		<!-- Mime -->
		<td
			v-if="isMimeAvailable"
			class="files-list__column files-list__row-mime" />

		<!-- Size -->
		<td
			v-if="isSizeAvailable"
			class="files-list__column files-list__row-size">
			<span>{{ totalSize }}</span>
		</td>

		<!-- Mtime -->
		<td
			v-if="isMtimeAvailable"
			class="files-list__column files-list__row-mtime" />

		<!-- Custom views columns -->
		<th
			v-for="column in columns"
			:key="column.id"
			:class="classForColumn(column)">
			<span>{{ column.summary?.(nodes, currentView) }}</span>
		</th>
	</tr>
</template>

<script setup lang="ts">
import type { IColumn, INode, IView } from '@nextcloud/files'

import { formatFileSize } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import { useFileListWidth } from '../composables/useFileListWidth.ts'
import { useActiveStore } from '../store/active.ts'

const props = defineProps<{
	/** The current view */
	currentView: IView

	/** Whether the mime column is available */
	isMimeAvailable: boolean

	/** Whether the mtime column is available */
	isMtimeAvailable: boolean

	/** Whether the size column is available */
	isSizeAvailable: boolean

	/** The nodes to summarize */
	nodes: INode[]

	/** Summary text */
	summary: string
}>()

const activeStore = useActiveStore()
const { isNarrow } = useFileListWidth()

const currentFolder = computed(() => activeStore.activeFolder)

const columns = computed(() => {
	// Hide columns if the list is too small
	if (isNarrow.value) {
		return []
	}
	return props.currentView?.columns || []
})

const totalSize = computed(() => {
	// If we have the size already, let's use it
	if (currentFolder.value?.size) {
		return formatFileSize(currentFolder.value.size, true)
	}

	// Otherwise let's compute it
	return formatFileSize(props.nodes.reduce((total, node) => total + (node.size ?? 0), 0), true)
})

/**
 * Get the CSS classes for a custom column
 *
 * @param column - The column
 */
function classForColumn(column: IColumn) {
	return {
		'files-list__row-column-custom': true,
		[`files-list__row-${props.currentView.id}-${column.id}`]: true,
	}
}
</script>

<style scoped lang="scss">
// Scoped row
tr {
	margin-bottom: var(--body-container-margin);
	border-top: 1px solid var(--color-border);
	// Prevent hover effect on the whole row
	background-color: transparent !important;
	border-bottom: none !important;

	td {
		user-select: none;
		// Make sure the cell colors don't apply to column headers
		color: var(--color-text-maxcontrast) !important;
	}
}
</style>
