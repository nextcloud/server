<!--
 - @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 - @copyright Copyright (c) 2020 Gary Kim <gary@garykim.dev>
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
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -
 -->

<template>
	<!-- Single-file rendering -->
	<div v-if="el"
		id="viewer"
		:data-handler="handlerId">
		<component :is="currentFile.modal"
			v-if="!currentFile.failed"
			:key="currentFile | uniqueKey"
			ref="content"
			:active="true"
			:can-swipe="false"
			:can-zoom="false"
			v-bind="currentFile"
			:file-list="[currentFile]"
			:is-full-screen="false"
			:loaded.sync="currentFile.loaded"
			:is-sidebar-shown="false"
			class="viewer__file viewer__file--active"
			@error="currentFailed" />
		<Error v-else
			:name="currentFile.basename" />
	</div>

	<!-- Modal view rendering -->
	<NcModal v-else-if="initiated || currentFile.modal"
		id="viewer"
		:additional-trap-elements="trapElements"
		:class="modalClass"
		:clear-view-delay="-1 /* disable fade-out because of accessibility reasons */"
		:close-button-contained="false"
		:dark="true"
		:data-handler="handlerId"
		:enable-slideshow="hasPrevious || hasNext"
		:slideshow-paused="editing"
		:enable-swipe="canSwipe && !editing"
		:has-next="hasNext"
		:has-previous="hasPrevious"
		:inline-actions="canEdit ? 1 : 0"
		:spread-navigation="true"
		:style="{ width: isSidebarShown ? `${sidebarPosition}px` : null }"
		:name="currentFile.basename"
		class="viewer"
		size="full"
		@close="close"
		@previous="previous"
		@next="next">
		<!-- ACTIONS -->
		<template #actions>
			<!-- Inline items -->
			<NcActionButton v-if="canEdit"
				:close-after-click="true"
				@click="onEdit">
				<template #icon>
					<Pencil :size="20" />
				</template>
				{{ t('viewer', 'Edit') }}
			</NcActionButton>
			<!-- Menu items -->
			<NcActionButton :close-after-click="true"
				@click="toggleFullScreen">
				<template #icon>
					<Fullscreen v-if="!isFullscreenMode" :size="20" />
					<FullscreenExit v-else :size="20" />
				</template>
				{{ isFullscreenMode ? t('viewer', 'Exit full screen') : t('viewer', 'Full screen') }}
			</NcActionButton>
			<NcActionButton v-if="enableSidebar && Sidebar && sidebarOpenFilePath && !isSidebarShown"
				:close-after-click="true"
				icon="icon-menu-sidebar"
				@click="showSidebar">
				{{ t('viewer', 'Open sidebar') }}
			</NcActionButton>
			<NcActionLink v-if="canDownload"
				:download="currentFile.basename"
				:close-after-click="true"
				:href="downloadPath">
				<template #icon>
					<Download :size="20" />
				</template>
				{{ t('viewer', 'Download') }}
			</NcActionLink>
			<NcActionButton v-if="canDelete"
				:close-after-click="true"
				@click="onDelete">
				<template #icon>
					<Delete :size="20" />
				</template>
				{{ t('viewer', 'Delete') }}
			</NcActionButton>
		</template>

		<div class="viewer__content"
			:class="contentClass"
			@click.self.exact="close"
			@contextmenu="preventContextMenu">
			<!-- COMPARE FILE -->
			<div v-if="comparisonFile && !comparisonFile.failed && showComparison" class="viewer__file-wrapper">
				<component :is="comparisonFile.modal"
					:key="comparisonFile | uniqueKey"
					ref="comparison-content"
					v-bind="comparisonFile"
					:active="true"
					:can-swipe="false"
					:can-zoom="false"
					:editing="false"
					:is-full-screen="isFullscreen"
					:is-sidebar-shown="isSidebarShown"
					:loaded.sync="comparisonFile.loaded"
					class="viewer__file viewer__file--active"
					@error="comparisonFailed" />
			</div>

			<!-- PREVIOUS -->
			<div v-if="previousFile"
				:key="previousFile | uniqueKey"
				class="viewer__file-wrapper viewer__file-wrapper--hidden"
				aria-hidden="true"
				inert>
				<component :is="previousFile.modal"
					v-if="!previousFile.failed"
					ref="previous-content"
					v-bind="previousFile"
					:file-list="fileList"
					class="viewer__file"
					@error="previousFailed" />
				<Error v-else
					:name="previousFile.basename" />
			</div>

			<!-- CURRENT -->
			<div :key="currentFile | uniqueKey" class="viewer__file-wrapper">
				<component :is="currentFile.modal"
					v-if="!currentFile.failed"
					ref="content"
					v-bind="currentFile"
					:active="true"
					:can-swipe.sync="canSwipe"
					:can-zoom="true"
					:editing.sync="editing"
					:file-list="fileList"
					:is-full-screen="isFullscreen"
					:is-sidebar-shown="isSidebarShown"
					:loaded.sync="currentFile.loaded"
					class="viewer__file viewer__file--active"
					@error="currentFailed" />
				<Error v-else
					:name="currentFile.basename" />
			</div>

			<!-- NEXT -->
			<div v-if="nextFile"
				:key="nextFile | uniqueKey"
				class="viewer__file-wrapper viewer__file-wrapper--hidden"
				aria-hidden="true"
				inert>
				<component :is="nextFile.modal"
					v-if="!nextFile.failed"
					ref="next-content"
					v-bind="nextFile"
					:file-list="fileList"
					class="viewer__file"
					@error="nextFailed" />
				<Error v-else
					:name="nextFile.basename" />
			</div>
		</div>
	</NcModal>
</template>

<script>
import '@nextcloud/dialogs/style.css'
import Vue from 'vue'

import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { Node, davRemoteURL, davRootPath } from '@nextcloud/files'
import { showError } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'

import isFullscreen from '@nextcloud/vue/dist/Mixins/isFullscreen.js'
import isMobile from '@nextcloud/vue/dist/Mixins/isMobile.js'

import { canDownload } from '../utils/canDownload.ts'
import { extractFilePaths, sortCompare } from '../utils/fileUtils.ts'
import getSortingConfig from '../services/FileSortingConfig.ts'
import cancelableRequest from '../utils/CancelableRequest.js'
import Error from '../components/Error.vue'
import File from '../models/file.js'
import getFileInfo from '../services/FileInfo.ts'
import getFileList from '../services/FileList.ts'
import Mime from '../mixins/Mime.js'
import logger from '../services/logger.js'

import Delete from 'vue-material-design-icons/Delete.vue'
import Download from 'vue-material-design-icons/Download.vue'
import Fullscreen from 'vue-material-design-icons/Fullscreen.vue'
import FullscreenExit from 'vue-material-design-icons/FullscreenExit.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'

// Dynamic loading
const NcModal = () => import('@nextcloud/vue/dist/Components/NcModal.js')
const NcActionLink = () => import('@nextcloud/vue/dist/Components/NcActionLink.js')
const NcActionButton = () => import('@nextcloud/vue/dist/Components/NcActionButton.js')

export default {
	name: 'Viewer',

	components: {
		Delete,
		Download,
		Error,
		Fullscreen,
		FullscreenExit,
		NcActionButton,
		NcActionLink,
		NcModal,
		Pencil,
	},

	filters: {
		uniqueKey(file) {
			return '' + file.fileid + file.source
		},
	},

	mixins: [isFullscreen, isMobile],

	data() {
		return {
			// Reactivity bindings
			Viewer: OCA.Viewer,
			Sidebar: null,
			handlers: OCA.Viewer.availableHandlers,

			// Viewer variables
			components: {},
			mimeGroups: {},
			registeredHandlers: {},

			// Files variables
			currentIndex: 0,
			previousFile: {},
			currentFile: {},
			comparisonFile: null,
			nextFile: {},
			fileList: [],
			sortingConfig: null,

			// States
			isLoaded: false,
			initiated: false,
			editing: false,

			// cancellable requests
			cancelRequestFile: () => {},
			cancelRequestFolder: () => {},

			// Flags
			sidebarPosition: 0,
			isSidebarShown: false,
			isFullscreenMode: false,
			canSwipe: true,
			isStandalone: false,
			theme: null,
			root: davRemoteURL,
			handlerId: '',

			trapElements: [],
		}
	},

	computed: {
		downloadPath() {
			return this.currentFile.source ?? this.currentFile.davPath
		},
		hasPrevious() {
			return this.fileList.length > 1
				&& (this.canLoop || !this.isStartOfList)
		},
		hasNext() {
			return this.fileList.length > 1
				&& (this.canLoop || !this.isEndOfList)
		},
		file() {
			return this.Viewer.file
		},
		fileInfo() {
			return this.Viewer.fileInfo
		},
		comparisonFileInfo() {
			return this.Viewer.compareFileInfo
		},
		files() {
			return this.Viewer.files
		},
		enableSidebar() {
			return this.Viewer.enableSidebar
		},
		el() {
			return this.Viewer.el
		},
		loadMore() {
			return this.Viewer.loadMore
		},
		canLoop() {
			return this.Viewer.canLoop
		},
		isStartOfList() {
			return this.currentIndex === 0
		},
		isEndOfList() {
			return this.currentIndex === this.fileList.length - 1
		},

		isImage() {
			return ['image/jpeg', 'image/png', 'image/webp'].includes(this.currentFile?.mime)
		},

		/**
		 * Returns the path to the current opened file in the sidebar.
		 *
		 * If the sidebar is available but closed an empty string is returned.
		 * If the sidebar is not available null is returned.
		 *
		 * @return {string|null} the path to the current opened file in the
		 *          sidebar, if any.
		 */
		sidebarFile() {
			return this.Sidebar && this.Sidebar.file
		},
		sidebarOpenFilePath() {
			try {
				const relativePath = this.currentFile?.davPath?.split(davRootPath)[1]
				return relativePath?.split('/')?.map(decodeURIComponent)?.join('/')
			} catch (e) {
				return false
			}
		},

		/**
		 * Is the current user allowed to delete the file?
		 *
		 * @return {boolean}
		 */
		canDelete() {
			return this.currentFile?.permissions?.includes('D')
		},

		/**
		 * Is the current user allowed to download the file
		 *
		 * @return {boolean}
		 */
		canDownload() {
			// download not possible for comparison
			if (this.comparisonFile) {
				return false
			}
			return this.currentFile && canDownload(this.currentFile)
		},

		/**
		 * Is the current user allowed to edit the file ?
		 * https://github.com/nextcloud/server/blob/7718c9776c5903474b8f3cf958cdd18a53b2449e/apps/dav/lib/Connector/Sabre/Node.php#L357-L387
		 *
		 * @return {boolean}
		 */
		canEdit() {
			return !this.isMobile
				&& this.canDownload
				&& this.currentFile?.permissions?.includes('W')
				&& this.isImage
				&& !this.comparisonFile
				&& (loadState('core', 'config', [])['enable_non-accessible_features'] ?? true)
		},

		modalClass() {
			return {
				'icon-loading': !this.currentFile.loaded && !this.currentFile.failed,
				'theme--undefined': this.theme === null,
				'theme--dark': this.theme === 'dark',
				'theme--light': this.theme === 'light',
				'theme--default': this.theme === 'default',
				'image--fullscreen': this.isImage && this.isFullscreenMode,
			}
		},

		showComparison() {
			return !this.isMobile
		},

		contentClass() {
			return {
				'viewer--split': this.comparisonFile,
			}
		},

		isSameFile() {
			return (fileInfo = null, path = null) => {
				if (
					path && path === this.currentFile.path
					&& !this.currentFile.source
				) {
					return true
				}

				if (
					fileInfo && fileInfo.fileid === this.currentFile.fileid
					&& fileInfo.mtime && fileInfo.mtime === this.currentFile.mtime
					&& fileInfo.source && fileInfo.source === this.currentFile.source
				) {
					return true
				}

				return false
			}
		},
	},

	watch: {
		el(element) {
			logger.info(element)
			this.$nextTick(() => {
				const viewerRoot = document.getElementById('viewer')
				if (element) {
					const el = document.querySelector(element)
					if (el) {
						el.appendChild(viewerRoot)
					} else {
						logger.warn('Could not find element ', { element })
					}
				} else {
					document.body.appendChild(viewerRoot)
				}
			})
		},

		file(path) {
			// we got a valid path! Load file...
			if (path && path.trim() !== '') {
				logger.info('Opening viewer for file ', { path })
				this.openFile(path, OCA.Viewer.overrideHandlerId)
			} else {
				// path is empty, we're closing!
				this.cleanup()
			}
		},

		fileInfo(fileInfo) {
			if (fileInfo) {
				logger.info('Opening viewer for fileInfo ', { fileInfo })
				this.openFileInfo(fileInfo, OCA.Viewer.overrideHandlerId)
			} else {
				// object is undefined, we're closing!
				this.cleanup()
			}
		},

		comparisonFileInfo(fileInfo) {
			if (fileInfo) {
				logger.info('Opening viewer for comparisonFileInfo ', { fileInfo })
				this.compareFile(fileInfo)
			} else {
				// object is undefined, we're closing!
				this.cleanup()
			}
		},

		files(fileList) {
			// the files list changed, let's update the current opened index
			const currentIndex = fileList.findIndex(file => file.filename === this.currentFile.filename)
			if (currentIndex > -1) {
				this.currentIndex = currentIndex
				logger.debug('The files list changed, new current file index is ' + currentIndex)
			}
			// finally replace the fileList
			this.fileList = fileList
		},

		// user reached the end of list
		async isEndOfList(isEndOfList) {
			if (!isEndOfList || this.el) {
				return
			}

			// if we have a loadMore handler, let's fetch more files
			if (this.loadMore && typeof this.loadMore === 'function') {
				logger.debug('Fetching additional files...')
				const list = await this.loadMore()

				if (Array.isArray(list) && list.length > 0) {
					this.fileList.push(...list)
				}
			}
		},

	},

	beforeMount() {
		this.isStandalone = window.OCP?.Files === undefined
		if (this.isStandalone) {
			logger.info('No OCP.Files app found, viewer is now in standalone mode')
		}

		// register on load
		document.addEventListener('DOMContentLoaded', () => {
			// register all primary components mimes
			this.handlers.forEach(handler => {
				this.registerHandler(handler)
			})

			// then register aliases. We need to have the components
			// first so we can bind the alias to them.
			this.handlers.forEach(handler => {
				this.registerHandlerAlias(handler)
			})
			this.isLoaded = true

			// bind Sidebar if available
			if (OCA?.Files?.Sidebar) {
				this.Sidebar = OCA.Files.Sidebar.state
			}

			logger.info(`${this.handlers.length} viewer handlers registered`, { handlers: this.handlers })
		})

		window.addEventListener('resize', this.onResize)
	},

	mounted() {
		// React to Files' Sidebar events.
		subscribe('files:sidebar:opened', this.handleAppSidebarOpen)
		subscribe('files:sidebar:closed', this.handleAppSidebarClose)
		subscribe('files:node:updated', this.handleFileUpdated)
		subscribe('viewer:trapElements:changed', this.handleTrapElementsChange)
		window.addEventListener('keydown', this.keyboardDeleteFile)
		window.addEventListener('keydown', this.keyboardDownloadFile)
		window.addEventListener('keydown', this.keyboardEditFile)
		this.addFullscreenEventListeners()
	},

	beforeDestroy() {
		window.removeEventListener('resize', this.onResize)
	},

	destroyed() {
		// Unsubscribe to Files Sidebar events.
		unsubscribe('files:sidebar:opened', this.handleAppSidebarOpen)
		unsubscribe('files:sidebar:closed', this.handleAppSidebarClose)
		unsubscribe('viewer:trapElements:changed', this.handleTrapElementsChange)
		window.removeEventListener('keydown', this.keyboardDeleteFile)
		window.removeEventListener('keydown', this.keyboardDownloadFile)
		window.removeEventListener('keydown', this.keyboardEditFile)
		this.removeFullscreenEventListeners()
	},

	methods: {
		/**
		 * If there is no download permission also hide the context menu.
		 * @param {MouseEvent} event The mouse click event
		 */
		preventContextMenu(event) {
			if (this.canDownload) {
				return
			}
			event.preventDefault()
		},

		async beforeOpen() {
			// initial loading start
			this.initiated = true

			if (OCA?.Files?.Sidebar?.setFullScreenMode) {
				OCA.Files.Sidebar.setFullScreenMode(true)
			}
			this.sortingConfig = await getSortingConfig()

			// Load Roboto font for visual regression tests
			if (window.loadRoboto) {
				logger.debug('⚠️ Loading roboto font for visual regression tests')
				import('@fontsource/roboto/index.css')
				delete window.loadRoboto
			}
		},

		/**
		 * Open the view and display the clicked file
		 *
		 * @param {string} path the file path to open
		 * @param {string|null} overrideHandlerId the ID of the handler with which to view the files, if any
		 */
		async openFile(path, overrideHandlerId = null) {
			await this.beforeOpen()

			// cancel any previous request
			this.cancelRequestFile()

			// do not open the same file again
			if (this.isSameFile(null, path)) {
				return
			}

			const { request: fileRequest, cancel: cancelRequestFile } = cancelableRequest(getFileInfo)
			this.cancelRequestFile = cancelRequestFile

			// extract needed info from path
			const [, fileName] = extractFilePaths(path)

			// prevent scrolling while opened
			if (!this.el) {
				document.body.style.overflow = 'hidden'
				document.documentElement.style.overflow = 'hidden'
			}

			// swap title with original one
			const title = document.getElementsByTagName('head')[0].getElementsByTagName('title')[0]
			if (title && !title.dataset.old) {
				title.dataset.old = document.title
				this.updateTitle(fileName)
			}

			try {
				// retrieve and store the file info
				const fileInfo = await fileRequest(path)
				console.debug('File info for ' + path + ' fetched', fileInfo)
				await this.openFileInfo(fileInfo, overrideHandlerId)
			} catch (error) {
				if (error?.response?.status === 404) {
					logger.error('The file no longer exists, error: ', { error })
					showError(t('viewer', 'This file no longer exists'))
					this.close()
				} else {
					console.error('Could not open file ' + path, error)
				}
			}
		},

		/**
		 * Open the view and display the clicked file from a known file info object
		 *
		 * @param {object} fileInfo the file info object to open
		 * @param {string|null} overrideHandlerId the ID of the handler with which to view the files, if any
		 */
		async openFileInfo(fileInfo, overrideHandlerId = null) {
			this.beforeOpen()
			// cancel any previous request
			this.cancelRequestFolder()

			// do not open the same file info again
			if (this.isSameFile(fileInfo)) {
				return
			}

			// get original mime and alias
			const mime = fileInfo.mime
			const alias = mime.split('/')[0]

			let handler
			// Try provided handler, if any
			if (overrideHandlerId !== null) {
				const overrideHandler = Object.values(this.registeredHandlers).find(h => h.id === overrideHandlerId)
				handler = overrideHandler ?? handler
			}
			// If no provided handler, or provided handler not found: try a supported handler with mime/mime-alias
			if (!handler) {
				handler = this.registeredHandlers[mime] ?? this.registeredHandlers[alias]
			}

			// if we don't have a handler for this mime, abort
			if (!handler) {
				logger.error('The following file could not be displayed', { fileInfo })
				showError(t('viewer', 'There is no plugin available to display this file type'))
				this.close()
				return
			}

			this.theme = handler.theme ?? 'dark'
			this.handlerId = handler.id

			// check if part of a group, if so retrieve full files list
			const group = this.mimeGroups[mime]
			if (this.files && this.files.length > 0) {
				logger.debug('A files list have been provided. No folder content will be fetched.')
				// we won't sort files here, let's use the order the array has
				this.fileList = this.files

				// store current position
				this.currentIndex = this.fileList.findIndex(file => file.filename === fileInfo.filename)
			} else if (group && this.el === null) {
				const mimes = this.mimeGroups[group]
					? this.mimeGroups[group]
					: [mime]

				// retrieve folder list
				const { request: folderRequest, cancel: cancelRequestFolder } = cancelableRequest(getFileList)
				this.cancelRequestFolder = cancelRequestFolder
				const [dirPath] = extractFilePaths(fileInfo.filename)
				const fileList = await folderRequest(dirPath)

				// filter out the unwanted mimes
				const filteredFiles = fileList.filter(file => file.mime && mimes.indexOf(file.mime) !== -1)

				// sort like the files list
				// TODO: implement global sorting API
				// https://github.com/nextcloud/server/blob/a83b79c5f8ab20ed9b4d751167417a65fa3c42b8/apps/files/lib/Controller/ApiController.php#L247
				this.fileList = filteredFiles.sort((a, b) => sortCompare(a, b, this.sortingConfig.key, this.sortingConfig.asc))

				// store current position
				this.currentIndex = this.fileList.findIndex(file => file.filename === fileInfo.filename)
			} else {
				this.currentIndex = 0
				this.fileList = [fileInfo]
			}

			// get saved fileInfo
			fileInfo = this.fileList[this.currentIndex] ?? fileInfo

			// show file
			this.currentFile = new File(fileInfo, mime, handler.component)
			this.comparisonFile = null
			this.updatePreviousNext()

			// if sidebar was opened before, let's update the file
			this.changeSidebar()
		},

		/**
		 * Open the view and display the file from the file list
		 *
		 * @param {object} fileInfo the opened file info
		 */
		openFileFromList(fileInfo) {
			// override mimetype if existing alias
			const mime = fileInfo.mime
			this.currentFile = new File(fileInfo, mime, this.components[mime])
			this.changeSidebar()
			this.updatePreviousNext()
		},

		async compareFile(fileInfo) {
			this.comparisonFile = new File(fileInfo, fileInfo.mime, this.components[fileInfo.mime])
		},

		/**
		 * Show sidebar if available and a file is already opened
		 */
		changeSidebar() {
			if (this.sidebarFile) {
				this.showSidebar()
			}
		},

		/**
		 * Update the previous and next file components
		 */
		updatePreviousNext() {
			const prev = this.fileList[this.currentIndex - 1]
			const next = this.fileList[this.currentIndex + 1]

			if (prev) {
				const mime = prev.mime
				if (this.components[mime]) {
					this.previousFile = new File(prev, mime, this.components[mime])
				}
			} else {
				// RESET
				this.previousFile = null
			}

			if (next) {
				const mime = next.mime
				if (this.components[mime]) {
					this.nextFile = new File(next, mime, this.components[mime])
				}
			} else {
				// RESET
				this.nextFile = null
			}

		},

		updateTitle(fileName) {
			document.title = `${fileName} - ${OCA.Theming?.name ?? oc_defaults.name}`
		},

		/**
		 * Registering possible new handlers
		 *
		 * @param {object} handler the handler to register
		 * @param {string} handler.id unique handler identifier
		 * @param {Array} handler.mimes list of valid mimes compatible with the handler
		 * @param {object} handler.component a VueJs component to render when a file matching the mime list is opened
		 * @param {string} [handler.group] a group name to be associated with for the slideshow
		 */
		registerHandler(handler) {
			// checking if handler is not already registered
			if (handler.id && Object.values(this.registeredHandlers).findIndex((h) => h.id === handler.id) > -1) {
				logger.error('The following handler is already registered', { handler })
				return
			}

			// checking valid handler id
			if (!handler.id || handler.id.trim() === '' || typeof handler.id !== 'string') {
				logger.error('The following handler doesn\'t have a valid id', { handler })
				return
			}

			// checking if no valid mimes data but alias. If so, skipping...
			if (!(handler.mimes && Array.isArray(handler.mimes)) && handler.mimesAliases) {
				return
			}

			// Nothing available to process! Failure
			if (!(handler.mimes && Array.isArray(handler.mimes)) && !handler.mimesAliases) {
				logger.error('The following handler doesn\'t have a valid mime array', { handler })
				return
			}

			// checking valid handler component data
			if ((!handler.component || (typeof handler.component !== 'object' && typeof handler.component !== 'function'))) {
				logger.error('The following handler doesn\'t have a valid component', { handler })
				return
			}

			// force apply mixin
			handler.component.mixins = [...handler?.component?.mixins ?? [], Mime]

			// parsing mimes registration
			if (handler.mimes) {
				handler.mimes.forEach(mime => {
					// checking valid mime
					if (this.components[mime]) {
						logger.error('The following mime is already registered', { mime, handler })
						return
					}

					// register groups
					this.registerGroups({ mime, group: handler.group })

					// register mime's component
					this.components[mime] = handler.component
					Vue.component(handler.component.name, handler.component)

					// set the handler as registered
					this.registeredHandlers[mime] = handler
				})
			}
		},

		registerHandlerAlias(handler) {
			// parsing aliases registration
			if (handler.mimesAliases) {
				Object.keys(handler.mimesAliases).forEach(mime => {

					if (handler.mimesAliases && typeof handler.mimesAliases !== 'object') {
						logger.error('The following handler doesn\'t have a valid mimesAliases object', { handler })
						return

					}

					// this is the targeted alias
					const alias = handler.mimesAliases[mime]

					// checking valid mime
					if (this.components[mime]) {
						logger.error('The following mime is already registered', { mime, handler })
						return
					}
					if (!this.components[alias]) {
						logger.error('The requested alias does not exists', { alias, mime, handler })
						return
					}

					// register groups if the request alias had a group
					this.registerGroups({ mime, group: this.mimeGroups[alias] })

					// register mime's component
					this.components[mime] = this.components[alias]

					// set the handler as registered
					this.registeredHandlers[mime] = handler
				})
			}
		},

		registerGroups({ mime, group }) {
			if (group) {
				this.mimeGroups[mime] = group
				// init if undefined
				if (!this.mimeGroups[group]) {
					this.mimeGroups[group] = []
				}
				this.mimeGroups[group].push(mime)
			}
		},

		/**
		 * Close the viewer
		 */
		close() {
			// This will set file to ''
			// which then triggers cleanup.
			OCA.Viewer.close()

			if (OCA?.Files?.Sidebar) {
				OCA.Files.Sidebar.setFullScreenMode(false)
			}

			if (this.isFullscreenMode) {
				this.exitFullscreen()
			}
		},

		keyboardDeleteFile(event) {
			if (this.canDelete && event.key === 'Delete' && event.ctrlKey === true) {
				this.onDelete()
			}
		},

		keyboardDownloadFile(event) {
			if (event.key === 's' && event.ctrlKey === true) {
				event.preventDefault()
				if (this.canDownload) {
					const a = document.createElement('a')
					a.href = this.currentFile.davPath
					a.download = this.currentFile.basename
					document.body.appendChild(a)
					a.click()
					document.body.removeChild(a)
				}
			}
		},

		keyboardEditFile(event) {
			if (event.key === 'e' && event.ctrlKey === true) {
				event.preventDefault()
				if (this.canEdit) {
					this.onEdit()
				}
			}
		},

		cleanup() {
			// reset all properties
			this.currentFile = {}
			this.comparisonFile = null
			this.currentModal = null
			this.fileList = []
			this.initiated = false
			this.theme = null

			// cancel requests
			this.cancelRequestFile()
			this.cancelRequestFolder()

			// restore default
			document.body.style.overflow = null
			document.documentElement.style.overflow = null

			// Callback before updating the title
			// If the callback creates a new entry in browser history
			// the title update will affect the new entry
			// rather then the previous one.
			this.Viewer.onClose()

			// swap back original title
			const title = document.getElementsByTagName('head')[0].getElementsByTagName('title')[0]
			if (title && title.dataset.old) {
				document.title = title.dataset.old
				delete title.dataset.old
			}
		},

		/**
		 * Open previous available file
		 */
		previous() {
			const oldFileInfo = this.fileList[this.currentIndex]
			this.currentIndex--
			if (this.currentIndex < 0) {
				this.currentIndex = this.fileList.length - 1
			}

			const fileInfo = this.fileList[this.currentIndex]
			this.openFileFromList(fileInfo)
			this.Viewer.onPrev(fileInfo, oldFileInfo)
			this.updateTitle(this.currentFile.basename)
		},

		/**
		 * Open next available file
		 */
		next() {
			const oldFileInfo = this.fileList[this.currentIndex]
			this.currentIndex++
			if (this.currentIndex > this.fileList.length - 1) {
				this.currentIndex = 0
			}

			const fileInfo = this.fileList[this.currentIndex]
			this.openFileFromList(fileInfo)
			this.Viewer.onNext(fileInfo, oldFileInfo)
			this.updateTitle(this.currentFile.basename)
		},

		/**
		 * Failures handlers
		 */
		comparisonFailed() {
			this.comparisonFile.failed = true
		},

		previousFailed() {
			this.previousFile.failed = true
		},

		currentFailed() {
			this.currentFile.failed = true
		},

		nextFailed() {
			this.nextFile.failed = true
		},

		/**
		 * Show the sharing sidebar
		 */

		async showSidebar() {
			// Open the sidebar sharing tab
			// TODO: also hide figure, needs a proper method for it in server Sidebar

			if (this.enableSidebar && OCA?.Files?.Sidebar) {
				await OCA.Files.Sidebar.open(this.sidebarOpenFilePath)
			}
		},

		handleAppSidebarOpen() {
			this.isSidebarShown = true
			const sidebar = document.querySelector('aside.app-sidebar')
			if (sidebar) {
				this.sidebarPosition = sidebar.getBoundingClientRect().left
				this.trapElements = [sidebar]
			}
		},

		handleAppSidebarClose() {
			this.isSidebarShown = false
			this.trapElements = []
		},

		// Update etag of updated file to break cache.
		/**
		 *
		 * @param {Node} node
		 */
		async handleFileUpdated(node) {
			const index = this.fileList.findIndex(({ fileid: currentFileId }) => currentFileId === node.fileid)

			// Ensure compatibility with the legacy data model that the Viewer is using. (see "model.ts").
			// This can be removed once Viewer is migrated to the new Node API.
			node.etag = node.attributes.etag
			this.fileList.splice(index, 1, node)
			if (node.fileid === this.currentFile.fileid) {
				this.currentFile.etag = node.attributes.etag
			}
		},

		onResize() {
			const sidebar = document.querySelector('aside.app-sidebar')
			if (sidebar) {
				this.sidebarPosition = sidebar.getBoundingClientRect().left
			}
		},

		async onDelete() {
			try {
				const fileid = this.currentFile.fileid
				const url = this.source ?? this.currentFile.davPath

				await axios.delete(url)
				emit('files:node:deleted', { fileid })

				// fileid is not unique, basename is not unqiue, filename is
				const currentIndex = this.fileList.findIndex(file => file.filename === this.currentFile.filename)
				if (this.hasPrevious || this.hasNext) {
					// Checking the previous or next file
					this.hasPrevious ? this.previous() : this.next()

					this.fileList.splice(currentIndex, 1)
				} else {
					this.close()
				}
			} catch (error) {
				console.error(error)
				showError(error)
			}
		},

		onEdit() {
			this.editing = true
		},

		handleTrapElementsChange(element) {
			this.trapElements.push(element)
		},

		// Support full screen API on standard-compliant browsers and Safari (apparently except iPhone).
		// Implementation based on:
		//   https://developer.mozilla.org/en-US/docs/Web/API/Fullscreen_API/Guide

		toggleFullScreen() {
			if (this.isFullscreenMode) {
				this.exitFullscreen()
			} else {
				this.requestFullscreen()
			}
		},

		requestFullscreen() {
			const el = document.documentElement
			if (el.requestFullscreen) {
				el.requestFullscreen()
			} else if (el.webkitRequestFullscreen) {
				el.webkitRequestFullscreen()
			}
		},

		exitFullscreen() {
			if (document.exitFullscreen) {
				document.exitFullscreen()
			} else if (document.webkitExitFullscreen) {
				document.webkitExitFullscreen()
			}
		},

		addFullscreenEventListeners() {
			document.addEventListener('fullscreenchange', this.onFullscreenchange)
			document.addEventListener('webkitfullscreenchange', this.onFullscreenchange)
		},

		removeFullscreenEventListeners() {
			document.addEventListener('fullscreenchange', this.onFullscreenchange)
			document.addEventListener('webkitfullscreenchange', this.onFullscreenchange)
		},

		onFullscreenchange() {
			if (document.fullscreenElement === document.documentElement
				|| document.webkitFullscreenElement === document.documentElement) {
				this.isFullscreenMode = true
			} else {
				this.isFullscreenMode = false
			}
		},

	},
}
</script>

<style lang="scss" scoped>
.viewer {
	&.modal-mask {
		transition: width ease 100ms, background-color .3s ease;
	}

	:deep(.modal-container),
	&__content {
		overflow: visible !important;
		cursor: pointer;
	}

	&--split {
		.viewer__file--active {
			width: 50%;
		}
	}

	:deep(.modal-wrapper) {
		.modal-container {
			// Ensure some space at the bottom
			top: var(--header-height);
			bottom: var(--header-height);
			height: auto;
			// let the mime components manage their own background-color
			background-color: transparent;
			box-shadow: none;
		}
	}

	&__content {
		width: 100%;
		height: 100%;
	}

	&__file-wrapper {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 100%;
		height: 100%;

		// display on page but make it invisible
		&--hidden {
			position: absolute;
			z-index: -1;
			left: -10000px;
		}
	}

	&__file {
		transition: height 100ms ease,
			width 100ms ease;
	}

	&.theme--dark:deep(.button-vue--vue-tertiary) {
		&:hover {
			background-color: rgba(255, 255, 255, .08) !important;
		}
		&:focus,
		&:focus-visible {
			background-color: rgba(255, 255, 255, .08) !important;
			outline: 2px solid var(--color-primary-element) !important;
		}
		&.action-item__menutoggle {
			background-color: transparent;
		}
	}

	&.theme--undefined.modal-mask {
		background-color: transparent !important;
	}

	&.theme--light {
		&.modal-mask {
			background-color: rgba(255, 255, 255, .92) !important;
		}
		:deep(.modal-header__name),
		:deep(.modal-header .icons-menu button svg) {
			color: #000 !important;
		}
	}

	&.theme--default {
		&.modal-mask {
			background-color: var(--color-main-background) !important;
		}
		:deep(.modal-header__name),
		:deep(.modal-header .icons-menu) {
			color: var(--color-main-text) !important;

			button svg, a {
				color: var(--color-main-text) !important;
			}
		}
	}

	&.image--fullscreen {
		// Special display mode for images in full screen
		:deep(.modal-header) {
			.modal-header__name {
				// Hide file name
				opacity: 0;
			}
			.icons-menu {
				// Semi-transparent background for icons only
				background-color: rgba(0, 0, 0, 0.2);
			}
		}
		:deep(.modal-wrapper) {
			.modal-container {
				// Use entire screen height
				top: 0;
				bottom: 0;
				height: 100%;
			}
		}
	}
}

</style>

<style lang="scss">
.component-fade-enter-active,
.component-fade-leave-active {
	transition: opacity .3s ease;
}

.component-fade-enter, .component-fade-leave-to {
	opacity: 0;
}

// force white icon on single buttons
#viewer.modal-mask--dark .action-item--single.icon-menu-sidebar {
	background-image: url('../assets/menu-sidebar-white.svg');
}

#viewer.modal-mask--dark .action-item--single.icon-download {
	background-image: var(--icon-download-fff);
}

// put autocomplete over full sidebar
// TODO: remove when new sharing sidebar (18)
// is the min-version of viewer
.ui-autocomplete {
	z-index: 2050 !important;
}

</style>
