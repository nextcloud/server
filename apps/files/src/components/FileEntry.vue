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
	<tr :class="{'files-list__row--dragover': dragover, 'files-list__row--loading': isLoading}"
		data-cy-files-list-row
		:data-cy-files-list-row-fileid="fileid"
		:data-cy-files-list-row-name="source.basename"
		:draggable="canDrag"
		class="files-list__row"
		v-on="rowListeners">
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
				:source="source"
				:dragover="dragover"
				@click.native="execDefaultAction" />

			<FileEntryName ref="name"
				:display-name="displayName"
				:extension="extension"
				:files-list-width="filesListWidth"
				:nodes="nodes"
				:source="source"
				@click="execDefaultAction" />
		</td>

		<!-- Actions -->
		<FileEntryActions v-show="!isRenamingSmallScreen"
			ref="actions"
			:class="`files-list__row-actions-${uniqueId}`"
			:files-list-width="filesListWidth"
			:loading.sync="loading"
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
			<NcDateTime :timestamp="source.mtime" :ignore-seconds="true" />
		</td>

		<!-- View columns -->
		<td v-for="column in columns"
			:key="column.id"
			:class="`files-list__row-${currentView?.id}-${column.id}`"
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
import moment from '@nextcloud/moment'

import FileEntryMixin from './FileEntryMixin.ts'
import NcDateTime from '@nextcloud/vue/dist/Components/NcDateTime.js'
import CustomElementRender from './CustomElementRender.vue'
import FileEntryActions from './FileEntry/FileEntryActions.vue'
import FileEntryCheckbox from './FileEntry/FileEntryCheckbox.vue'
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
		isMtimeAvailable: {
			type: Boolean,
			default: false,
		},
		isSizeAvailable: {
			type: Boolean,
			default: false,
		},
		compact: {
			type: Boolean,
			default: false,
		},
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
			return this.currentView?.columns || []
		},

		size() {
			const size = parseInt(this.source.size, 10) || 0
			if (typeof size !== 'number' || size < 0) {
				return this.t('files', 'Pending')
			}
			return formatFileSize(size, true)
		},
		sizeOpacity() {
			const maxOpacitySize = 10 * 1024 * 1024

			const size = parseInt(this.source.size, 10) || 0
			if (!size || size < 0) {
				return {}
			}

			const ratio = Math.round(Math.min(100, 100 * Math.pow((this.source.size / maxOpacitySize), 2)))
			return {
				color: `color-mix(in srgb, var(--color-main-text) ${ratio}%, var(--color-text-maxcontrast))`,
			}
		},
		mtimeOpacity() {
			const maxOpacityTime = 31 * 24 * 60 * 60 * 1000 // 31 days

			const mtime = this.source.mtime?.getTime?.()
			if (!mtime) {
				return {}
			}

			// 1 = today, 0 = 31 days ago
			const ratio = Math.round(Math.min(100, 100 * (maxOpacityTime - (Date.now() - mtime)) / maxOpacityTime))
			if (ratio < 0) {
				return {}
			}
			return {
				color: `color-mix(in srgb, var(--color-main-text) ${ratio}%, var(--color-text-maxcontrast))`,
			}
		},
		mtimeTitle() {
			if (this.source.mtime) {
				return moment(this.source.mtime).format('LLL')
			}
			return ''
		},
	},

	methods: {
		formatFileSize,
	},
})
</script>
