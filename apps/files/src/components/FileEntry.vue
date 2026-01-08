<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<tr
		:class="{
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
				:source="source"
				:dragover="dragover"
				@auxclick.native="execDefaultAction"
				@click.native="execDefaultAction" />

			<FileEntryName
				ref="name"
				:basename="basename"
				:extension="extension"
				:nodes="nodes"
				:source="source"
				@auxclick.native="execDefaultAction"
				@click.native="execDefaultAction" />
		</td>

		<!-- Actions -->
		<FileEntryActions
			v-show="!isRenamingSmallScreen"
			ref="actions"
			:class="`files-list__row-actions-${uniqueId}`"
			:opened.sync="openedMenu"
			:source="source" />

		<!-- Mime -->
		<td
			v-if="isMimeAvailable"
			:title="mime"
			class="files-list__row-mime"
			data-cy-files-list-row-mime
			@click="openDetailsIfAvailable">
			<span>{{ mime }}</span>
		</td>

		<!-- Size -->
		<td
			v-if="!compact && isSizeAvailable"
			:style="sizeOpacity"
			class="files-list__row-size"
			data-cy-files-list-row-size
			@click="openDetailsIfAvailable">
			<span>{{ size }}</span>
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
			<span v-else>{{ t('files', 'Unknown date') }}</span>
		</td>

		<!-- View columns -->
		<td
			v-for="column in columns"
			:key="column.id"
			:class="`files-list__row-${activeView.id}-${column.id}`"
			class="files-list__row-column-custom"
			:data-cy-files-list-row-column-custom="column.id"
			@click="openDetailsIfAvailable">
			<CustomElementRender
				:active-folder="activeFolder"
				:active-view="activeView"
				:render="column.render"
				:source="source" />
		</td>
	</tr>
</template>

<script lang="ts">
import { FileType, formatFileSize } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { useHotKey } from '@nextcloud/vue/composables/useHotKey'
import { defineComponent } from 'vue'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import CustomElementRender from './CustomElementRender.vue'
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
		isMimeAvailable: {
			type: Boolean,
			default: false,
		},

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
			return this.activeView.columns || []
		},

		mime() {
			if (this.source.type === FileType.Folder) {
				return this.t('files', 'Folder')
			}

			if (!this.source.mime || this.source.mime === 'application/octet-stream') {
				return t('files', 'Unknown file type')
			}

			if (window.OC?.MimeTypeList?.names?.[this.source.mime]) {
				return window.OC.MimeTypeList.names[this.source.mime]
			}

			const baseType = this.source.mime.split('/')[0]
			const ext = this.source?.extension?.toUpperCase().replace(/^\./, '') || ''
			if (baseType === 'image') {
				return t('files', '{ext} image', { ext })
			}
			if (baseType === 'video') {
				return t('files', '{ext} video', { ext })
			}
			if (baseType === 'audio') {
				return t('files', '{ext} audio', { ext })
			}
			if (baseType === 'text') {
				return t('files', '{ext} text', { ext })
			}

			return this.source.mime
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

			this.defaultFileAction?.exec({
				nodes: [this.source],
				folder: this.activeFolder!,
				contents: this.nodes,
				view: this.activeView!,
			})
		},
	},
})
</script>
