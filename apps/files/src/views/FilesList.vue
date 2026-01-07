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
					<NcButton
						v-if="canShare && fileListWidth >= 512"
						:aria-label="shareButtonLabel"
						:class="{ 'files-list__header-share-button--shared': shareButtonType }"
						:title="shareButtonLabel"
						class="files-list__header-share-button"
						variant="tertiary"
						@click="openSharingSidebar">
						<template #icon>
							<LinkIcon v-if="shareButtonType === ShareType.Link" />
							<AccountPlusIcon v-else :size="20" />
						</template>
					</NcButton>

					<!-- Uploader -->
					<UploadPicker
						v-if="canUpload && !isQuotaExceeded && currentFolder"
						allow-folders
						:no-label="fileListWidth <= 511"
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

			<NcActions
				class="files-list__header-actions"
				:inline="1"
				variant="tertiary"
				force-name>
				<NcActionButton
					v-for="action in enabledFileListActions"
					:key="action.id"
					:disabled="!!loadingAction"
					:data-cy-files-list-action="action.id"
					close-after-click
					@click="execFileListAction(action)">
					<template #icon>
						<NcLoadingIcon v-if="loadingAction === action.id" :size="18" />
						<NcIconSvgWrapper
							v-else-if="action.iconSvgInline !== undefined && currentView"
							:svg="action.iconSvgInline(currentView)" />
					</template>
					{{ actionDisplayName(action) }}
				</NcActionButton>
			</NcActions>

			<NcButton
				v-if="enableGridView"
				:aria-label="gridViewButtonLabel"
				:title="gridViewButtonLabel"
				class="files-list__header-grid-button"
				variant="tertiary"
				@click="toggleGridView">
				<template #icon>
					<ListViewIcon v-if="userConfig.grid_view" />
					<ViewGridIcon v-else />
				</template>
			</NcButton>
		</div>

		<!-- Drag and drop notice -->
		<DragAndDropNotice v-if="!loading && canUpload && currentFolder" :current-folder="currentFolder" />

		<!--
			Initial current view loading0. This should never happen,
			views are supposed to be registered far earlier in the lifecycle.
			In case the URL is bad or a view is missing, we show a loading icon.
		-->
		<NcLoadingIcon
			v-if="!currentView"
			class="files-list__loading-icon"
			:size="38"
			:name="t('files', 'Loading current folder')" />

		<!-- File list - always mounted -->
		<FilesListVirtual
			v-else
			ref="filesListVirtual"
			:current-folder="currentFolder"
			:current-view="currentView"
			:nodes="dirContentsSorted"
			:summary="summary">
			<template #empty>
				<!-- Initial loading -->
				<NcLoadingIcon
					v-if="loading && !isRefreshing"
					class="files-list__loading-icon"
					:size="38"
					:name="t('files', 'Loading current folder')" />

				<!-- Empty due to error -->
				<NcEmptyContent v-else-if="error" :name="error" data-cy-files-content-error>
					<template #action>
						<NcButton variant="secondary" @click="fetchContent">
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
				<NcEmptyContent
					v-else
					:name="currentView?.emptyTitle || t('files', 'No files in here')"
					:description="currentView?.emptyCaption || t('files', 'Upload some content or sync with your devices!')"
					data-cy-files-content-empty>
					<template v-if="directory !== '/'" #action>
						<!-- Uploader -->
						<UploadPicker
							v-if="canUpload && !isQuotaExceeded"
							allow-folders
							class="files-list__header-upload-button"
							:content="getContent"
							:destination="currentFolder"
							:forbidden-characters="forbiddenCharacters"
							multiple
							@failed="onUploadFail"
							@uploaded="onUpload" />
						<NcButton v-else :to="toPreviousDir" variant="primary">
							{{ t('files', 'Go back') }}
						</NcButton>
					</template>
					<template #icon>
						<NcIconSvgWrapper :svg="currentView?.icon" />
					</template>
				</NcEmptyContent>
			</template>
		</FilesListVirtual>
	</NcAppContent>
</template>

<script lang="ts">
import type { ContentsWithRoot, FileListAction, INode, Node } from '@nextcloud/files'
import type { Upload } from '@nextcloud/upload'
import type { ComponentPublicInstance } from 'vue'
import type { Route } from 'vue-router'
import type { UserConfig } from '../types.ts'

import { getCurrentUser } from '@nextcloud/auth'
import { getCapabilities } from '@nextcloud/capabilities'
import { showError, showSuccess, showWarning } from '@nextcloud/dialogs'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { Folder, getFileListActions, Permission, sortNodes } from '@nextcloud/files'
import { getRemoteURL, getRootPath } from '@nextcloud/files/dav'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { dirname, join } from '@nextcloud/paths'
import { ShareType } from '@nextcloud/sharing'
import { UploadPicker, UploadStatus } from '@nextcloud/upload'
import { useThrottleFn } from '@vueuse/core'
import { normalize, relative } from 'path'
import { computed, defineComponent } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import AccountPlusIcon from 'vue-material-design-icons/AccountPlusOutline.vue'
import IconAlertCircleOutline from 'vue-material-design-icons/AlertCircleOutline.vue'
import ListViewIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import LinkIcon from 'vue-material-design-icons/Link.vue'
import IconReload from 'vue-material-design-icons/Reload.vue'
import ViewGridIcon from 'vue-material-design-icons/ViewGridOutline.vue'
import BreadCrumbs from '../components/BreadCrumbs.vue'
import DragAndDropNotice from '../components/DragAndDropNotice.vue'
import FilesListVirtual from '../components/FilesListVirtual.vue'
import { useFileListWidth } from '../composables/useFileListWidth.ts'
import { useRouteParameters } from '../composables/useRouteParameters.ts'
import logger from '../logger.ts'
import filesSortingMixin from '../mixins/filesSorting.ts'
import { useActiveStore } from '../store/active.ts'
import { useFilesStore } from '../store/files.ts'
import { useFiltersStore } from '../store/filters.ts'
import { usePathsStore } from '../store/paths.ts'
import { useSelectionStore } from '../store/selection.ts'
import { useSidebarStore } from '../store/sidebar.ts'
import { useUploaderStore } from '../store/uploader.ts'
import { useUserConfigStore } from '../store/userconfig.ts'
import { useViewConfigStore } from '../store/viewConfig.ts'
import { humanizeWebDAVError } from '../utils/davUtils.ts'
import { defaultView } from '../utils/filesViews.ts'
import { getSummaryFor } from '../utils/fileUtils.ts'

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
		const sidebar = useSidebarStore()
		const activeStore = useActiveStore()
		const filesStore = useFilesStore()
		const filtersStore = useFiltersStore()
		const pathsStore = usePathsStore()
		const selectionStore = useSelectionStore()
		const uploaderStore = useUploaderStore()
		const userConfigStore = useUserConfigStore()
		const viewConfigStore = useViewConfigStore()

		const fileListWidth = useFileListWidth()
		const { directory, fileId } = useRouteParameters()

		const enableGridView = (loadState('core', 'config', [])['enable_non-accessible_features'] ?? true)
		const forbiddenCharacters = loadState<string[]>('files', 'forbiddenCharacters', [])

		const currentView = computed(() => activeStore.activeView)

		return {
			currentView,
			directory,
			fileId,
			fileListWidth,
			t,

			sidebar,
			activeStore,
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
			controller: new AbortController(),
			promise: null as Promise<ContentsWithRoot> | null,

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
				const controller = new AbortController()
				return (await view.getContents(normalizedPath, { signal: controller.signal })).contents
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
		currentFolder(): Folder {
			// Temporary fake folder to use until we have the first valid folder
			// fetched and cached. This allow us to mount the FilesListVirtual
			// at all time and avoid unmount/mount and undesired rendering issues.
			const dummyFolder = new Folder({
				id: 0,
				source: getRemoteURL() + getRootPath(),
				root: getRootPath(),
				owner: getCurrentUser()?.uid || null,
				permissions: Permission.NONE,
			})

			if (!this.currentView?.id) {
				return dummyFolder
			}

			return this.filesStore.getDirectoryByPath(this.currentView.id, this.directory) || dummyFolder
		},

		dirContents(): Node[] {
			return (this.currentFolder?._children || [])
				.map(this.filesStore.getNode)
				.filter((node: Node) => !!node)
		},

		/**
		 * The current directory contents.
		 */
		dirContentsSorted(): INode[] {
			if (!this.currentView) {
				return []
			}

			const customColumn = (this.currentView?.columns || [])
				.find((column) => column.id === this.sortingMode)

			// Custom column must provide their own sorting methods
			if (customColumn?.sort && typeof customColumn.sort === 'function') {
				const results = [...this.dirContentsFiltered].sort(customColumn.sort)
				return this.isAscSorting ? results : results.reverse()
			}

			const nodes = sortNodes(this.dirContentsFiltered, {
				sortFavoritesFirst: this.userConfig.sort_favorites_first,
				sortFoldersFirst: this.userConfig.sort_folders_first,
				sortingMode: this.sortingMode,
				sortingOrder: this.isAscSorting ? 'asc' : 'desc',
			})

			// TODO upstream this
			if (this.currentView.id === 'files') {
				nodes.sort((a, b) => {
					const aa = relative(a.source, this.currentFolder!.source) === '..'
					const bb = relative(b.source, this.currentFolder!.source) === '..'
					if (aa && bb) {
						return 0
					} else if (aa) {
						return -1
					}
					return 1
				})
			}

			return nodes
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
			if (this.shareTypesAttributes.some((type) => type === ShareType.Link)) {
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
				.filter((action) => {
					if (action.enabled === undefined) {
						return true
					}
					return action.enabled({
						view: this.currentView!,
						folder: this.currentFolder!,
						contents: this.dirContents,
					})
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

		debouncedFetchContent() {
			return useThrottleFn(this.fetchContent, 800, true)
		},
	},

	watch: {
		/**
		 * Handle rendering the custom empty view
		 *
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

		currentFolder() {
			this.activeStore.activeFolder = this.currentFolder
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
			this.sidebar.close()
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
		subscribe('files:node:updated', this.onUpdatedNode)

		// reload on settings change
		subscribe('files:config:updated', this.fetchContent)

		// filter content if filter were changed
		subscribe('files:filters:changed', this.filterDirContent)

		subscribe('files:search:updated', this.onUpdateSearch)

		// Finally, fetch the current directory contents
		await this.fetchContent()
		if (this.fileId) {
			// If we have a fileId, let's check if the file exists
			const node = this.dirContents.find((node) => node.fileid?.toString() === this.fileId?.toString())
			// If the file isn't in the current directory nor if
			// the current directory is the file, we show an error
			if (!node && this.currentFolder?.fileid?.toString() !== this.fileId.toString()) {
				showError(t('files', 'The file could not be found'))
			}
		}
	},

	unmounted() {
		unsubscribe('files:node:updated', this.onUpdatedNode)
		unsubscribe('files:config:updated', this.fetchContent)
		unsubscribe('files:filters:changed', this.filterDirContent)
		unsubscribe('files:search:updated', this.onUpdateSearch)
	},

	methods: {
		onUpdateSearch({ query, scope }) {
			if (query && scope !== 'filter') {
				this.debouncedFetchContent()
			}
		},

		async fetchContent() {
			this.loading = true
			this.error = null
			const dir = this.directory
			const currentView = this.currentView

			if (!currentView) {
				logger.debug('The current view does not exists or is not ready.', { currentView })

				// If we still haven't a valid view, let's wait for the page to load
				// then try again. Else redirect to the default view
				window.addEventListener('DOMContentLoaded', () => {
					if (!this.currentView) {
						logger.warn('No current view after DOMContentLoaded, redirecting to the default view')
						window.OCP.Files.Router.goToRoute(null, { view: defaultView() })
					}
				}, { once: true })
				return
			}

			logger.debug('Fetching contents for directory', { dir, currentView })

			// If we have a cancellable promise ongoing, cancel it
			if (this.promise) {
				this.controller.abort()
				logger.debug('Cancelled previous ongoing fetch')
			}

			// Fetch the current dir contents
			this.controller = new AbortController()
			this.promise = currentView.getContents(dir, { signal: this.controller.signal })
			try {
				const { folder, contents } = await this.promise
				logger.debug('Fetched contents', { dir, folder, contents })

				// Update store
				this.filesStore.updateNodes(contents)

				// Define current directory children
				// TODO: make it more official
				this.$set(folder, '_children', contents.map((node) => node.source))

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
				const folders = contents.filter((node) => node.type === 'folder')
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
		 * The upload manager have finished handling the queue
		 *
		 * @param upload the uploaded data
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

			this.sidebar.open(this.currentFolder, 'sharing')
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
				const success = await action.exec({
					nodes: [this.source],
					view: this.currentView,
					folder: this.currentFolder,
					contents: this.dirContents,
				})
				// If the action returns null, we stay silent
				if (success === null || success === undefined) {
					return
				}

				if (success) {
					showSuccess(t('files', '{displayName}: done', { displayName }))
					return
				}
				showError(t('files', '{displayName}: failed', { displayName }))
			} catch (error) {
				logger.error('Error while executing action', { action, error })
				showError(t('files', '{displayName}: failed', { displayName }))
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
