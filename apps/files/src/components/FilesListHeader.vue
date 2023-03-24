<!--
  - @copyright Copyright (c) 2019 Gary Kim <gary@garykim.dev>
  -
  - @author Gary Kim <gary@garykim.dev>
  -
  - @license GNU AGPL version 3 or any later version
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
	<tr>
		<th class="files-list__column files-list__row-checkbox">
			<NcCheckboxRadioSwitch v-bind="selectAllBind" @update:checked="onToggleAll" />
		</th>

		<!-- Link to file -->
		<th class="files-list__column files-list__row-name files-list__column--sortable"
			@click.exact.stop="toggleSortBy('basename')">
			<!-- Icon or preview -->
			<span class="files-list__row-icon" />

			<!-- Name -->
			<FilesListHeaderButton :name="t('files', 'Name')" mode="basename" />
		</th>

		<!-- Actions -->
		<th class="files-list__row-actions" />

		<!-- Size -->
		<th v-if="isSizeAvailable"
			:class="{'files-list__column--sortable': isSizeAvailable}"
			class="files-list__column files-list__row-size">
			<FilesListHeaderButton :name="t('files', 'Size')" mode="size" />
		</th>

		<!-- Custom views columns -->
		<th v-for="column in columns"
			:key="column.id"
			:class="classForColumn(column)">
			<FilesListHeaderButton v-if="!!column.sort" :name="column.title" :mode="column.id" />
			<span v-else>
				{{ column.title }}
			</span>
		</th>
	</tr>
</template>

<script lang="ts">
import { mapState } from 'pinia'
import { translate } from '@nextcloud/l10n'
import MenuDown from 'vue-material-design-icons/MenuDown.vue'
import MenuUp from 'vue-material-design-icons/MenuUp.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import Vue from 'vue'

import { useFilesStore } from '../store/files'
import { useSelectionStore } from '../store/selection'
import { useSortingStore } from '../store/sorting'
import logger from '../logger.js'
import Navigation from '../services/Navigation'
import FilesListHeaderButton from './FilesListHeaderButton.vue'

export default Vue.extend({
	name: 'FilesListHeader',

	components: {
		FilesListHeaderButton,
		NcCheckboxRadioSwitch,
	},

	props: {
		isSizeAvailable: {
			type: Boolean,
			default: false,
		},
		nodes: {
			type: Array,
			required: true,
		},
	},

	setup() {
		const filesStore = useFilesStore()
		const selectionStore = useSelectionStore()
		const sortingStore = useSortingStore()
		return {
			filesStore,
			selectionStore,
			sortingStore,
		}
	},

	computed: {
		...mapState(useSortingStore, ['filesSortingConfig']),

		/** @return {Navigation} */
		currentView() {
			return this.$navigation.active
		},

		columns() {
			return this.currentView?.columns || []
		},

		dir() {
			// Remove any trailing slash but leave root slash
			return (this.$route?.query?.dir || '/').replace(/^(.+)\/$/, '$1')
		},

		selectAllBind() {
			const label = this.isNoneSelected || this.isSomeSelected
				? this.t('files', 'Select all')
				: this.t('files', 'Unselect all')
			return {
				'aria-label': label,
				checked: this.isAllSelected,
				indeterminate: this.isSomeSelected,
				title: label,
			}
		},

		isAllSelected() {
			return this.selectedFiles.length === this.nodes.length
		},

		isNoneSelected() {
			return this.selectedFiles.length === 0
		},

		isSomeSelected() {
			return !this.isAllSelected && !this.isNoneSelected
		},

		selectedFiles() {
			return this.selectionStore.selected
		},

		sortingMode() {
			return this.sortingStore.getSortingMode(this.currentView.id)
				|| this.currentView.defaultSortKey
				|| 'basename'
		},
		isAscSorting() {
			return this.sortingStore.isAscSorting(this.currentView.id) === true
		},
	},

	methods: {
		classForColumn(column) {
			return {
				'files-list__column': true,
				'files-list__column--sortable': !!column.sort,
				'files-list__row-column-custom': true,
				[`files-list__row-${this.currentView.id}-${column.id}`]: true,
			}
		},

		sortAriaLabel(column) {
			const direction = this.isAscSorting
				? this.t('files', 'ascending')
				: this.t('files', 'descending')
			return this.t('files', 'Sort list by {column} ({direction})', {
				column,
				direction,
			})
		},

		onToggleAll(selected) {
			if (selected) {
				const selection = this.nodes.map(node => node.attributes.fileid.toString())
				logger.debug('Added all nodes to selection', { selection })
				this.selectionStore.set(selection)
			} else {
				logger.debug('Cleared selection')
				this.selectionStore.reset()
			}
		},

		toggleSortBy(key) {
			// If we're already sorting by this key, flip the direction
			if (this.sortingMode === key) {
				this.sortingStore.toggleSortingDirection(this.currentView.id)
				return
			}
			// else sort ASC by this new key
			this.sortingStore.setSortingBy(key, this.currentView.id)
		},

		toggleSortByCustomColumn(column) {
			if (!column.sort) {
				return
			}
			this.toggleSortBy(column.id)
		},

		t: translate,
	},
})
</script>

<style scoped lang="scss">
@import '../mixins/fileslist-row.scss';
.files-list__column {
	user-select: none;
	// Make sure the cell colors don't apply to column headers
	color: var(--color-text-maxcontrast) !important;

	&--sortable {
		cursor: pointer;
	}
}

</style>
