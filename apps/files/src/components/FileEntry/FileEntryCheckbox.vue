<!--
  - @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
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
	<td class="files-list__row-checkbox"
		@keyup.esc.exact="resetSelection">
		<NcLoadingIcon v-if="isLoading" />
		<NcCheckboxRadioSwitch v-else
			:aria-label="ariaLabel"
			:checked="isSelected"
			@update:checked="onSelectionChange" />
	</td>
</template>

<script lang="ts">
import { Node, FileType } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { type PropType, defineComponent } from 'vue'

import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import { useKeyboardStore } from '../../store/keyboard.ts'
import { useSelectionStore } from '../../store/selection.ts'
import logger from '../../logger.js'

export default defineComponent({
	name: 'FileEntryCheckbox',

	components: {
		NcCheckboxRadioSwitch,
		NcLoadingIcon,
	},

	props: {
		fileid: {
			type: Number,
			required: true,
		},
		isLoading: {
			type: Boolean,
			default: false,
		},
		nodes: {
			type: Array as PropType<Node[]>,
			required: true,
		},
		source: {
			type: Object as PropType<Node>,
			required: true,
		},
	},

	setup() {
		const selectionStore = useSelectionStore()
		const keyboardStore = useKeyboardStore()
		return {
			keyboardStore,
			selectionStore,
		}
	},

	computed: {
		selectedFiles() {
			return this.selectionStore.selected
		},
		isSelected() {
			return this.selectedFiles.includes(this.fileid)
		},
		index() {
			return this.nodes.findIndex((node: Node) => node.fileid === this.fileid)
		},
		isFile() {
			return this.source.type === FileType.File
		},
		ariaLabel() {
			return this.isFile
				? t('files', 'Toggle selection for file "{displayName}"', { displayName: this.source.basename })
				: t('files', 'Toggle selection for folder "{displayName}"', { displayName: this.source.basename })
		},
	},

	methods: {
		onSelectionChange(selected: boolean) {
			const newSelectedIndex = this.index
			const lastSelectedIndex = this.selectionStore.lastSelectedIndex

			// Get the last selected and select all files in between
			if (this.keyboardStore?.shiftKey && lastSelectedIndex !== null) {
				const isAlreadySelected = this.selectedFiles.includes(this.fileid)

				const start = Math.min(newSelectedIndex, lastSelectedIndex)
				const end = Math.max(lastSelectedIndex, newSelectedIndex)

				const lastSelection = this.selectionStore.lastSelection
				const filesToSelect = this.nodes
					.map(file => file.fileid)
					.slice(start, end + 1)
					.filter(Boolean) as number[]

				// If already selected, update the new selection _without_ the current file
				const selection = [...lastSelection, ...filesToSelect]
					.filter(fileid => !isAlreadySelected || fileid !== this.fileid)

				logger.debug('Shift key pressed, selecting all files in between', { start, end, filesToSelect, isAlreadySelected })
				// Keep previous lastSelectedIndex to be use for further shift selections
				this.selectionStore.set(selection)
				return
			}

			const selection = selected
				? [...this.selectedFiles, this.fileid]
				: this.selectedFiles.filter(fileid => fileid !== this.fileid)

			logger.debug('Updating selection', { selection })
			this.selectionStore.set(selection)
			this.selectionStore.setLastIndex(newSelectedIndex)
		},

		resetSelection() {
			this.selectionStore.reset()
		},

		t,
	},
})
</script>
