<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<tr :class="{'files-list__row--active': isActive, 'files-list__row--dragover': dragover, 'files-list__row--loading': isLoading}"
		data-cy-files-list-row
		:data-cy-files-list-row-fileid="fileid"
		:data-cy-files-list-row-name="source.basename"
		:draggable="canDrag"
		class="files-list__row"
		@contextmenu="onRightClick"
		@dragover="onDragOver"
		@dragleave="onDragLeave"
		@dragstart="onDragStart"
		@dragend="onDragEnd"
		@drop="onDrop">
		<!-- Failed indicator -->
		<span v-if="isFailedSource" class="files-list__row--failed" />

		<!-- Checkbox -->
		<FileEntryCheckbox :fileid="fileid"
			:is-loading="isLoading"
			:nodes="nodes"
			:source="source" />

		<!-- Link to file -->
		<td class="files-list__row-name" data-cy-files-list-row-name>
			<!-- Icon or preview -->
			<FileEntryPreview ref="preview"
				:dragover="dragover"
				:grid-mode="true"
				:source="source"
				@auxclick.native="execDefaultAction"
				@click.native="execDefaultAction" />

			<FileEntryName ref="name"
				:display-name="displayName"
				:extension="extension"
				:files-list-width="filesListWidth"
				:grid-mode="true"
				:nodes="nodes"
				:source="source"
				@auxclick.native="execDefaultAction"
				@click.native="execDefaultAction" />
		</td>

		<!-- Actions -->
		<FileEntryActions ref="actions"
			:class="`files-list__row-actions-${uniqueId}`"
			:files-list-width="filesListWidth"
			:grid-mode="true"
			:loading.sync="loading"
			:opened.sync="openedMenu"
			:source="source" />
	</tr>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

import { useNavigation } from '../composables/useNavigation'
import { useActionsMenuStore } from '../store/actionsmenu.ts'
import { useDragAndDropStore } from '../store/dragging.ts'
import { useFilesStore } from '../store/files.ts'
import { useRenamingStore } from '../store/renaming.ts'
import { useSelectionStore } from '../store/selection.ts'
import FileEntryMixin from './FileEntryMixin.ts'
import FileEntryActions from './FileEntry/FileEntryActions.vue'
import FileEntryCheckbox from './FileEntry/FileEntryCheckbox.vue'
import FileEntryName from './FileEntry/FileEntryName.vue'
import FileEntryPreview from './FileEntry/FileEntryPreview.vue'

export default defineComponent({
	name: 'FileEntryGrid',

	components: {
		FileEntryActions,
		FileEntryCheckbox,
		FileEntryName,
		FileEntryPreview,
	},

	mixins: [
		FileEntryMixin,
	],

	inheritAttrs: false,

	setup() {
		const actionsMenuStore = useActionsMenuStore()
		const draggingStore = useDragAndDropStore()
		const filesStore = useFilesStore()
		const renamingStore = useRenamingStore()
		const selectionStore = useSelectionStore()
		const { currentView } = useNavigation()

		return {
			actionsMenuStore,
			draggingStore,
			filesStore,
			renamingStore,
			selectionStore,

			currentView,
		}
	},

	data() {
		return {
			gridMode: true,
		}
	},
})
</script>
