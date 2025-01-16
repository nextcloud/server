<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<td class="files-list__row-checkbox"
		@keyup.esc.exact="resetSelection">
		<NcLoadingIcon v-if="isLoading" :name="loadingLabel" />
		<NcCheckboxRadioSwitch v-else
			:aria-label="ariaLabel"
			:checked="isSelected"
			data-cy-files-list-row-checkbox
			@update:checked="onSelectionChange" />
	</td>
</template>

<script lang="ts">
import type { Node } from '@nextcloud/files'
import type { PropType } from 'vue'
import type { FileSource } from '../../types.ts'

import { FileType } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import { useHotKey } from '@nextcloud/vue/dist/Composables/useHotKey.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import { useActiveStore } from '../../store/active.ts'
import { useKeyboardStore } from '../../store/keyboard.ts'
import { useSelectionStore } from '../../store/selection.ts'
import logger from '../../logger.ts'

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
		const activeStore = useActiveStore()

		return {
			activeStore,
			keyboardStore,
			selectionStore,
			t,
		}
	},

	computed: {
		isActive() {
			return this.activeStore.activeNode?.source === this.source.source
		},

		selectedFiles() {
			return this.selectionStore.selected
		},
		isSelected() {
			return this.selectedFiles.includes(this.source.source)
		},
		index() {
			return this.nodes.findIndex((node: Node) => node.source === this.source.source)
		},
		isFile() {
			return this.source.type === FileType.File
		},
		ariaLabel() {
			return this.isFile
				? t('files', 'Toggle selection for file "{displayName}"', { displayName: this.source.basename })
				: t('files', 'Toggle selection for folder "{displayName}"', { displayName: this.source.basename })
		},
		loadingLabel() {
			return this.isFile
				? t('files', 'File is loading')
				: t('files', 'Folder is loading')
		},
	},

	created() {
		// ctrl+space toggle selection
		useHotKey(' ', this.onToggleSelect, {
			stop: true,
			prevent: true,
			ctrl: true,
		})

		// ctrl+shift+space toggle range selection
		useHotKey(' ', this.onToggleSelect, {
			stop: true,
			prevent: true,
			ctrl: true,
			shift: true,
		})
	},

	methods: {
		onSelectionChange(selected: boolean) {
			const newSelectedIndex = this.index
			const lastSelectedIndex = this.selectionStore.lastSelectedIndex

			// Get the last selected and select all files in between
			if (this.keyboardStore?.shiftKey && lastSelectedIndex !== null) {
				const isAlreadySelected = this.selectedFiles.includes(this.source.source)

				const start = Math.min(newSelectedIndex, lastSelectedIndex)
				const end = Math.max(lastSelectedIndex, newSelectedIndex)

				const lastSelection = this.selectionStore.lastSelection
				const filesToSelect = this.nodes
					.map(file => file.source)
					.slice(start, end + 1)
					.filter(Boolean) as FileSource[]

				// If already selected, update the new selection _without_ the current file
				const selection = [...lastSelection, ...filesToSelect]
					.filter(source => !isAlreadySelected || source !== this.source.source)

				logger.debug('Shift key pressed, selecting all files in between', { start, end, filesToSelect, isAlreadySelected })
				// Keep previous lastSelectedIndex to be use for further shift selections
				this.selectionStore.set(selection)
				return
			}

			const selection = selected
				? [...this.selectedFiles, this.source.source]
				: this.selectedFiles.filter(source => source !== this.source.source)

			logger.debug('Updating selection', { selection })
			this.selectionStore.set(selection)
			this.selectionStore.setLastIndex(newSelectedIndex)
		},

		resetSelection() {
			this.selectionStore.reset()
		},

		onToggleSelect() {
			// Don't react if the node is not active
			if (!this.isActive) {
				return
			}

			logger.debug('Toggling selection for file', { source: this.source })
			this.onSelectionChange(!this.isSelected)
		},
	},
})
</script>
