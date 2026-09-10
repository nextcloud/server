<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<tr
		class="files-list__row-head"
		:class="{ 'files-list__row-head--selected': !isNoneSelected }">
		<th
			class="files-list__column files-list__row-checkbox"
			@keyup.esc.exact="resetSelection">
			<NcCheckboxRadioSwitch
				v-bind="selectAllBind"
				data-cy-files-list-selection-checkbox
				@update:model-value="onToggleAll" />

			<!-- Batch selection UI lives in the select-all cell so column headers
			     stay valid and Tab order stays in document flow
			     (select-all → actions → rows). -->
			<template v-if="!isNoneSelected">
				<span class="files-list__selected">
					{{ n('files', '{count} selected', '{count} selected', selectedNodes.length, { count: selectedNodes.length }) }}
				</span>
				<FilesListTableHeaderActions
					:current-view="currentView"
					:selected-nodes="selectedNodes" />
			</template>
		</th>

		<!-- Columns display (always present so headers match body cells;
		     visually hidden while a selection is active) -->

		<!-- Link to file -->
		<th
			class="files-list__column files-list__row-name files-list__column--sortable"
			:class="{ 'hidden-visually': !isNoneSelected }"
			:aria-sort="ariaSortForMode('basename')"
			:inert="!isNoneSelected">
			<!-- Icon or preview -->
			<span class="files-list__row-icon" />

			<!-- Name -->
			<FilesListTableHeaderButton :name="t('files', 'Name')" mode="basename" />
		</th>

		<!-- Actions -->
		<th
			class="files-list__row-actions"
			:class="{ 'hidden-visually': !isNoneSelected }"
			:inert="!isNoneSelected">
			<span class="hidden-visually">
				{{ t('files', 'Actions') }}
			</span>
		</th>

		<!-- Mime -->
		<th
			v-if="isMimeAvailable"
			class="files-list__column files-list__row-mime"
			:class="{
				'files-list__column--sortable': isMimeAvailable,
				'hidden-visually': !isNoneSelected,
			}"
			:aria-sort="ariaSortForMode('mime')"
			:inert="!isNoneSelected">
			<FilesListTableHeaderButton :name="t('files', 'File type')" mode="mime" />
		</th>

		<!-- Size -->
		<th
			v-if="isSizeAvailable"
			class="files-list__column files-list__row-size"
			:class="{
				'files-list__column--sortable': isSizeAvailable,
				'hidden-visually': !isNoneSelected,
			}"
			:aria-sort="ariaSortForMode('size')"
			:inert="!isNoneSelected">
			<FilesListTableHeaderButton :name="t('files', 'Size')" mode="size" />
		</th>

		<!-- Mtime -->
		<th
			v-if="isMtimeAvailable"
			class="files-list__column files-list__row-mtime"
			:class="{
				'files-list__column--sortable': isMtimeAvailable,
				'hidden-visually': !isNoneSelected,
			}"
			:aria-sort="ariaSortForMode('mtime')"
			:inert="!isNoneSelected">
			<FilesListTableHeaderButton :name="t('files', 'Modified')" mode="mtime" />
		</th>

		<!-- Custom views columns -->
		<th
			v-for="column in columns"
			:key="column.id"
			:class="classForColumn(column)"
			:aria-sort="ariaSortForMode(column.id)"
			:inert="!isNoneSelected">
			<FilesListTableHeaderButton v-if="!!column.sort" :name="column.title" :mode="column.id" />
			<span v-else>
				{{ column.title }}
			</span>
		</th>
	</tr>
</template>

<script lang="ts">
import type { Node, View } from '@nextcloud/files'
import type { PropType } from 'vue'
import type { FileSource } from '../types.ts'

import { n, t } from '@nextcloud/l10n'
import { useHotKey } from '@nextcloud/vue/composables/useHotKey'
import { defineComponent } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import FilesListTableHeaderActions from './FilesListTableHeaderActions.vue'
import FilesListTableHeaderButton from './FilesListTableHeaderButton.vue'
import { useFileListWidth } from '../composables/useFileListWidth.ts'
import { useRouteParameters } from '../composables/useRouteParameters.ts'
import filesSortingMixin from '../mixins/filesSorting.ts'
import { useActiveStore } from '../store/active.ts'
import { useFilesStore } from '../store/files.ts'
import { useSelectionStore } from '../store/selection.ts'
import { logger } from '../utils/logger.ts'

export default defineComponent({
	name: 'FilesListTableHeader',

	components: {
		FilesListTableHeaderActions,
		FilesListTableHeaderButton,
		NcCheckboxRadioSwitch,
	},

	mixins: [
		filesSortingMixin,
	],

	props: {
		currentView: {
			type: Object as PropType<View>,
			required: true,
		},

		isMimeAvailable: {
			type: Boolean,
			default: false,
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
	},

	setup() {
		const activeStore = useActiveStore()
		const filesStore = useFilesStore()
		const selectionStore = useSelectionStore()
		const { directory } = useRouteParameters()

		const { isNarrow } = useFileListWidth()

		return {
			activeStore,
			filesStore,
			selectionStore,

			directory,
			isNarrow,
		}
	},

	computed: {
		columns() {
			// Hide columns if the list is too small
			if (this.isNarrow) {
				return []
			}
			return this.activeStore.activeView?.columns || []
		},

		dir() {
			// Remove any trailing slash but leave root slash
			return this.directory.replace(/^(.+)\/$/, '$1')
		},

		selectAllBind() {
			const label = t('files', 'Toggle selection for all files and folders')
			return {
				'aria-label': label,
				checked: this.isAllSelected,
				indeterminate: this.isSomeSelected,
				title: label,
			}
		},

		selectedNodes() {
			return this.selectionStore.selected
		},

		isAllSelected() {
			return this.selectedNodes.length === this.nodes.length
		},

		isNoneSelected() {
			return this.selectedNodes.length === 0
		},

		isSomeSelected() {
			return !this.isAllSelected && !this.isNoneSelected
		},
	},

	created() {
		// ctrl+a selects all
		useHotKey('a', this.onToggleAll, {
			ctrl: true,
			stop: true,
			prevent: true,
		})

		// Escape key cancels selection
		useHotKey('Escape', this.resetSelection, {
			stop: true,
			prevent: true,
		})
	},

	methods: {
		ariaSortForMode(mode: string): 'ascending' | 'descending' | undefined {
			if (this.sortingMode === mode) {
				return this.isAscSorting ? 'ascending' : 'descending'
			}
		},

		classForColumn(column) {
			return {
				'files-list__column': true,
				'files-list__column--sortable': !!column.sort,
				'files-list__row-column-custom': true,
				[`files-list__row-${this.activeStore.activeView?.id}-${column.id}`]: true,
				'hidden-visually': !this.isNoneSelected,
			}
		},

		onToggleAll(selected = true) {
			if (selected) {
				const selection = this.nodes.map((node) => node.source).filter(Boolean) as FileSource[]
				logger.debug('Added all nodes to selection', { selection })
				this.selectionStore.setLastIndex(null)
				this.selectionStore.set(selection)
			} else {
				logger.debug('Cleared selection')
				this.selectionStore.reset()
			}
		},

		resetSelection() {
			if (this.isNoneSelected) {
				return
			}
			this.selectionStore.reset()
		},

		n,
		t,
	},
})
</script>

<style scoped lang="scss">
.files-list__column {
	user-select: none;
	// Make sure the cell colors don't apply to column headers
	color: var(--color-text-maxcontrast) !important;

	&--sortable {
		cursor: pointer;
	}
}

.files-list__selected {
	padding-inline-end: 12px;
	white-space: nowrap;
	font-variant-numeric: tabular-nums;
	flex-shrink: 0;
	color: var(--color-main-text);
}
</style>
