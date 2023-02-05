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
		<th class="files-list__row-checkbox">
			<NcCheckboxRadioSwitch v-bind="selectAllBind" @update:checked="onToggleAll" />
		</th>

		<!-- Icon or preview -->
		<th class="files-list__row-icon" />

		<!-- Link to file and -->
		<th class="files-list__row-name">
			{{ t('files', 'Name') }}
		</th>

		<!-- Actions -->
		<th class="files-list__row-actions" />
	</tr>
</template>

<script lang="ts">
import { File, Folder } from '@nextcloud/files'
import { translate } from '@nextcloud/l10n'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import Vue from 'vue'

import logger from '../logger'
import { useSelectionStore } from '../store/selection'
import { useFilesStore } from '../store/files'

export default Vue.extend({
	name: 'FilesListHeader',

	components: {
		NcCheckboxRadioSwitch,
	},

	props: {
		nodes: {
			type: [File, Folder],
			required: true,
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
	},

	methods: {
		/**
		 * Get a cached note from the store
		 *
		 * @param {number} fileId the file id to get
		 * @return {Folder|File}
		 */
		getNode(fileId) {
			return this.filesStore.getNode(fileId)
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

		t: translate,
	},
})
</script>

<style scoped lang="scss">
@import '../mixins/fileslist-row.scss'

</style>
