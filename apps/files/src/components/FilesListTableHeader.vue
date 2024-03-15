<!--
  - @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<tr class="files-list__row-head">
		<th class="files-list__column files-list__row-checkbox"
			@keyup.esc.exact="resetSelection">
			<NcCheckboxRadioSwitch v-bind="selectAllBind" @update:checked="onToggleAll" />
		</th>

		<!-- Columns display -->

		<!-- Link to file -->
		<th class="files-list__column files-list__row-name files-list__column--sortable"
			:aria-sort="ariaSortForMode('basename')">
			<!-- Icon or preview -->
			<span class="files-list__row-icon" />

			<!-- Name -->
			<FilesListTableHeaderButton :name="t('files', 'Name')" mode="basename" />
		</th>

		<!-- Actions -->
		<th class="files-list__row-actions" />

		<!-- Size -->
		<th v-if="isSizeAvailable"
			class="files-list__column files-list__row-size"
			:class="{ 'files-list__column--sortable': isSizeAvailable }"
			:aria-sort="ariaSortForMode('size')">
			<FilesListTableHeaderButton :name="t('files', 'Size')" mode="size" />
		</th>

		<!-- Mtime -->
		<th v-if="isMtimeAvailable"
			class="files-list__column files-list__row-mtime"
			:class="{ 'files-list__column--sortable': isMtimeAvailable }"
			:aria-sort="ariaSortForMode('mtime')">
			<FilesListTableHeaderButton :name="t('files', 'Modified')" mode="mtime" />
		</th>

		<!-- Custom views columns -->
		<th v-for="column in columns"
			:key="column.id"
			:class="classForColumn(column)"
			:aria-sort="ariaSortForMode(column.id)">
			<FilesListTableHeaderButton v-if="!!column.sort" :name="column.title" :mode="column.id" />
			<span v-else>
				{{ column.title }}
			</span>
		</th>
	</tr>
</template>

<script lang="ts">
import { translate as t } from '@nextcloud/l10n'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import { defineComponent, type PropType } from 'vue'

import { useFilesStore } from '../store/files.ts'
import { useSelectionStore } from '../store/selection.ts'
import FilesListTableHeaderButton from './FilesListTableHeaderButton.vue'
import filesSortingMixin from '../mixins/filesSorting.ts'
import logger from '../logger.js'
import type { Node } from '@nextcloud/files'

export default defineComponent({
	name: 'FilesListTableHeader',

	components: {
		FilesListTableHeaderButton,
		NcCheckboxRadioSwitch,
	},

	mixins: [
		filesSortingMixin,
	],

	props: {
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
		filesListWidth: {
			type: Number,
			default: 0,
		},
	},

	setup() {
		const filesStore = useFilesStore()
		const selectionStore = useSelectionStore()
		return {
			filesStore,
			selectionStore,
		}
	},

	computed: {
		currentView() {
			return this.$navigation.active
		},

		columns() {
			// Hide columns if the list is too small
			if (this.filesListWidth < 512) {
				return []
			}
			return this.currentView?.columns || []
		},

		dir() {
			// Remove any trailing slash but leave root slash
			return (this.$route?.query?.dir || '/').replace(/^(.+)\/$/, '$1')
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

	methods: {
		ariaSortForMode(mode: string): ARIAMixin['ariaSort'] {
			if (this.sortingMode === mode) {
				return this.isAscSorting ? 'ascending' : 'descending'
			}
			return null
		},

		classForColumn(column) {
			return {
				'files-list__column': true,
				'files-list__column--sortable': !!column.sort,
				'files-list__row-column-custom': true,
				[`files-list__row-${this.currentView?.id}-${column.id}`]: true,
			}
		},

		onToggleAll(selected) {
			if (selected) {
				const selection = this.nodes.map(node => node.fileid).filter(Boolean) as number[]
				logger.debug('Added all nodes to selection', { selection })
				this.selectionStore.setLastIndex(null)
				this.selectionStore.set(selection)
			} else {
				logger.debug('Cleared selection')
				this.selectionStore.reset()
			}
		},

		resetSelection() {
			this.selectionStore.reset()
		},

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

</style>
