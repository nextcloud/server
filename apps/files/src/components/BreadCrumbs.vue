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
	<NcBreadcrumbs 
		data-cy-files-content-breadcrumbs
		:aria-label="t('files', 'Current directory path')">
		<!-- Current path sections -->
		<NcBreadcrumb v-for="(section, index) in sections"
			v-show="shouldShowBreadcrumbs"
			:key="section.dir"
			v-bind="section"
			dir="auto"
			:to="section.to"
			:force-icon-text="true"
			:title="titleForSection(index, section)"
			:aria-description="ariaForSection(section)"
			@click.native="onClick(section.to)"
			@dragover.native="onDragOver($event, section.dir)"
			@dropped="onDrop($event, section.dir)">
			<template v-if="index === 0" #icon>
				<NcIconSvgWrapper :size="20"
					:svg="viewIcon" />
			</template>
		</NcBreadcrumb>

		<!-- Forward the actions slot -->
		<template #actions>
			<slot name="actions" />
		</template>
	</NcBreadcrumbs>
</template>

<script lang="ts">
import { Permission, type Node } from '@nextcloud/files'

import { basename } from 'path'
import { defineComponent } from 'vue'
import { translate as t} from '@nextcloud/l10n'
import HomeSvg from '@mdi/svg/svg/home.svg?raw'
import NcBreadcrumb from '@nextcloud/vue/dist/Components/NcBreadcrumb.js'
import NcBreadcrumbs from '@nextcloud/vue/dist/Components/NcBreadcrumbs.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

import { onDropExternalFiles, onDropInternalFiles } from '../services/DropService'
import { showError } from '@nextcloud/dialogs'
import { useDragAndDropStore } from '../store/dragging.ts'
import { useFilesStore } from '../store/files.ts'
import { usePathsStore } from '../store/paths.ts'
import { useSelectionStore } from '../store/selection.ts'
import { useUploaderStore } from '../store/uploader.ts'
import filesListWidthMixin from '../mixins/filesListWidth.ts'
import logger from '../logger'
import { debug } from '../../../../core/src/OC/debug.js'
import { F } from 'lodash/fp'

export default defineComponent({
	name: 'BreadCrumbs',

	components: {
		NcBreadcrumbs,
		NcBreadcrumb,
		NcIconSvgWrapper,
	},

	mixins: [
		filesListWidthMixin,
	],

	props: {
		path: {
			type: String,
			default: '/',
		},
	},

	setup() {
		const draggingStore = useDragAndDropStore()
		const filesStore = useFilesStore()
		const pathsStore = usePathsStore()
		const selectionStore = useSelectionStore()
		const uploaderStore = useUploaderStore()

		return {
			draggingStore,
			filesStore,
			pathsStore,
			selectionStore,
			uploaderStore,
		}
	},

	computed: {
		currentView() {
			return this.$navigation.active
		},

		dirs(): string[] {
			const cumulativePath = (acc: string) => (value: string) => (acc += `${value}/`)
			// Generate a cumulative path for each path segment: ['/', '/foo', '/foo/bar', ...] etc
			const paths: string[] = this.path.split('/').filter(Boolean).map(cumulativePath('/'))
			// Strip away trailing slash
			return ['/', ...paths.map((path: string) => path.replace(/^(.+)\/$/, '$1'))]
		},

		sections() {
			return this.dirs.map((dir: string, index: number) => {
				const fileid = this.getFileIdFromPath(dir)
				const to = { ...this.$route, params: { fileid }, query: { dir } }
				return {
					dir,
					exact: true,
					name: this.getDirDisplayName(dir),
					to,
					// disable drop on current directory
					disableDrop: index === this.dirs.length - 1,
				}
			})
		},

		isUploadInProgress(): boolean {
			return this.uploaderStore.queue.length !== 0
		},

		// Hide breadcrumbs if an upload is ongoing
		shouldShowBreadcrumbs(): boolean {
			return this.filesListWidth > 400 && !this.isUploadInProgress
		},

		// used to show the views icon for the first breadcrumb
		viewIcon(): string {
			return this.currentView?.icon ?? HomeSvg
		},

		selectedFiles() {
			return this.selectionStore.selected
		},

		draggingFiles() {
			return this.draggingStore.dragging
		},
	},

	methods: {
		getNodeFromId(id: number): Node | undefined {
			return this.filesStore.getNode(id)
		},
		getFileIdFromPath(path: string): number | undefined {
			return this.pathsStore.getPath(this.currentView?.id, path)
		},
		getDirDisplayName(path: string): string {
			if (path === '/') {
				return this.$navigation?.active?.name || t('files', 'Home')
			}

			const fileId: number | undefined = this.getFileIdFromPath(path)
			const node: Node | undefined = (fileId) ? this.getNodeFromId(fileId) : undefined
			return node?.attributes?.displayName || basename(path)
		},

		onClick(to) {
			if (to?.query?.dir === this.$route.query.dir) {
				this.$emit('reload')
			}
		},

		onDragOver(event: DragEvent, path: string) {
			// Cannot drop on the current directory
			if (path === this.dirs[this.dirs.length - 1]) {
				event.dataTransfer.dropEffect = 'none'
				return
			}

			// Handle copy/move drag and drop
			if (event.ctrlKey) {
				event.dataTransfer.dropEffect = 'copy'
			} else {
				event.dataTransfer.dropEffect = 'move'
			}
		},

		async onDrop(event: DragEvent, path: string) {
			// skip if native drop like text drag and drop from files names
			if (!this.draggingFiles && !event.dataTransfer?.files?.length) {
				return
			}

			// Caching the selection
			const selection = this.draggingFiles
			const files = event.dataTransfer?.files || new FileList()

			event.preventDefault()
			event.stopPropagation()

			// We might not have the target directory fetched yet
			const contents = await this.currentView?.getContents(path)
			const folder = contents?.folder
			if (!folder) {
				showError(this.t('files', 'Target folder does not exist any more'))
				return
			}

			const canDrop = (folder.permissions & Permission.CREATE) !== 0
			const isCopy = event.ctrlKey

			// If another button is pressed, cancel it. This
			// allows cancelling the drag with the right click.
			if (!canDrop || event.button !== 0) {
				return
			}

			logger.debug('Dropped', { event, folder, selection })

			// Check whether we're uploading files
			if (files.length > 0) {
				await onDropExternalFiles(folder, files)
				return
			}

			// Else we're moving/copying files
			const nodes = selection.map(fileid => this.filesStore.getNode(fileid)) as Node[]
			await onDropInternalFiles(folder, nodes, isCopy)

			// Reset selection after we dropped the files
			// if the dropped files are within the selection
			if (selection.some(fileid => this.selectedFiles.includes(fileid))) {
				logger.debug('Dropped selection, resetting select store...')
				this.selectionStore.reset()
			}
		},

		titleForSection(index, section) {
			if (section?.to?.query?.dir === this.$route.query.dir) {
				return t('files', 'Reload current directory')
			} else if (index === 0) {
				return t('files', 'Go to the "{dir}" directory', section)
			}
			return null
		},

		ariaForSection(section) {
			if (section?.to?.query?.dir === this.$route.query.dir) {
				return t('files', 'Reload current directory')
			}
			return null
		},

		t,
	},
})
</script>

<style lang="scss" scoped>
.breadcrumb {
	// Take as much space as possible
	flex: 1 1 100% !important;
	width: 100%;
	margin-inline: 0px 10px 0px 10px;

	::v-deep a {
		cursor: pointer !important;
	}
}

</style>
