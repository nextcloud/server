<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<VirtualList ref="virtualList"
		:caption="caption"
		:column-count="columnCount"
		:data-component="userConfig.grid_view ? FileEntryGrid : FileEntry"
		data-key="source"
		:data-sources="nodes"
		:extra-props="{
			isMtimeAvailable,
			isSizeAvailable,
			nodes,
		}"
		:item-height="userConfig.grid_view ? 198 : 55"
		:scroll-to-index="scrollToIndex">
		<!-- Thead-->
		<template #header>
			<!-- Table header and sort buttons -->
			<FilesListTableHeader ref="thead"
				:files-list-width="fileListWidth"
				:is-mtime-available="isMtimeAvailable"
				:is-size-available="isSizeAvailable"
				:nodes="nodes" />
		</template>

		<!-- Tfoot-->
		<template #footer>
			<FilesListTableFooter :current-view="currentView"
				:files-list-width="fileListWidth"
				:is-mtime-available="isMtimeAvailable"
				:is-size-available="isSizeAvailable"
				:nodes="nodes"
				:summary="summary" />
		</template>
	</VirtualList>
</template>

<script lang="ts">
import type { UserConfig } from '../types.ts'
import type { Node } from '@nextcloud/files'
import type { ComponentPublicInstance, PropType } from 'vue'

import { Folder, Permission } from '@nextcloud/files'
import { showError } from '@nextcloud/dialogs'
import { n, t } from '@nextcloud/l10n'
import { useHotKey } from '@nextcloud/vue/composables/useHotKey'
import { defineComponent } from 'vue'

import { useActiveStore } from '../store/active.ts'
import { useFileListHeaders } from '../composables/useFileListHeaders.ts'
import { useFileListWidth } from '../composables/useFileListWidth.ts'
import { useNavigation } from '../composables/useNavigation.ts'
import { useRouteParameters } from '../composables/useRouteParameters.ts'
import { useSelectionStore } from '../store/selection.js'
import { useUserConfigStore } from '../store/userconfig.ts'

import FileEntry from './FileEntry.vue'
import FileEntryGrid from './FileEntryGrid.vue'
import FilesListTableFooter from './FilesListTableFooter.vue'
import FilesListTableHeader from './FilesListTableHeader.vue'
import VirtualList from './VirtualList.vue'
import logger from '../logger.ts'

export default defineComponent({
	name: 'FilesListTable',

	components: {
		FilesListTableFooter,
		FilesListTableHeader,
		VirtualList,
	},

	props: {
		currentFolder: {
			type: Folder,
			required: true,
		},
		nodes: {
			type: Array as PropType<Node[]>,
			required: true,
		},
		summary: {
			type: String,
			required: true,
		},
	},

	setup() {
		const activeStore = useActiveStore()
		const selectionStore = useSelectionStore()
		const userConfigStore = useUserConfigStore()

		const fileListWidth = useFileListWidth()
		const { currentView } = useNavigation(true)
		const { fileId, openDetails, openFile } = useRouteParameters()

		return {
			currentView,
			fileId,
			fileListWidth,
			headers: useFileListHeaders(),
			openDetails,
			openFile,

			activeStore,
			selectionStore,
			userConfigStore,

			n,
			t,
		}
	},

	data() {
		return {
			FileEntry,
			FileEntryGrid,
			scrollToIndex: 0,
			openFileId: null as number|null,
		}
	},

	computed: {
		userConfig(): UserConfig {
			return this.userConfigStore.userConfig
		},

		isMtimeAvailable() {
			// Hide mtime column on narrow screens
			if (this.fileListWidth < 768) {
				return false
			}
			return this.nodes.some(node => node.mtime !== undefined)
		},
		isSizeAvailable() {
			// Hide size column on narrow screens
			if (this.fileListWidth < 768) {
				return false
			}
			return this.nodes.some(node => node.size !== undefined)
		},

		cantUpload() {
			return this.currentFolder && (this.currentFolder.permissions & Permission.CREATE) === 0
		},

		caption() {
			const defaultCaption = t('files', 'List of files and folders.')
			const viewCaption = this.currentView.caption || defaultCaption
			const cantUploadCaption = this.cantUpload ? t('files', 'You do not have permission to upload or create files here.') : null
			const sortableCaption = t('files', 'Column headers with buttons are sortable.')
			const virtualListNote = t('files', 'This list is not fully rendered for performance reasons. The files will be rendered as you navigate through the list.')
			return [
				viewCaption,
				cantUploadCaption,
				sortableCaption,
				virtualListNote,
			].filter(Boolean).join('\n')
		},

		columnCount() {
			if (!this.userConfig.grid_view || !this.$el) {
				return 1
			}

			const itemWidth = Number.parseInt(window.getComputedStyle(this.$el).getPropertyValue('--row-width'))
			return Math.floor(this.fileListWidth / itemWidth)
		},
	},

	watch: {
		fileId: {
			handler(fileId) {
				this.scrollToFile(fileId, false)
			},
			immediate: true,
		},
	},

	created() {
		useHotKey(['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'], this.onKeyDown, {
			stop: true,
			prevent: true,
		})
	},

	methods: {
		onScroll(event: Event) {
			(this.$refs.virtualList as ComponentPublicInstance<typeof VirtualList>).onScroll(event)
		},

		scrollToFile(fileId: number|null, warn = true) {
			if (fileId) {
				const index = this.nodes.findIndex(node => node.fileid === fileId)
				if (index === -1) {
					if (warn) {
						showError(t('files', 'File not found'))
					}
					return
				}

				this.scrollToIndex = Math.max(0, index)
			}
		},

		onKeyDown(event: KeyboardEvent) {
			// Up and down arrow keys
			if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
				const index = this.nodes.findIndex(node => node.fileid === this.fileId) ?? 0
				const nextIndex = event.key === 'ArrowUp' ? index - this.columnCount : index + this.columnCount
				if (nextIndex < 0 || nextIndex >= this.nodes.length) {
					return
				}

				const nextNode = this.nodes[nextIndex]

				if (nextNode && nextNode?.fileid) {
					this.setActiveNode(nextNode)
				}
			}

			// if grid mode, left and right arrow keys
			if (this.userConfig.grid_view && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) {
				const index = this.nodes.findIndex(node => node.fileid === this.fileId) ?? 0
				const nextIndex = event.key === 'ArrowLeft' ? index - 1 : index + 1
				if (nextIndex < 0 || nextIndex >= this.nodes.length) {
					return
				}

				const nextNode = this.nodes[nextIndex]
				if (nextNode) {
					this.setActiveNode(nextNode)
				}
			}
		},

		setActiveNode(node: Node) {
			const { fileid } = node
			if (fileid === undefined) {
				logger.debug('Cannot set node without file id as active node', { node })
				return
			}

			logger.debug('Navigating to file ' + node.path, { node, fileid })
			this.scrollToFile(fileid)

			// Remove openfile and opendetails from the URL
			const query = { ...this.$route.query }
			delete query.openfile
			delete query.opendetails

			this.activeStore.setActiveNode(node)

			// Silent update of the URL
			window.OCP.Files.Router.goToRoute(
				null,
				{ ...this.$route.params, fileid: String(fileid) },
				query,
				true,
			)
		},
	},
})
</script>
