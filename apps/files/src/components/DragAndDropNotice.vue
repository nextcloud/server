<!--
	- @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
	-
	- @author John Molakvoæ <skjnldsv@protonmail.com>
	- @author Ferdinand Thiessen <opensource@fthiessen.de>
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
	<div v-show="dragover"
		class="files-list__drag-drop-notice"
		@drop="onDrop">
		<div class="files-list__drag-drop-notice-wrapper">
			<TrayArrowDownIcon :size="48" />
			<h3 class="files-list-drag-drop-notice__title">
				{{ t('files', 'Drag and drop files here to upload') }}
			</h3>
		</div>
	</div>
</template>

<script lang="ts">
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import TrayArrowDownIcon from 'vue-material-design-icons/TrayArrowDown.vue'

import logger from '../logger.js'
import { handleDrop } from '../services/DropService'
import { showSuccess } from '@nextcloud/dialogs'

export default defineComponent({
	name: 'DragAndDropNotice',

	components: {
		TrayArrowDownIcon,
	},

	props: {
		currentFolder: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			dragover: false,
		}
	},

	mounted() {
		// Add events on parent to cover both the table and DragAndDrop notice
		const mainContent = window.document.querySelector('main.app-content') as HTMLElement
		mainContent.addEventListener('dragover', this.onDragOver)
		mainContent.addEventListener('dragleave', this.onDragLeave)
	},

	beforeDestroy() {
		const mainContent = window.document.querySelector('main.app-content') as HTMLElement
		mainContent.removeEventListener('dragover', this.onDragOver)
		mainContent.removeEventListener('dragleave', this.onDragLeave)
	},

	methods: {
		onDragOver(event: DragEvent) {
			const isForeignFile = event.dataTransfer?.types.includes('Files')
			if (isForeignFile) {
				// Only handle uploading
				this.dragover = true
			}
		},

		onDragLeave(/* event: DragEvent */) {
			if (this.dragover) {
				this.dragover = false
			}
		},

		onDrop(event: DragEvent) {
			if (this.$el.querySelector('tbody')?.contains(event.target as Node)) {
				return
			}

			event.preventDefault()
			event.stopPropagation()

			if (event.dataTransfer && event.dataTransfer.items.length > 0) {
				// Start upload
				logger.debug(`Uploading files to ${this.currentFolder.path}`)
				// Process finished uploads
				handleDrop(event.dataTransfer).then((uploads) => {
					logger.debug('Upload terminated', { uploads })
					showSuccess(t('files', 'Upload successful'))

					// Scroll to last upload in current directory if terminated
					const lastUpload = uploads.findLast((upload) => !upload.file.webkitRelativePath.includes('/') && upload.response?.headers?.['oc-fileid'])
					if (lastUpload !== undefined) {
						this.$router.push({
							...this.$route,
							params: {
								view: this.$route.params?.view ?? 'files',
								// Remove instanceid from header response
								fileid: parseInt(lastUpload.response!.headers['oc-fileid']),
							},
						})
					}
				})
			}
			this.dragover = false
		},
		t,
	},
})
</script>

<style lang="scss" scoped>
.files-list__drag-drop-notice {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 100%;
	// Breadcrumbs height + row thead height
	min-height: calc(58px + 55px);
	margin: 0;
	user-select: none;
	color: var(--color-text-maxcontrast);
	background-color: var(--color-main-background);
	border-color: black;

	h3 {
		margin-left: 16px;
		color: inherit;
	}

	&-wrapper {
		display: flex;
		align-items: center;
		justify-content: center;
		height: 15vh;
		max-height: 70%;
		padding: 0 5vw;
		border: 2px var(--color-border-dark) dashed;
		border-radius: var(--border-radius-large);
	}
}

</style>
