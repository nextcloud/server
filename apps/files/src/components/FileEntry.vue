<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<tr :class="{
			'files-list__row--dragover': dragover,
			'files-list__row--loading': isLoading,
			'files-list__row--active': isActive,
		}"
		data-cy-files-list-row
		:data-cy-files-list-row-fileid="fileid"
		:data-cy-files-list-row-name="source.basename"
		:draggable="canDrag"
		class="files-list__row"
		v-on="rowListeners">
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
				:source="source"
				:dragover="dragover"
				@auxclick.native="execDefaultAction"
				@click.native="execDefaultAction" />

			<FileEntryName ref="name"
				:basename="basename"
				:extension="extension"
				:nodes="nodes"
				:source="source"
				@auxclick.native="execDefaultAction"
				@click.native="execDefaultAction" />
		</td>

		<!-- Actions -->
		<FileEntryActions v-show="!isRenamingSmallScreen"
			ref="actions"
			:class="`files-list__row-actions-${uniqueId}`"
			:opened.sync="openedMenu"
			:source="source" />

		<!-- Size -->
		<td v-if="!compact && isSizeAvailable"
			:style="sizeOpacity"
			class="files-list__row-size"
			data-cy-files-list-row-size
			@click="openDetailsIfAvailable">
			<span>{{ size }}</span>
		</td>

		<!-- Mtime -->
		<td v-if="!compact && isMtimeAvailable"
			:style="mtimeOpacity"
			class="files-list__row-mtime"
			data-cy-files-list-row-mtime
			@click="openDetailsIfAvailable">
			<NcDateTime v-if="mtime" :timestamp="mtime" :ignore-seconds="true" />
			<span v-else>{{ t('files', 'Unknown date') }}</span>
		</td>

		<!-- View columns -->
		<td v-for="column in columns"
			:key="column.id"
			:class="`files-list__row-${currentView.id}-${column.id}`"
			class="files-list__row-column-custom"
			:data-cy-files-list-row-column-custom="column.id"
			@click="openDetailsIfAvailable">
			<CustomElementRender :current-view="currentView"
				:render="column.render"
				:source="source" />
		</td>
	</tr>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { formatFileSize } from '@nextcloud/files'
import { useHotKey } from '@nextcloud/vue/dist/Composables/useHotKey.js'
import moment from '@nextcloud/moment'
import NcDateTime from '@nextcloud/vue/dist/Components/NcDateTime.js'

import { useNavigation } from '../composables/useNavigation.ts'
import { useFileListWidth } from '../composables/useFileListWidth.ts'
import { useRouteParameters } from '../composables/useRouteParameters.ts'
import { useActionsMenuStore } from '../store/actionsmenu.ts'
import { useDragAndDropStore } from '../store/dragging.ts'
import { useFilesStore } from '../store/files.ts'
import { useRenamingStore } from '../store/renaming.ts'
import { useSelectionStore } from '../store/selection.ts'

import CustomElementRender from './CustomElementRender.vue'
import FileEntryActions from './FileEntry/FileEntryActions.vue'
import FileEntryCheckbox from './FileEntry/FileEntryCheckbox.vue'
import FileEntryMixin from './FileEntryMixin.ts'
import FileEntryName from './FileEntry/FileEntryName.vue'
import FileEntryPreview from './FileEntry/FileEntryPreview.vue'

export default defineComponent({
	name: 'FileEntry',

	components: {
		CustomElementRender,
		FileEntryActions,
		FileEntryCheckbox,
		FileEntryName,
		FileEntryPreview,
		NcDateTime,
	},

	mixins: [
		FileEntryMixin,
	],

	props: {
		isSizeAvailable: {
			type: Boolean,
			default: false,
		},
	},

	setup() {
		const actionsMenuStore = useActionsMenuStore()
		const draggingStore = useDragAndDropStore()
		const filesStore = useFilesStore()
		const renamingStore = useRenamingStore()
		const selectionStore = useSelectionStore()
		const filesListWidth = useFileListWidth()
		// The file list is guaranteed to be only shown with active view - thus we can set the `loaded` flag
		const { currentView } = useNavigation(true)
		const {
			directory: currentDir,
			fileId: currentFileId,
		} = useRouteParameters()

		return {
			actionsMenuStore,
			draggingStore,
			filesStore,
			renamingStore,
			selectionStore,

			currentDir,
			currentFileId,
			currentView,
			filesListWidth,
		}
	},

	computed: {
		/**
		 * Conditionally add drag and drop listeners
		 * Do not add drag start and over listeners on renaming to allow to drag and drop text
		 */
		rowListeners() {
			const conditionals = this.isRenaming
				? {}
				: {
					dragstart: this.onDragStart,
					dragover: this.onDragOver,
				}

			return {
				...conditionals,
				contextmenu: this.onRightClick,
				dragleave: this.onDragLeave,
				dragend: this.onDragEnd,
				drop: this.onDrop,
			}
		},
		columns() {
			// Hide columns if the list is too small
			if (this.filesListWidth < 512 || this.compact) {
				return []
			}
			return this.currentView.columns || []
		},

		size() {
			const size = this.source.size
			if (size === undefined || isNaN(size) || size < 0) {
				return this.t('files', 'Pending')
			}
			return formatFileSize(size, true)
		},

		sizeOpacity() {
			const maxOpacitySize = 10 * 1024 * 1024

			const size = this.source.size
			if (size === undefined || isNaN(size) || size < 0) {
				return {}
			}

			const ratio = Math.round(Math.min(100, 100 * Math.pow((size / maxOpacitySize), 2)))
			return {
				color: `color-mix(in srgb, var(--color-main-text) ${ratio}%, var(--color-text-maxcontrast))`,
			}
		},

		mtime() {
			// If the mtime is not a valid date, return it as is
			if (this.source.mtime && !isNaN(this.source.mtime.getDate())) {
				return this.source.mtime
			}

			if (this.source.crtime && !isNaN(this.source.crtime.getDate())) {
				return this.source.crtime
			}

			return null
		},

		mtimeTitle() {
			if (this.source.mtime) {
				return moment(this.source.mtime).format('LLL')
			}
			return ''
		},
	},

	created() {
		useHotKey('Enter', this.triggerDefaultAction, {
			stop: true,
			prevent: true,
		})
	},

	methods: {
		formatFileSize,

		triggerDefaultAction() {
			// Don't react to the event if the file row is not active
			if (!this.isActive) {
				return
			}

			this.defaultFileAction?.exec(this.source, this.currentView, this.currentDir)
		},
	},
})
</script>
