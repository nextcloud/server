<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<tr
		:class="{ 'files-list__row--active': isActive, 'files-list__row--dragover': dragover, 'files-list__row--loading': isLoading }"
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
		<FileEntryCheckbox
			:fileid="fileid"
			:is-loading="isLoading"
			:nodes="nodes"
			:source="source" />

		<!-- Link to file -->
		<td class="files-list__row-name" data-cy-files-list-row-name>
			<!-- Icon or preview -->
			<FileEntryPreview
				ref="preview"
				:dragover="dragover"
				:grid-mode="true"
				:source="source"
				@auxclick.native="execDefaultAction"
				@click.native="execDefaultAction" />

			<FileEntryName
				ref="name"
				:basename="basename"
				:extension="extension"
				:grid-mode="true"
				:nodes="nodes"
				:source="source"
				@auxclick.native="execDefaultAction"
				@click.native="execDefaultAction" />
		</td>

		<!-- Mtime -->
		<td
			v-if="!compact && isMtimeAvailable"
			:style="mtimeOpacity"
			class="files-list__row-mtime"
			data-cy-files-list-row-mtime
			@click="openDetailsIfAvailable">
			<NcDateTime
				v-if="mtime"
				ignore-seconds
				:timestamp="mtime" />
		</td>

		<!-- Actions -->
		<FileEntryActions
			ref="actions"
			:opened.sync="openedMenu"
			:class="`files-list__row-actions-${uniqueId}`"
			:grid-mode="true"
			:source="source" />
	</tr>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import FileEntryActions from './FileEntry/FileEntryActions.vue'
import FileEntryCheckbox from './FileEntry/FileEntryCheckbox.vue'
import FileEntryName from './FileEntry/FileEntryName.vue'
import FileEntryPreview from './FileEntry/FileEntryPreview.vue'
import { useFileListWidth } from '../composables/useFileListWidth.ts'
import { useRouteParameters } from '../composables/useRouteParameters.ts'
import { useActionsMenuStore } from '../store/actionsmenu.ts'
import { useActiveStore } from '../store/active.ts'
import { useDragAndDropStore } from '../store/dragging.ts'
import { useFilesStore } from '../store/files.ts'
import { useRenamingStore } from '../store/renaming.ts'
import { useSelectionStore } from '../store/selection.ts'
import FileEntryMixin from './FileEntryMixin.ts'

export default defineComponent({
	name: 'FileEntryGrid',

	components: {
		FileEntryActions,
		FileEntryCheckbox,
		FileEntryName,
		FileEntryPreview,
		NcDateTime,
	},

	mixins: [
		FileEntryMixin,
	],

	inheritAttrs: false,

	// keep in sync with FileEntry.vue
	setup() {
		const actionsMenuStore = useActionsMenuStore()
		const draggingStore = useDragAndDropStore()
		const filesStore = useFilesStore()
		const renamingStore = useRenamingStore()
		const selectionStore = useSelectionStore()
		const filesListWidth = useFileListWidth()
		const {
			fileId: currentRouteFileId,
		} = useRouteParameters()

		const {
			activeFolder,
			activeNode,
			activeView,
		} = useActiveStore()

		return {
			actionsMenuStore,
			activeFolder,
			activeNode,
			activeView,
			currentRouteFileId,
			draggingStore,
			filesListWidth,
			filesStore,
			renamingStore,
			selectionStore,
		}
	},

	data() {
		return {
			gridMode: true,
		}
	},
})
</script>
