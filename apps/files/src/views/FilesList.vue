<!--
  - @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @license GNU AGPL version 3 or any later version
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
	<NcAppContent :page-heading="pageHeading" data-cy-files-content>
		<div class="files-list__header">
			<!-- Current folder breadcrumbs -->
			<BreadCrumbs :path="dir" @reload="fetchContent">
				<template #actions>
					<!-- Sharing button -->
					<NcButton v-if="canShare && filesListWidth >= 512"
						:aria-label="shareButtonLabel"
						:class="{ 'files-list__header-share-button--shared': shareButtonType }"
						:title="shareButtonLabel"
						class="files-list__header-share-button"
						type="tertiary"
						@click="openSharingSidebar">
						<template #icon>
							<LinkIcon v-if="shareButtonType === Type.SHARE_TYPE_LINK" />
							<AccountPlusIcon v-else :size="20" />
						</template>
					</NcButton>

					<!-- Disabled upload button -->
					<NcButton v-if="!canUpload || isQuotaExceeded"
						:aria-label="cantUploadLabel"
						:title="cantUploadLabel"
						class="files-list__header-upload-button--disabled"
						:disabled="true"
						type="secondary">
						<template #icon>
							<PlusIcon :size="20" />
						</template>
						{{ t('files', 'New') }}
					</NcButton>

					<!-- Uploader -->
					<UploadPicker v-else-if="currentFolder"
						:content="dirContents"
						:destination="currentFolder"
						:multiple="true"
						class="files-list__header-upload-button"
						@failed="onUploadFail"
						@uploaded="onUpload" />
				</template>
			</BreadCrumbs>

			<NcButton v-if="filesListWidth >= 512 && enableGridView"
				:aria-label="gridViewButtonLabel"
				:title="gridViewButtonLabel"
				class="files-list__header-grid-button"
				type="tertiary"
				@click="toggleGridView">
				<template #icon>
					<ListViewIcon v-if="userConfig.grid_view" />
					<ViewGridIcon v-else />
				</template>
			</NcButton>

			<!-- Secondary loading indicator -->
			<NcLoadingIcon v-if="isRefreshing" class="files-list__refresh-icon" />
		</div>

		<!-- Drag and drop notice -->
		<DragAndDropNotice v-if="!loading && canUpload" :current-folder="currentFolder" />

		<!-- Initial loading -->
		<NcLoadingIcon v-if="loading && !isRefreshing"
			class="files-list__loading-icon"
			:size="38"
			:name="t('files', 'Loading current folder')" />

		<!-- Empty content placeholder -->
		<NcEmptyContent v-else-if="!loading && isEmptyDir"
			:name="currentView?.emptyTitle || t('files', 'No files in here')"
			:description="currentView?.emptyCaption || t('files', 'Upload some content or sync with your devices!')"
			data-cy-files-content-empty>
			<template #action>
				<NcButton v-if="dir !== '/'"
					:aria-label="t('files', 'Go to the previous folder')"
					type="primary"
					:to="toPreviousDir">
					{{ t('files', 'Go back') }}
				</NcButton>
			</template>
			<template #icon>
				<NcIconSvgWrapper :svg="currentView.icon" />
			</template>
		</NcEmptyContent>

		<!-- File list -->
		<FilesListVirtual v-else
			ref="filesListVirtual"
			:current-folder="currentFolder"
			:current-view="currentView"
			:nodes="dirContentsSorted" />
	</NcAppContent>
</template>

<script lang="ts">
import type { Route } from 'vue-router'
import type { Upload } from '@nextcloud/upload'
import type { UserConfig } from '../types.ts'
import type { View, ContentsWithRoot } from '@nextcloud/files'

import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { Folder, Node, Permission } from '@nextcloud/files'
import { getCapabilities } from '@nextcloud/capabilities'
import { join, dirname } from 'path'
import { orderBy } from 'natural-orderby'
import { Parser } from 'xml2js'
import { showError } from '@nextcloud/dialogs'
import { translate, translatePlural } from '@nextcloud/l10n'
import { Type } from '@nextcloud/sharing'
import { UploadPicker } from '@nextcloud/upload'
import { loadState } from '@nextcloud/initial-state'
import { defineComponent } from 'vue'

import LinkIcon from 'vue-material-design-icons/Link.vue'
import ListViewIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import AccountPlusIcon from 'vue-material-design-icons/AccountPlus.vue'
import ViewGridIcon from 'vue-material-design-icons/ViewGrid.vue'

import { action as sidebarAction } from '../actions/sidebarAction.ts'
import { useFilesStore } from '../store/files.ts'
import { usePathsStore } from '../store/paths.ts'
import { useSelectionStore } from '../store/selection.ts'
import { useUploaderStore } from '../store/uploader.ts'
import { useUserConfigStore } from '../store/userconfig.ts'
import { useViewConfigStore } from '../store/viewConfig.ts'
import BreadCrumbs from '../components/BreadCrumbs.vue'
import FilesListVirtual from '../components/FilesListVirtual.vue'
import filesListWidthMixin from '../mixins/filesListWidth.ts'
import filesSortingMixin from '../mixins/filesSorting.ts'
import logger from '../logger.js'
import DragAndDropNotice from '../components/DragAndDropNotice.vue'
import debounce from 'debounce'

const isSharingEnabled = (getCapabilities() as { files_sharing?: boolean })?.files_sharing !== undefined

export default defineComponent({
	name: 'FilesList',

	components: {
		BreadCrumbs,
		DragAndDropNotice,
		FilesListVirtual,
		LinkIcon,
		ListViewIcon,
		NcAppContent,
		NcButton,
		NcEmptyContent,
		NcIconSvgWrapper,
		NcLoadingIcon,
		PlusIcon,
		AccountPlusIcon,
		UploadPicker,
		ViewGridIcon,
	},

	mixins: [
		filesListWidthMixin,
		filesSortingMixin,
	],

	setup() {
		const filesStore = useFilesStore()
		const pathsStore = usePathsStore()
		const selectionStore = useSelectionStore()
		const uploaderStore = useUploaderStore()
		const userConfigStore = useUserConfigStore()
		const viewConfigStore = useViewConfigStore()

		const enableGridView = (loadState('core', 'config', [])['enable_non-accessible_features'] ?? true)

		return {
			filesStore,
			pathsStore,
			selectionStore,
			uploaderStore,
			userConfigStore,
			viewConfigStore,
			enableGridView,
		}
	},

	data() {
		return {
			filterText: '',
			loading: true,
			promise: null,
			Type,
		}
	},

	computed: {
		userConfig(): UserConfig {
			return this.userConfigStore.userConfig
		},

		currentView(): View {
			return this.$navigation.active || this.$navigation.views.find((view) => view.id === (this.$route.params?.view ?? 'files'))
		},

		pageHeading(): string {
			return this.currentView?.name ?? this.t('files', 'Files')
		},

		/**
		 * The current directory query.
		 */
		dir(): string {
			// Remove any trailing slash but leave root slash
			return (this.$route?.query?.dir?.toString() || '/').replace(/^(.+)\/$/, '$1')
		},

		/**
		 * The current folder.
		 */
		currentFolder(): Folder | undefined {
			if (!this.currentView?.id) {
				return
			}

			if (this.dir === '/') {
				return this.filesStore.getRoot(this.currentView.id)
			}
			const fileId = this.pathsStore.getPath(this.currentView.id, this.dir)
			return this.filesStore.getNode(fileId)
		},

		/**
		 * Directory content sorting parameters
		 * Provided by an extra computed property for caching
		 */
		sortingParameters() {
			const identifiers = [
				// 1: Sort favorites first if enabled
				...(this.userConfig.sort_favorites_first ? [v => v.attributes?.favorite !== 1] : []),
				// 2: Sort folders first if sorting by name
				...(this.userConfig.sort_folders_first ? [v => v.type !== 'folder'] : []),
				// 3: Use sorting mode if NOT basename (to be able to use displayName too)
				...(this.sortingMode !== 'basename' ? [v => v[this.sortingMode]] : []),
				// 4: Use displayName if available, fallback to name
				v => v.attributes?.displayName || v.basename,
				// 5: Finally, use basename if all previous sorting methods failed
				v => v.basename,
			]
			const orders = [
				// (for 1): always sort favorites before normal files
				...(this.userConfig.sort_favorites_first ? ['asc'] : []),
				// (for 2): always sort folders before files
				...(this.userConfig.sort_folders_first ? ['asc'] : []),
				// (for 3): Reverse if sorting by mtime as mtime higher means edited more recent -> lower
				...(this.sortingMode === 'mtime' ? [this.isAscSorting ? 'desc' : 'asc'] : []),
				// (also for 3 so make sure not to conflict with 2 and 3)
				...(this.sortingMode !== 'mtime' && this.sortingMode !== 'basename' ? [this.isAscSorting ? 'asc' : 'desc'] : []),
				// for 4: use configured sorting direction
				this.isAscSorting ? 'asc' : 'desc',
				// for 5: use configured sorting direction
				this.isAscSorting ? 'asc' : 'desc',
			]
			return [identifiers, orders] as const
		},

		/**
		 * The current directory contents.
		 */
		dirContentsSorted(): Node[] {
			if (!this.currentView) {
				return []
			}

			let filteredDirContent = [...this.dirContents]
			// Filter based on the filterText obtained from nextcloud:unified-search.search event.
			if (this.filterText) {
				filteredDirContent = filteredDirContent.filter(node => {
					return node.attributes.basename.toLowerCase().includes(this.filterText.toLowerCase())
				})
				console.debug('Files view filtered', filteredDirContent)
			}

			const customColumn = (this.currentView?.columns || [])
				.find(column => column.id === this.sortingMode)

			// Custom column must provide their own sorting methods
			if (customColumn?.sort && typeof customColumn.sort === 'function') {
				const results = [...this.dirContents].sort(customColumn.sort)
				return this.isAscSorting ? results : results.reverse()
			}

			return orderBy(
				filteredDirContent,
				...this.sortingParameters,
			)
		},

		dirContents(): Node[] {
			const showHidden = this.userConfigStore?.userConfig.show_hidden
			return (this.currentFolder?._children || [])
				.map(this.getNode)
				.filter(file => {
					if (!showHidden) {
						return file && file?.attributes?.hidden !== true && !file?.basename.startsWith('.')
					}

					return !!file
				})
		},

		/**
		 * The current directory is empty.
		 */
		isEmptyDir(): boolean {
			return this.dirContents.length === 0
		},

		/**
		 * We are refreshing the current directory.
		 * But we already have a cached version of it
		 * that is not empty.
		 */
		isRefreshing(): boolean {
			return this.currentFolder !== undefined
				&& !this.isEmptyDir
				&& this.loading
		},

		/**
		 * Route to the previous directory.
		 */
		toPreviousDir(): Route {
			const dir = this.dir.split('/').slice(0, -1).join('/') || '/'
			return { ...this.$route, query: { dir } }
		},

		shareAttributes(): number[] | undefined {
			if (!this.currentFolder?.attributes?.['share-types']) {
				return undefined
			}
			return Object.values(this.currentFolder?.attributes?.['share-types'] || {}).flat() as number[]
		},
		shareButtonLabel() {
			if (!this.shareAttributes) {
				return this.t('files', 'Share')
			}

			if (this.shareButtonType === Type.SHARE_TYPE_LINK) {
				return this.t('files', 'Shared by link')
			}
			return this.t('files', 'Shared')
		},
		shareButtonType(): Type | null {
			if (!this.shareAttributes) {
				return null
			}

			// If all types are links, show the link icon
			if (this.shareAttributes.some(type => type === Type.SHARE_TYPE_LINK)) {
				return Type.SHARE_TYPE_LINK
			}

			return Type.SHARE_TYPE_USER
		},

		gridViewButtonLabel() {
			return this.userConfig.grid_view
				? this.t('files', 'Switch to list view')
				: this.t('files', 'Switch to grid view')
		},

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
			}
			return this.t('files', 'You don’t have permission to upload or create files here')
		},

		/**
		 * Check if current folder has share permissions
		 */
		canShare() {
			return isSharingEnabled
				&& this.currentFolder && (this.currentFolder.permissions & Permission.SHARE) !== 0
		},
	},

	watch: {
		currentView(newView, oldView) {
			if (newView?.id === oldView?.id) {
				return
			}

			logger.debug('View changed', { newView, oldView })
			this.selectionStore.reset()
			this.fetchContent()
		},

		dir(newDir, oldDir) {
			logger.debug('Directory changed', { newDir, oldDir })
			// TODO: preserve selection on browsing?
			this.selectionStore.reset()
			this.fetchContent()

			// Scroll to top, force virtual scroller to re-render
			if (this.$refs?.filesListVirtual?.$el) {
				this.$refs.filesListVirtual.$el.scrollTop = 0
			}
		},

		dirContents(contents) {
			logger.debug('Directory contents changed', { view: this.currentView, folder: this.currentFolder, contents })
			emit('files:list:updated', { view: this.currentView, folder: this.currentFolder, contents })
		},
	},

	mounted() {
		this.fetchContent()
		subscribe('files:node:updated', this.onUpdatedNode)
		subscribe('nextcloud:unified-search.search', this.onSearch)
		subscribe('nextcloud:unified-search.reset', this.onSearch)
	},

	unmounted() {
		unsubscribe('files:node:updated', this.onUpdatedNode)
	},

	methods: {
		async fetchContent() {
			this.loading = true
			const dir = this.dir
			const currentView = this.currentView

			if (!currentView) {
				logger.debug('The current view doesn\'t exists or is not ready.', { currentView })
				return
			}

			// If we have a cancellable promise ongoing, cancel it
			if (typeof this.promise?.cancel === 'function') {
				this.promise.cancel()
				logger.debug('Cancelled previous ongoing fetch')
			}

			// Fetch the current dir contents
			this.promise = currentView.getContents(dir) as Promise<ContentsWithRoot>
			try {
				const { folder, contents } = await this.promise
				logger.debug('Fetched contents', { dir, folder, contents })

				// Update store
				this.filesStore.updateNodes(contents)

				// Define current directory children
				// TODO: make it more official
				this.$set(folder, '_children', contents.map(node => node.fileid))

				// If we're in the root dir, define the root
				if (dir === '/') {
					this.filesStore.setRoot({ service: currentView.id, root: folder })
				} else {
					// Otherwise, add the folder to the store
					if (folder.fileid) {
						this.filesStore.updateNodes([folder])
						this.pathsStore.addPath({ service: currentView.id, fileid: folder.fileid, path: dir })
					} else {
						// If we're here, the view API messed up
						logger.error('Invalid root folder returned', { dir, folder, currentView })
					}
				}

				// Update paths store
				const folders = contents.filter(node => node.type === 'folder')
				folders.forEach(node => {
					this.pathsStore.addPath({ service: currentView.id, fileid: node.fileid, path: join(dir, node.basename) })
				})
			} catch (error) {
				logger.error('Error while fetching content', { error })
			} finally {
				this.loading = false
			}

		},

		/**
		 * Get a cached note from the store
		 *
		 * @param {number} fileId the file id to get
		 * @return {Folder|File}
		 */
		getNode(fileId) {
			return this.filesStore.getNode(fileId)
		},

		/**
		 * The upload manager have finished handling the queue
		 * @param {Upload} upload the uploaded data
		 */
		onUpload(upload: Upload) {
			// Let's only refresh the current Folder
			// Navigating to a different folder will refresh it anyway
			const destinationSource = dirname(upload.source)
			const needsRefresh = destinationSource === this.currentFolder?.source

			// TODO: fetch uploaded files data only
			// Use parseInt(upload.response?.headers?.['oc-fileid']) to get the fileid
			if (needsRefresh) {
				// fetchContent will cancel the previous ongoing promise
				this.fetchContent()
			}
		},

		async onUploadFail(upload: Upload) {
			const status = upload.response?.status || 0

			// Check known status codes
			if (status === 507) {
				showError(this.t('files', 'Not enough free space'))
				return
			} else if (status === 404 || status === 409) {
				showError(this.t('files', 'Target folder does not exist any more'))
				return
			} else if (status === 403) {
				showError(this.t('files', 'Operation is blocked by access control'))
				return
			}

			// Else we try to parse the response error message
			try {
				const parser = new Parser({ trim: true, explicitRoot: false })
				const response = await parser.parseStringPromise(upload.response?.data)
				const message = response['s:message'][0] as string
				if (typeof message === 'string' && message.trim() !== '') {
					// The server message is also translated
					showError(this.t('files', 'Error during upload: {message}', { message }))
					return
				}
			} catch (error) {
				logger.error('Error while parsing', { error })
			}

			// Finally, check the status code if we have one
			if (status !== 0) {
				showError(this.t('files', 'Error during upload, status code {status}', { status }))
				return
			}

			showError(this.t('files', 'Unknown error during upload'))
		},

		/**
		 * Refreshes the current folder on update.
		 *
		 * @param node is the file/folder being updated.
		 */
		onUpdatedNode(node?: Node) {
			if (node?.fileid === this.currentFolder?.fileid) {
				this.fetchContent()
			}
		},
		/**
		 * Handle search event from unified search.
		 *
		 * @param searchEvent is event object.
		 */
		onSearch: debounce(function(searchEvent) {
			console.debug('Files app handling search event from unified search...', searchEvent)
			this.filterText = searchEvent.query
		}, 500),
		openSharingSidebar() {
			if (!this.currentFolder) {
				logger.debug('No current folder found for opening sharing sidebar')
				return
			}

			if (window?.OCA?.Files?.Sidebar?.setActiveTab) {
				window.OCA.Files.Sidebar.setActiveTab('sharing')
			}
			sidebarAction.exec(this.currentFolder, this.currentView, this.currentFolder.path)
		},
		toggleGridView() {
			this.userConfigStore.update('grid_view', !this.userConfig.grid_view)
		},

		t: translate,
		n: translatePlural,
	},
})
</script>

<style scoped lang="scss">
.app-content {
	// Virtual list needs to be full height and is scrollable
	display: flex;
	overflow: hidden;
	flex-direction: column;
	max-height: 100%;
	position: relative !important;
}

$margin: 4px;
$navigationToggleSize: 50px;

.files-list {
	&__header {
		display: flex;
		align-items: center;
		// Do not grow or shrink (vertically)
		flex: 0 0;
		// Align with the navigation toggle icon
		margin: $margin $margin $margin $navigationToggleSize;
		max-width: 100%;

		>* {
			// Do not grow or shrink (horizontally)
			// Only the breadcrumbs shrinks
			flex: 0 0;
		}

		&-share-button {
			color: var(--color-text-maxcontrast) !important;

			&--shared {
				color: var(--color-main-text) !important;
			}
		}
	}

	&__refresh-icon {
		flex: 0 0 44px;
		width: 44px;
		height: 44px;
	}

	&__loading-icon {
		margin: auto;
	}
}
</style>
