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

		<!-- Size -->
		<td v-if="isSizeAvailable"
			class="files-list__column files-list__row-size">
			<span>{{ totalSize }}</span>
		</td>

		<!-- Mtime -->
		<td v-if="isMtimeAvailable"
			class="files-list__column files-list__row-mtime" />

		<!-- Custom views columns -->
		<th v-for="column in columns"
			:key="column.id"
			:class="classForColumn(column)">
			<span>{{ column.summary?.(nodes, currentView) }}</span>
		</th>
	</tr>
</template>

<script lang="ts">
import type { Node } from '@nextcloud/files'
import type { PropType } from 'vue'

import { View, formatFileSize } from '@nextcloud/files'
import { translate } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import { useFilesStore } from '../store/files.ts'
import { usePathsStore } from '../store/paths.ts'
import { useRouteParameters } from '../composables/useRouteParameters.ts'

export default defineComponent({
	name: 'FilesListTableFooter',

	props: {
		currentView: {
			type: View,
			required: true,
		},
		isMtimeAvailable: {
			type: Boolean,
			default: false,
		},
		isSizeAvailable: {
			type: Boolean,
			default: false,
		},
		nodes: {
			type: Array as PropType<Node[]>,
			required: true,
		},
		summary: {
			type: String,
			default: '',
		},
		filesListWidth: {
			type: Number,
			default: 0,
		},
	},

	setup() {
		const pathsStore = usePathsStore()
		const filesStore = useFilesStore()
		const { directory } = useRouteParameters()
		return {
			filesStore,
			pathsStore,
			directory,
		}
	},

	computed: {
		currentFolder() {
			if (!this.currentView?.id) {
				return
			}

			if (this.directory === '/') {
				return this.filesStore.getRoot(this.currentView.id)
			}
			const fileId = this.pathsStore.getPath(this.currentView.id, this.directory)!
			return this.filesStore.getNode(fileId)
		},

		columns() {
			// Hide columns if the list is too small
			if (this.filesListWidth < 512) {
				return []
			}
			return this.currentView?.columns || []
		},

		totalSize() {
			// If we have the size already, let's use it
			if (this.currentFolder?.size) {
				return formatFileSize(this.currentFolder.size, true)
			}

			// Otherwise let's compute it
			return formatFileSize(this.nodes.reduce((total, node) => total + (node.size ?? 0), 0), true)
		},
	},

	methods: {
		classForColumn(column) {
			return {
				'files-list__row-column-custom': true,
				[`files-list__row-${this.currentView.id}-${column.id}`]: true,
			}
		},

		t: translate,
	},
})
</script>

<style scoped lang="scss">
// Scoped row
tr {
	margin-bottom: max(25vh, var(--body-container-margin));
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
