<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcBreadcrumbs data-cy-files-content-breadcrumbs
		:aria-label="t('files', 'Current directory path')"
		class="files-list__breadcrumbs"
		:class="{ 'files-list__breadcrumbs--with-progress': wrapUploadProgressBar }">
		<!-- Current path sections -->
		<NcBreadcrumb v-for="(section, index) in sections"
			:key="section.dir"
			v-bind="section"
			dir="auto"
			:to="section.to"
			:force-icon-text="index === 0 && fileListWidth >= 486"
			:title="titleForSection(index, section)"
			:aria-description="ariaForSection(section)"
			@click.native="onClick(section.to)"
			@dragover.native="onDragOver($event, section.dir)"
			@drop="onDrop($event, section.dir)">
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
import type { Node } from '@nextcloud/files'
import type { FileSource } from '../types.ts'

import { basename } from 'path'
import { defineComponent } from 'vue'
import { Permission } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import HomeSvg from '@mdi/svg/svg/home.svg?raw'
import NcBreadcrumb from '@nextcloud/vue/dist/Components/NcBreadcrumb.js'
import NcBreadcrumbs from '@nextcloud/vue/dist/Components/NcBreadcrumbs.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

import { useNavigation } from '../composables/useNavigation.ts'
import { onDropInternalFiles, dataTransferToFileTree, onDropExternalFiles } from '../services/DropService.ts'
import { useFileListWidth } from '../composables/useFileListWidth.ts'
import { showError } from '@nextcloud/dialogs'
import { useDragAndDropStore } from '../store/dragging.ts'
import { useFilesStore } from '../store/files.ts'
import { usePathsStore } from '../store/paths.ts'
import { useSelectionStore } from '../store/selection.ts'
import { useUploaderStore } from '../store/uploader.ts'
import logger from '../logger'

export default defineComponent({
	name: 'BreadCrumbs',

	components: {
		NcBreadcrumbs,
		NcBreadcrumb,
		NcIconSvgWrapper,
	},

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
		const fileListWidth = useFileListWidth()
		const { currentView, views } = useNavigation()

		return {
			draggingStore,
			filesStore,
			pathsStore,
			selectionStore,
			uploaderStore,

			currentView,
			fileListWidth,
			views,
		}
	},

	computed: {
		dirs(): string[] {
			const cumulativePath = (acc: string) => (value: string) => (acc += `${value}/`)
			// Generate a cumulative path for each path segment: ['/', '/foo', '/foo/bar', ...] etc
			const paths: string[] = this.path.split('/').filter(Boolean).map(cumulativePath('/'))
			// Strip away trailing slash
			return ['/', ...paths.map((path: string) => path.replace(/^(.+)\/$/, '$1'))]
		},

		sections() {
			return this.dirs.map((dir: string, index: number) => {
				const source = this.getFileSourceFromPath(dir)
				const node: Node | undefined = source ? this.getNodeFromSource(source) : undefined
				return {
					dir,
					exact: true,
					name: this.getDirDisplayName(dir),
					to: this.getTo(dir, node),
					// disable drop on current directory
					disableDrop: index === this.dirs.length - 1,
				}
			})
		},

		isUploadInProgress(): boolean {
			return this.uploaderStore.queue.length !== 0
		},

		// Hide breadcrumbs if an upload is ongoing
		wrapUploadProgressBar(): boolean {
			// if an upload is ongoing, and on small screens / mobile, then
			// show the progress bar for the upload below breadcrumbs
			return this.isUploadInProgress && this.fileListWidth < 512
		},

		// used to show the views icon for the first breadcrumb
		viewIcon(): string {
			return this.currentView?.icon ?? HomeSvg
		},

		selectedFiles() {
			return this.selectionStore.selected as FileSource[]
		},

		draggingFiles() {
			return this.draggingStore.dragging as FileSource[]
		},
	},

	methods: {
		getNodeFromSource(source: FileSource): Node | undefined {
			return this.filesStore.getNode(source)
		},
		getFileSourceFromPath(path: string): FileSource | null {
			return (this.currentView && this.pathsStore.getPath(this.currentView.id, path)) ?? null
		},
		getDirDisplayName(path: string): string {
			if (path === '/') {
				return this.currentView?.name || t('files', 'Home')
			}

			const source = this.getFileSourceFromPath(path)
			const node = source ? this.getNodeFromSource(source) : undefined
			return node?.displayname || basename(path)
		},

		getTo(dir: string, node?: Node): Record<string, unknown> {
			if (dir === '/') {
				return {
					...this.$route,
					params: { view: this.currentView?.id },
					query: {},
				}
			}
			if (node === undefined) {
				const view = this.views.find(view => view.params?.dir === dir)
				return {
					...this.$route,
					params: { fileid: view?.params?.fileid ?? '' },
					query: { dir },
				}
			}
			return {
				...this.$route,
				params: { fileid: String(node.fileid) },
				query: { dir: node.path },
			}
		},

		onClick(to) {
			if (to?.query?.dir === this.$route.query.dir) {
				this.$emit('reload')
			}
		},

		onDragOver(event: DragEvent, path: string) {
			if (!event.dataTransfer) {
				return
			}

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
			if (!this.draggingFiles && !event.dataTransfer?.items?.length) {
				return
			}

			// Do not stop propagation, so the main content
			// drop event can be triggered too and clear the
			// dragover state on the DragAndDropNotice component.
			event.preventDefault()

			// Caching the selection
			const selection = this.draggingFiles
			const items = [...event.dataTransfer?.items || []] as DataTransferItem[]

			// We need to process the dataTransfer ASAP before the
			// browser clears it. This is why we cache the items too.
			const fileTree = await dataTransferToFileTree(items)

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

			logger.debug('Dropped', { event, folder, selection, fileTree })

			// Check whether we're uploading files
			if (fileTree.contents.length > 0) {
				await onDropExternalFiles(fileTree, folder, contents.contents)
				return
			}

			// Else we're moving/copying files
			const nodes = selection.map(source => this.filesStore.getNode(source)) as Node[]
			await onDropInternalFiles(nodes, folder, contents.contents, isCopy)

			// Reset selection after we dropped the files
			// if the dropped files are within the selection
			if (selection.some(source => this.selectedFiles.includes(source))) {
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
.files-list__breadcrumbs {
	// Take as much space as possible
	flex: 1 1 100% !important;
	width: 100%;
	height: 100%;
	margin-block: 0;
	margin-inline: 10px;
	min-width: 0;

	:deep() {
		a {
			cursor: pointer !important;
		}
	}

	&--with-progress {
		flex-direction: column !important;
		align-items: flex-start !important;
	}
}
</style>
