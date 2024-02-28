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
		<span v-if="source.attributes.failed" class="files-list__row--failed" />

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
				@click.native="execDefaultAction" />

			<FileEntryName ref="name"
				:display-name="displayName"
				:extension="extension"
				:files-list-width="filesListWidth"
				:grid-mode="true"
				:nodes="nodes"
				:source="source"
				@click="execDefaultAction" />
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
		return {
			actionsMenuStore,
			draggingStore,
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
