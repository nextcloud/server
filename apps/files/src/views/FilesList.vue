<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcAppContent :page-heading="pageHeading" data-cy-files-content>
		<div class="files-list__header" :class="{ 'files-list__header--public': isPublic }">
			<!-- Current folder breadcrumbs -->
			<BreadCrumbs :path="directory" @reload="fetchContent">
				<template #actions>
					<!-- Sharing button -->
					<NcButton v-if="canShare && fileListWidth >= 512"
						:aria-label="shareButtonLabel"
						:class="{ 'files-list__header-share-button--shared': shareButtonType }"
						:title="shareButtonLabel"
						class="files-list__header-share-button"
						type="tertiary"
						@click="openSharingSidebar">
						<template #icon>
							<LinkIcon v-if="shareButtonType === ShareType.Link" />
							<AccountPlusIcon v-else :size="20" />
						</template>
					</NcButton>

					<!-- Uploader -->
					<UploadPicker v-if="canUpload && !isQuotaExceeded && currentFolder"
						allow-folders
						class="files-list__header-upload-button"
						:content="getContent"
						:destination="currentFolder"
						:forbidden-characters="forbiddenCharacters"
						multiple
						@failed="onUploadFail"
						@uploaded="onUpload" />
				</template>
			</BreadCrumbs>

			<!-- Secondary loading indicator -->
			<NcLoadingIcon v-if="isRefreshing" class="files-list__refresh-icon" />

			<NcActions class="files-list__header-actions"
				:inline="1"
				type="tertiary"
				force-name>
				<NcActionButton v-for="action in enabledFileListActions"
					:key="action.id"
					:disabled="!!loadingAction"
					:data-cy-files-list-action="action.id"
					close-after-click
					@click="execFileListAction(action)">
					<template #icon>
						<NcLoadingIcon v-if="loadingAction === action.id" :size="18" />
						<NcIconSvgWrapper v-else-if="action.iconSvgInline !== undefined && currentView"
							:svg="action.iconSvgInline(currentView)" />
					</template>
					{{ actionDisplayName(action) }}
				</NcActionButton>
			</NcActions>

			<NcButton v-if="fileListWidth >= 512 && enableGridView"
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
		</div>

		<!-- Drag and drop notice -->
		<DragAndDropNotice v-if="!loading && canUpload && currentFolder" :current-folder="currentFolder" />

		<!-- Initial loading -->
		<NcLoadingIcon v-if="loading && !isRefreshing"
			class="files-list__loading-icon"
			:size="38"
			:name="t('files', 'Loading current folder')" />

		<!-- Empty content placeholder -->
		<template v-else-if="!loading && isEmptyDir && currentFolder && currentView">
			<div class="files-list__before">
				<!-- Headers -->
				<FilesListHeader v-for="header in headers"
					:key="header.id"
					:current-folder="currentFolder"
					:current-view="currentView"
					:header="header" />
			</div>
			<!-- Empty due to error -->
			<NcEmptyContent v-if="error" :name="error" data-cy-files-content-error>
				<template #action>
					<NcButton type="secondary" @click="fetchContent">
						<template #icon>
							<IconReload :size="20" />
						</template>
						{{ t('files', 'Retry') }}
					</NcButton>
				</template>
				<template #icon>
					<IconAlertCircleOutline />
				</template>
			</NcEmptyContent>
			<!-- Custom empty view -->
			<div v-else-if="currentView?.emptyView" class="files-list__empty-view-wrapper">
				<div ref="customEmptyView" />
			</div>
			<!-- Default empty directory view -->
			<NcEmptyContent v-else
				:name="currentView?.emptyTitle || t('files', 'No files in here')"
				:description="currentView?.emptyCaption || t('files', 'Upload some content or sync with your devices!')"
				data-cy-files-content-empty>
				<template v-if="directory !== '/'" #action>
					<!-- Uploader -->
					<UploadPicker v-if="canUpload && !isQuotaExceeded"
						allow-folders
						class="files-list__header-upload-button"
						:content="getContent"
						:destination="currentFolder"
						:forbidden-characters="forbiddenCharacters"
						multiple
						@failed="onUploadFail"
						@uploaded="onUpload" />
					<NcButton v-else :to="toPreviousDir" type="primary">
						{{ t('files', 'Go back') }}
					</NcButton>
				</template>
				<template #icon>
					<NcIconSvgWrapper :svg="currentView.icon" />
				</template>
			</NcEmptyContent>
		</template>

		<!-- File list -->
		<FilesListVirtual v-else
			ref="filesListVirtual"
			:current-folder="currentFolder"
			:current-view="currentView"
			:nodes="dirContentsSorted"
			:summary="summary" />
	</NcAppContent>
</template>

<script lang="ts">
import type { ContentsWithRoot, FileListAction, Folder, INode } from '@nextcloud/files'
import type { Upload } from '@nextcloud/upload'
import type { CancelablePromise } from 'cancelable-promise'
import type { ComponentPublicInstance } from 'vue'
import type { Route } from 'vue-router'
import type { UserConfig } from '../types.ts'

import { getCapabilities } from '@nextcloud/capabilities'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { Node, Permission, sortNodes, getFileListActions } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { join, dirname, normalize } from 'path'
import { showError, showSuccess, showWarning } from '@nextcloud/dialogs'
import { ShareType } from '@nextcloud/sharing'
import { UploadPicker, UploadStatus } from '@nextcloud/upload'
import { loadState } from '@nextcloud/initial-state'
import { defineComponent } from 'vue'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

import AccountPlusIcon from 'vue-material-design-icons/AccountPlus.vue'
import IconAlertCircleOutline from 'vue-material-design-icons/AlertCircleOutline.vue'
import IconReload from 'vue-material-design-icons/Reload.vue'
import LinkIcon from 'vue-material-design-icons/Link.vue'
import ListViewIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import ViewGridIcon from 'vue-material-design-icons/ViewGrid.vue'

import { action as sidebarAction } from '../actions/sidebarAction.ts'
import { getSummaryFor } from '../utils/fileUtils.ts'
import { humanizeWebDAVError } from '../utils/davUtils.ts'
import { useFileListHeaders } from '../composables/useFileListHeaders.ts'
import { useFileListWidth } from '../composables/useFileListWidth.ts'
import { useFilesStore } from '../store/files.ts'
import { useFiltersStore } from '../store/filters.ts'
import { useNavigation } from '../composables/useNavigation.ts'
import { usePathsStore } from '../store/paths.ts'
import { useRouteParameters } from '../composables/useRouteParameters.ts'
import { useSelectionStore } from '../store/selection.ts'
import { useUploaderStore } from '../store/uploader.ts'
import { useUserConfigStore } from '../store/userconfig.ts'
import { useViewConfigStore } from '../store/viewConfig.ts'
import BreadCrumbs from '../components/BreadCrumbs.vue'
import DragAndDropNotice from '../components/DragAndDropNotice.vue'
import FilesListHeader from '../components/FilesListHeader.vue'
import FilesListVirtual from '../components/FilesListVirtual.vue'
import filesSortingMixin from '../mixins/filesSorting.ts'
import logger from '../logger.ts'

const isSharingEnabled = (getCapabilities() as { files_sharing?: boolean })?.files_sharing !== undefined

export default defineComponent({
	name: 'FilesList',

	components: {
		BreadCrumbs,
		DragAndDropNotice,
		FilesListHeader,
		FilesListVirtual,
		LinkIcon,
		ListViewIcon,
		NcAppContent,
		NcActions,
		NcActionButton,
		NcButton,
		NcEmptyContent,
		NcIconSvgWrapper,
		NcLoadingIcon,
		AccountPlusIcon,
		UploadPicker,
		ViewGridIcon,
		IconAlertCircleOutline,
		IconReload,
	},

	mixins: [
		filesSortingMixin,
	],

	props: {
		isPublic: {
			type: Boolean,
			default: false,
		},
	},

	setup() {
		const { currentView } = useNavigation()
		const { directory, fileId } = useRouteParameters()
		const fileListWidth = useFileListWidth()
		const filesStore = useFilesStore()
		const filtersStore = useFiltersStore()
		const pathsStore = usePathsStore()
		const selectionStore = useSelectionStore()
		const uploaderStore = useUploaderStore()
		const userConfigStore = useUserConfigStore()
		const viewConfigStore = useViewConfigStore()

		const enableGridView = (loadState('core', 'config', [])['enable_non-accessible_features'] ?? true)
		const forbiddenCharacters = loadState<string[]>('files', 'forbiddenCharacters', [])

		return {
			currentView,
			directory,
			fileId,
			fileListWidth,
			headers: useFileListHeaders(),
			t,

			filesStore,
			filtersStore,
			pathsStore,
			selectionStore,
			uploaderStore,
			userConfigStore,
			viewConfigStore,

			// non reactive data
			enableGridView,
			forbiddenCharacters,
			ShareType,
		}
	},

	data() {
		return {
			loading: true,
			loadingAction: null as string | null,
			error: null as string | null,
			promise: null as CancelablePromise<ContentsWithRoot> | Promise<ContentsWithRoot> | null,

			dirContentsFiltered: [] as INode[],
		}
	},

	computed: {
		/**
		 * Get a callback function for the uploader to fetch directory contents for conflict resolution
		 */
		getContent() {
			const view = this.currentView!
			return async (path?: string) => {
				// as the path is allowed to be undefined we need to normalize the path ('//' to '/')
				const normalizedPath = normalize(`${this.currentFolder?.path ?? ''}/${path ?? ''}`)
				// Try cache first
				const nodes = this.filesStore.getNodesByPath(view.id, normalizedPath)
				if (nodes.length > 0) {
					return nodes
				}
				// If not found in the files store (cache)
				// use the current view to fetch the content for the requested path
				return (await view.getContents(normalizedPath)).contents
			}
		},

		userConfig(): UserConfig {
			return this.userConfigStore.userConfig
		},

		pageHeading(): string {
			const title = this.currentView?.name ?? t('files', 'Files')

			if (this.currentFolder === undefined || this.directory === '/') {
				return title
			}
			return `${this.currentFolder.displayname} - ${title}`
		},

		/**
		 * The current folder.
		 */
		currentFolder(): Folder | undefined {
			if (!this.currentView?.id) {
				return
			}

			if (this.directory === '/') {
				return this.filesStore.getRoot(this.currentView.id)
			}

			const source = this.pathsStore.getPath(this.currentView.id, this.directory)
			if (source === undefined) {
				return
			}

			return this.filesStore.getNode(source) as Folder
		},

		dirContents(): Node[] {
			return (this.currentFolder?._children || [])
				.map(this.filesStore.getNode)
				.filter((node: Node) => !!node)
		},

		/**
		 * The current directory contents.
		 */
		dirContentsSorted() {
			if (!this.currentView) {
				return []
			}

			const customColumn = (this.currentView?.columns || [])
				.find(column => column.id === this.sortingMode)

			// Custom column must provide their own sorting methods
			if (customColumn?.sort && typeof customColumn.sort === 'function') {
				const results = [...this.dirContentsFiltered].sort(customColumn.sort)
				return this.isAscSorting ? results : results.reverse()
			}

			return sortNodes(this.dirContentsFiltered, {
				sortFavoritesFirst: this.userConfig.sort_favorites_first,
				sortFoldersFirst: this.userConfig.sort_folders_first,
				sortingMode: this.sortingMode,
				sortingOrder: this.isAscSorting ? 'asc' : 'desc',
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
			const dir = this.directory.split('/').slice(0, -1).join('/') || '/'
			return { ...this.$route, query: { dir } }
		},

		shareTypesAttributes(): number[] | undefined {
			if (!this.currentFolder?.attributes?.['share-types']) {
				return undefined
			}
			return Object.values(this.currentFolder?.attributes?.['share-types'] || {}).flat() as number[]
		},
		shareButtonLabel() {
			if (!this.shareTypesAttributes) {
				return t('files', 'Share')
			}

			if (this.shareButtonType === ShareType.Link) {
				return t('files', 'Shared by link')
			}
			return t('files', 'Shared')
		},
		shareButtonType(): ShareType | null {
			if (!this.shareTypesAttributes) {
				return null
			}

			// If all types are links, show the link icon
			if (this.shareTypesAttributes.some(type => type === ShareType.Link)) {
				return ShareType.Link
			}

			return ShareType.User
		},

		gridViewButtonLabel() {
			return this.userConfig.grid_view
				? t('files', 'Switch to list view')
				: t('files', 'Switch to grid view')
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

		/**
		 * Check if current folder has share permissions
		 */
		canShare() {
			return isSharingEnabled && !this.isPublic
				&& this.currentFolder && (this.currentFolder.permissions & Permission.SHARE) !== 0
		},

		showCustomEmptyView() {
			return !this.loading && this.isEmptyDir && this.currentView?.emptyView !== undefined
		},

		enabledFileListActions() {
			if (!this.currentView || !this.currentFolder) {
				return []
			}

			const actions = getFileListActions()
			const enabledActions = actions
				.filter(action => {
					if (action.enabled === undefined) {
						return true
					}
					return action.enabled(
						this.currentView!,
						this.dirContents,
						this.currentFolder as Folder,
					)
				})
				.toSorted((a, b) => a.order - b.order)
			return enabledActions
		},

		/**
		 * Using the filtered content if filters are active
		 */
		summary() {
			const hidden = this.dirContents.length - this.dirContentsFiltered.length
			return getSummaryFor(this.dirContentsFiltered, hidden)
		},
	},

	watch: {
		/**
		 * Update the window title to match the page heading
		 */
		pageHeading() {
			document.title = `${this.pageHeading} - ${getCapabilities().theming?.productName ?? 'Nextcloud'}`
		},

		/**
		 * Handle rendering the custom empty view
		 * @param show The current state if the custom empty view should be rendered
		 */
		showCustomEmptyView(show: boolean) {
			if (show) {
				this.$nextTick(() => {
					const el = this.$refs.customEmptyView as HTMLDivElement
					// We can cast here because "showCustomEmptyView" assets that current view is set
					this.currentView!.emptyView!(el)
				})
			}
		},

		currentView(newView, oldView) {
			if (newView?.id === oldView?.id) {
				return
			}

			logger.debug('View changed', { newView, oldView })
			this.selectionStore.reset()
			this.fetchContent()
		},

		directory(newDir, oldDir) {
			logger.debug('Directory changed', { newDir, oldDir })
			// TODO: preserve selection on browsing?
			this.selectionStore.reset()
			if (window.OCA.Files.Sidebar?.close) {
				window.OCA.Files.Sidebar.close()
			}
			this.fetchContent()

			// Scroll to top, force virtual scroller to re-render
			const filesListVirtual = this.$refs?.filesListVirtual as ComponentPublicInstance<typeof FilesListVirtual> | undefined
			if (filesListVirtual?.$el) {
				filesListVirtual.$el.scrollTop = 0
			}
		},

		dirContents(contents) {
			logger.debug('Directory contents changed', { view: this.currentView, folder: this.currentFolder, contents })
			emit('files:list:updated', { view: this.currentView, folder: this.currentFolder, contents })
			// Also refresh the filtered content
			this.filterDirContent()
		},
	},

	async mounted() {
		subscribe('files:node:deleted', this.onNodeDeleted)
		subscribe('files:node:updated', this.onUpdatedNode)

		// reload on settings change
		subscribe('files:config:updated', this.fetchContent)

		// filter content if filter were changed
		subscribe('files:filters:changed', this.filterDirContent)

		// Finally, fetch the current directory contents
		await this.fetchContent()
		if (this.fileId) {
			// If we have a fileId, let's check if the file exists
			const node = this.dirContents.find(node => node.fileid.toString() === this.fileId.toString())
			// If the file isn't in the current directory nor if
			// the current directory is the file, we show an error
			if (!node && this.currentFolder.fileid.toString() !== this.fileId.toString()) {
				showError(t('files', 'The file could not be found'))
			}
		}
	},

	unmounted() {
		unsubscribe('files:node:deleted', this.onNodeDeleted)
		unsubscribe('files:node:updated', this.onUpdatedNode)
		unsubscribe('files:config:updated', this.fetchContent)
	},

	methods: {
		async fetchContent() {
			this.loading = true
			this.error = null
			const dir = this.directory
			const currentView = this.currentView

			if (!currentView) {
				logger.debug('The current view doesn\'t exists or is not ready.', { currentView })
				return
			}

			// If we have a cancellable promise ongoing, cancel it
			if (this.promise && 'cancel' in this.promise) {
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
				this.$set(folder, '_children', contents.map(node => node.source))

				// If we're in the root dir, define the root
				if (dir === '/') {
					this.filesStore.setRoot({ service: currentView.id, root: folder })
				} else {
					// Otherwise, add the folder to the store
					if (folder.fileid) {
						this.filesStore.updateNodes([folder])
						this.pathsStore.addPath({ service: currentView.id, source: folder.source, path: dir })
					} else {
						// If we're here, the view API messed up
						logger.fatal('Invalid root folder returned', { dir, folder, currentView })
					}
				}

				// Update paths store
				const folders = contents.filter(node => node.type === 'folder')
				folders.forEach((node) => {
					this.pathsStore.addPath({ service: currentView.id, source: node.source, path: join(dir, node.basename) })
				})
			} catch (error) {
				logger.error('Error while fetching content', { error })
				this.error = humanizeWebDAVError(error)
			} finally {
				this.loading = false
			}

		},

		/**
		 * Handle the node deleted event to reset open file
		 * @param node The deleted node
		 */
		 onNodeDeleted(node: Node) {
			if (node.fileid && node.fileid === this.fileId) {
				if (node.fileid === this.currentFolder?.fileid) {
					// Handle the edge case that the current directory is deleted
					// in this case we need to keep the current view but move to the parent directory
					window.OCP.Files.Router.goToRoute(
						null,
						{ view: this.currentView!.id },
						{ dir: this.currentFolder?.dirname ?? '/' },
					)
				} else {
					// If the currently active file is deleted we need to remove the fileid and possible the `openfile` query
					window.OCP.Files.Router.goToRoute(
						null,
						{ ...this.$route.params, fileid: undefined },
						{ ...this.$route.query, openfile: undefined },
					)
				}
			}
		},

		/**
		 * The upload manager have finished handling the queue
		 * @param {Upload} upload the uploaded data
		 */
		onUpload(upload: Upload) {
			// Let's only refresh the current Folder
			// Navigating to a different folder will refresh it anyway
			const needsRefresh = dirname(upload.source) === this.currentFolder!.source

			// TODO: fetch uploaded files data only
			// Use parseInt(upload.response?.headers?.['oc-fileid']) to get the fileid
			if (needsRefresh) {
				// fetchContent will cancel the previous ongoing promise
				this.fetchContent()
			}
		},

		async onUploadFail(upload: Upload) {
			const status = upload.response?.status || 0

			if (upload.status === UploadStatus.CANCELLED) {
				showWarning(t('files', 'Upload was cancelled by user'))
				return
			}

			// Check known status codes
			if (status === 507) {
				showError(t('files', 'Not enough free space'))
				return
			} else if (status === 404 || status === 409) {
				showError(t('files', 'Target folder does not exist any more'))
				return
			} else if (status === 403) {
				showError(t('files', 'Operation is blocked by access control'))
				return
			}

			// Else we try to parse the response error message
			if (typeof upload.response?.data === 'string') {
				try {
					const parser = new DOMParser()
					const doc = parser.parseFromString(upload.response.data, 'text/xml')
					const message = doc.getElementsByTagName('s:message')[0]?.textContent ?? ''
					if (message.trim() !== '') {
						// The server message is also translated
						showError(t('files', 'Error during upload: {message}', { message }))
						return
					}
				} catch (error) {
					logger.error('Could not parse message', { error })
				}
			}

			// Finally, check the status code if we have one
			if (status !== 0) {
				showError(t('files', 'Error during upload, status code {status}', { status }))
				return
			}

			showError(t('files', 'Unknown error during upload'))
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

		openSharingSidebar() {
			if (!this.currentFolder) {
				logger.debug('No current folder found for opening sharing sidebar')
				return
			}

			if (window?.OCA?.Files?.Sidebar?.setActiveTab) {
				window.OCA.Files.Sidebar.setActiveTab('sharing')
			}
			sidebarAction.exec(this.currentFolder, this.currentView!, this.currentFolder.path)
		},

		toggleGridView() {
			this.userConfigStore.update('grid_view', !this.userConfig.grid_view)
		},

		filterDirContent() {
			let nodes: INode[] = this.dirContents
			for (const filter of this.filtersStore.sortedFilters) {
				nodes = filter.filter(nodes)
			}
			this.dirContentsFiltered = nodes
		},

		actionDisplayName(action: FileListAction): string {
			let displayName = action.id
			try {
				displayName = action.displayName(this.currentView!)
			} catch (error) {
				logger.error('Error while getting action display name', { action, error })
			}
			return displayName
		},

		async execFileListAction(action: FileListAction) {
			this.loadingAction = action.id

			const displayName = this.actionDisplayName(action)
			try {
				const success = await action.exec(this.source, this.dirContents, this.currentDir)
				// If the action returns null, we stay silent
				if (success === null || success === undefined) {
					return
				}

				if (success) {
					showSuccess(t('files', '"{displayName}" action executed successfully', { displayName }))
					return
				}
				showError(t('files', '"{displayName}" action failed', { displayName }))
			} catch (error) {
				logger.error('Error while executing action', { action, error })
				showError(t('files', '"{displayName}" action failed', { displayName }))
			} finally {
				this.loadingAction = null
			}
		},
	},
})
</script>

<style scoped lang="scss">
:global(.toast-loading-icon) {
	// Reduce start margin (it was made for text but this is an icon)
	margin-inline-start: -4px;
	// 16px icon + 5px on both sides
	min-width: 26px;
}

.app-content {
	// Virtual list needs to be full height and is scrollable
	display: flex;
	overflow: hidden;
	flex-direction: column;
	max-height: 100%;
	position: relative !important;
}

.files-list {
	&__header {
		display: flex;
		align-items: center;
		// Do not grow or shrink (vertically)
		flex: 0 0;
		max-width: 100%;
		// Align with the navigation toggle icon
		margin-block: var(--app-navigation-padding, 4px);
		margin-inline: calc(var(--default-clickable-area, 44px) + 2 * var(--app-navigation-padding, 4px)) var(--app-navigation-padding, 4px);

		&--public {
			// There is no navigation toggle on public shares
			margin-inline: 0 var(--app-navigation-padding, 4px);
		}

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

		&-actions {
			min-width: fit-content !important;
			margin-inline: calc(var(--default-grid-baseline) * 2);
		}
	}

	&__before {
		display: flex;
		flex-direction: column;
		gap: calc(var(--default-grid-baseline) * 2);
		margin-inline: calc(var(--default-clickable-area) + 2 * var(--app-navigation-padding));
	}

	&__empty-view-wrapper {
		display: flex;
		height: 100%;
	}

	&__refresh-icon {
		flex: 0 0 var(--default-clickable-area);
		width: var(--default-clickable-area);
		height: var(--default-clickable-area);
	}

	&__loading-icon {
		margin: auto;
	}
}
</style>
