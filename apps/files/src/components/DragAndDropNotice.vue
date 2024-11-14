<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div v-show="dragover"
		class="files-list__drag-drop-notice"
		data-cy-files-drag-drop-area
		v-files-drop.stop.prevent="{
			enable: canUpload,
			targetFolder: currentFolder,
		}">
		<div class="files-list__drag-drop-notice-wrapper">
			<template v-if="canUpload && !isQuotaExceeded">
				<TrayArrowDownIcon :size="48" />
				<h3 class="files-list-drag-drop-notice__title">
					{{ t('files', 'Drag and drop files here to upload') }}
				</h3>
			</template>

			<!-- Not permitted to drop files here -->
			<template v-else>
				<h3 class="files-list-drag-drop-notice__title">
					{{ cantUploadLabel }}
				</h3>
			</template>
		</div>
	</div>
</template>

<script lang="ts">
import type { Folder } from '@nextcloud/files'
import type { PropType } from 'vue'

import { Permission } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { Upload, UploadStatus } from '@nextcloud/upload'
import { defineComponent } from 'vue'
import debounce from 'debounce'

import TrayArrowDownIcon from 'vue-material-design-icons/TrayArrowDown.vue'

import { useNavigation } from '../composables/useNavigation'
import vFilesDrop from '../directives/vFilesDrop.ts'
import logger from '../logger.ts'

export default defineComponent({
	name: 'DragAndDropNotice',

	components: {
		TrayArrowDownIcon,
	},

	props: {
		currentFolder: {
			type: Object as PropType<Folder>,
			required: true,
		},
	},

	setup() {
		const { currentView } = useNavigation()

		return {
			currentView,
			vFilesDrop,
		}
	},

	data() {
		return {
			dragover: false,
		}
	},

	computed: {
		/**
		 * Check if the current folder has create permissions
		 */
		canUpload() {
			return this.currentFolder && (this.currentFolder.permissions & Permission.CREATE) !== 0
		},

		isQuotaExceeded() {
			return this.currentFolder?.attributes?.['quota-available-bytes'] === 0
		},

		cantUploadLabel() {
			if (this.isQuotaExceeded) {
				return this.t('files', 'Your have used your space quota and cannot upload files anymore')
			} else if (!this.canUpload) {
				return this.t('files', 'You don’t have permission to upload or create files here')
			}
			return null
		},

		/**
		 * Debounced function to reset the drag over state
		 * Required as Firefox has a bug where no dragleave is emitted:
		 * https://bugzilla.mozilla.org/show_bug.cgi?id=656164
		 */
		resetDragOver() {
			return debounce(() => {
				this.dragover = false
			}, 3000)
		},
	},

	mounted() {
		// Add events on parent to cover both the table and DragAndDrop notice
		const mainContent = window.document.getElementById('app-content-vue') as HTMLElement
		mainContent.addEventListener('dragover', this.onDragOver)
		mainContent.addEventListener('dragleave', this.onDragLeave)
		mainContent.addEventListener('drop', this.onContentDrop)
	},

	beforeDestroy() {
		const mainContent = window.document.getElementById('app-content-vue') as HTMLElement
		mainContent.removeEventListener('dragover', this.onDragOver)
		mainContent.removeEventListener('dragleave', this.onDragLeave)
		mainContent.removeEventListener('drop', this.onContentDrop)
	},

	methods: {
		onDragOver(event: DragEvent) {
			// Needed to keep the drag/drop events chain working
			event.preventDefault()

			const isForeignFile = event.dataTransfer?.types.includes('Files')
			if (isForeignFile) {
				// Only handle uploading of outside files (not Nextcloud files)
				this.dragover = true
				this.resetDragOver()
			}
		},

		onDragLeave(event: DragEvent) {
			// Counter bubbling, make sure we're ending the drag
			// only when we're leaving the current element
			// Avoid flickering
			const currentTarget = event.currentTarget as HTMLElement
			if (currentTarget?.contains((event.relatedTarget ?? event.target) as HTMLElement)) {
				return
			}

			if (this.dragover) {
				this.dragover = false
				this.resetDragOver.clear()
			}
		},

		/**
		 * Event handler for dropping on unsupported elements
		 * @param event The drop event
		 */
		onContentDrop(event: DragEvent) {
			logger.debug('Drag and drop cancelled, dropped on empty space', { event })
			event.preventDefault()
			if (this.dragover) {
				this.dragover = false
				this.resetDragOver.clear()
			}
		},

		/**
		 * Callback after drop was handled.
		 * @param uploads Uploaded files
		 */
		onFilesUploaded(uploads: Upload[]): void {
			if (uploads.length === 0) {
				// upload was aborted or no files dropped
				this.dragover = false
				this.resetDragOver.clear()
				return
			}

			// Scroll to last successful upload in current directory if terminated
			const lastUpload = uploads.findLast((upload) => upload.status !== UploadStatus.FAILED
				&& !upload.file.webkitRelativePath.includes('/')
				&& upload.response?.headers?.['oc-fileid']
				// Only use the last ID if it's in the current folder
				&& upload.source.replace(this.currentFolder.source, '').split('/').length === 2)

			if (lastUpload !== undefined) {
				logger.debug('Scrolling to last upload in current folder', { lastUpload })
				const location = {
					path: this.$route.path,
					// Keep params but change file id
					params: {
						...this.$route.params,
						fileid: String(lastUpload.response!.headers['oc-fileid']),
					},
					query: {
						...this.$route.query,
					},
				}
				// Remove open file from query
				delete location.query.openfile
				this.$router.push(location)
			}

			this.dragover = false
			this.resetDragOver.clear()
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
		margin-inline-start: 16px;
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
