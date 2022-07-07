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
	<Modal v-if="initiated || currentFile.modal"
		id="viewer"
		size="full"
		:class="{'icon-loading': !currentFile.loaded && !currentFile.failed,
			'theme--undefined': theme === null, 'theme--dark': theme === 'dark', 'theme--light': theme === 'light', 'theme--default': theme === 'default'}"
		:clear-view-delay="-1 /* disable fade-out because of accessibility reasons */"
		:dark="true"
		:enable-slideshow="hasPrevious || hasNext"
		:enable-swipe="canSwipe"
		:has-next="hasNext && (canLoop ? true : !isEndOfList)"
		:has-previous="hasPrevious && (canLoop ? true : !isStartOfList)"
		:spread-navigation="true"
		:style="{width: isSidebarShown ? `calc(100% - ${sidebarWidth}px)` : null}"
		:title="currentFile.basename"
		:view="currentFile.modal"
		class="viewer"
		@close="close"
		@previous="previous"
		@next="next">
		<!-- ACTIONS -->
		<template #actions>
			<ActionButton v-if="Sidebar && !isSidebarShown"
				:close-after-click="true"
				icon="icon-menu-sidebar"
				@click="showSidebar">
				{{ t('viewer', 'Open sidebar') }}
			</ActionButton>
			<ActionLink v-if="canDownload"
				:download="currentFile.basename"
				:close-after-click="true"
				:href="currentFile.davPath">
				<template #icon>
					<Download :size="24" title="" decorative />
				</template>
				{{ t('viewer', 'Download') }}
			</ActionLink>
			<ActionButton v-if="canDelete"
				:close-after-click="true"
				icon="icon-delete"
				@click="onDelete">
				{{ t('viewer', 'Delete') }}
			</ActionButton>
		</template>

		<div class="viewer__content" @click.self.exact="close">
			<!-- PREVIOUS -->
			<component :is="previousFile.modal"
				v-if="previousFile && !previousFile.failed"
				:key="previousFile.fileid"
				ref="previous-content"
				v-bind="previousFile"
				:file-list="fileList"
				class="viewer__file--hidden viewer__file"
				@error="previousFailed" />
			<Error v-else-if="previousFile"
				class="hidden-visually"
				:name="previousFile.basename" />

			<!-- CURRENT -->
			<component :is="currentFile.modal"
				v-if="!currentFile.failed"
				:key="currentFile.fileid"
				ref="content"
				:active="true"
				:can-swipe.sync="canSwipe"
				v-bind="currentFile"
				:file-list="fileList"
				:is-full-screen="isFullscreen"
				:loaded.sync="currentFile.loaded"
				:is-sidebar-shown="isSidebarShown"
				class="viewer__file viewer__file--active"
				@error="currentFailed" />
			<Error v-else
				:name="currentFile.basename" />

			<!-- NEXT -->
			<component :is="nextFile.modal"
				v-if="nextFile && !nextFile.failed"
				:key="nextFile.fileid"
				ref="next-content"
				v-bind="nextFile"
				:file-list="fileList"
				class="viewer__file--hidden viewer__file"
				@error="nextFailed" />
			<Error v-else-if="nextFile"
				class="hidden-visually"
				:name="nextFile.basename" />
		</div>
	</Modal>
</template>

<script>
import Vue from 'vue'

import axios from '@nextcloud/axios'
import '@nextcloud/dialogs/styles/toast.scss'
import { showError } from '@nextcloud/dialogs'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'

import ActionButton from '@nextcloud/vue/dist/Components/ActionButton.js'
import ActionLink from '@nextcloud/vue/dist/Components/ActionLink.js'
import isFullscreen from '@nextcloud/vue/dist/Mixins/isFullscreen.js'
import Modal from '@nextcloud/vue/dist/Components/Modal.js'

import { extractFilePaths, sortCompare } from '../utils/fileUtils.js'
import { getRootPath } from '../utils/davUtils.js'
import canDownload from '../utils/canDownload.js'
import cancelableRequest from '../utils/CancelableRequest.js'
import Error from '../components/Error.vue'
import File from '../models/file.js'
import filesActionHandler from '../services/FilesActionHandler.js'
import getFileInfo from '../services/FileInfo.js'
import getFileList from '../services/FileList.js'
import Mime from '../mixins/Mime.js'
import logger from '../services/logger.js'

import Download from 'vue-material-design-icons/Download.vue'

export default {
	name: 'Viewer',

	components: {
		ActionButton,
		ActionLink,
		Modal,
		Error,
		Download,
	},

	mixins: [isFullscreen],

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
			nextFile: {},
			fileList: [],

			// States
			isLoaded: false,
			initiated: false,

			// cancellable requests
			cancelRequestFile: () => {},
			cancelRequestFolder: () => {},

			// Flags
			sidebarWidth: 0,
			isSidebarShown: false,
			canSwipe: true,
			isStandalone: !(OCA && OCA.Files && 'fileActions' in OCA.Files),
			theme: null,
			root: getRootPath(),
		}
	},

	computed: {
		hasPrevious() {
			return this.fileList.length > 1
		},
		hasNext() {
			return this.fileList.length > 1
		},
		file() {
			return this.Viewer.file
		},
		files() {
			return this.Viewer.files
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

		/**
		 * Is the current user allowed to delete the file?
		 *
		 * @return {boolean}
		 */
		canDelete() {
			return this.currentFile?.permissions?.includes('D')
		},

		/**
		 * Is the current user allowed to download the file in public mode?
		 *
		 * @return {boolean}
		 */
		canDownload() {
			return canDownload()
		},
	},

	watch: {
		file(path) {
			// we got a valid path! Load file...
			if (path.trim() !== '') {
				logger.info('Opening viewer for file ', { path })
				this.openFile(path)
			} else {
				// path is empty, we're closing!
				this.cleanup()
			}
		},

		files(fileList) {
			// the files list changed, let's update the current opened index
			const currentIndex = fileList.findIndex(file => file.basename === this.currentFile.basename)
			if (currentIndex > -1) {
				this.currentIndex = currentIndex
				logger.debug('The files list changed, new current file index is ' + currentIndex)
			}
			// finally replace the fileList
			this.fileList = fileList
		},

		// user reached the end of list
		async isEndOfList(isEndOfList) {
			if (!isEndOfList) {
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
		// register on load
		document.addEventListener('DOMContentLoaded', event => {
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

		if (this.isStandalone) {
			logger.info('No OCA.Files app found, viewer is now in standalone mode')
		}
	},

	mounted() {
		// React to Files' Sidebar events.
		subscribe('files:sidebar:opened', this.handleAppSidebarOpen)
		subscribe('files:sidebar:closed', this.handleAppSidebarClose)
	},

	beforeDestroy() {
		window.removeEventListener('resize', this.onResize)
	},

	destroyed() {
		// Unsubscribe to Files' Sidebar events.
		unsubscribe('files:sidebar:opened', this.handleAppSidebarOpen)
		unsubscribe('files:sidebar:closed', this.handleAppSidebarClose)
	},

	methods: {
		/**
		 * Open the view and display the clicked file
		 *
		 * @param {string} path the file path to open
		 */
		async openFile(path) {
			// cancel any previous requests
			this.cancelRequestFile()
			this.cancelRequestFolder()

			if (OCA?.Files?.Sidebar) {
				OCA.Files.Sidebar.setFullScreenMode(true)
			}

			// do not open the same file again
			if (path === this.currentFile.path) {
				return
			}

			// initial loading start
			this.initiated = true
			const { request: fileRequest, cancel: cancelRequestFile } = cancelableRequest(getFileInfo)
			const { request: folderRequest, cancel: cancelRequestFolder } = cancelableRequest(getFileList)
			this.cancelRequestFile = cancelRequestFile
			this.cancelRequestFolder = cancelRequestFolder

			// extcrat needed info from path
			const [dirPath, fileName] = extractFilePaths(path)

			// prevent scrolling while opened
			document.body.style.overflow = 'hidden'

			// swap title with original one
			const title = document.getElementsByTagName('head')[0].getElementsByTagName('title')[0]
			if (title && !title.dataset.old) {
				title.dataset.old = document.title
				this.updateTitle(fileName)
			}

			try {

				// retrieve and store the file info
				let fileInfo = await fileRequest(path)

				// get original mime
				let mime = fileInfo.mime

				this.theme = this.registeredHandlers[mime].theme ?? 'dark'

				// if we don't have a handler for this mime, abort
				if (!(mime in this.components)) {
					logger.error('The following file could not be displayed', { fileName, fileInfo })
					showError(t('viewer', 'There is no plugin available to display this file type'))
					this.close()
					return
				}

				// check if part of a group, if so retrieve full files list
				const group = this.mimeGroups[mime]
				if (this.files && this.files.length > 0) {
					logger.debug('A files list have been provided. No folder content will be fetched.')
					// we won't sort files here, let's use the order the array has
					this.fileList = this.files

					// store current position
					this.currentIndex = this.fileList.findIndex(file => file.basename === fileName)
				} else if (group) {
					const mimes = this.mimeGroups[group]
						? this.mimeGroups[group]
						: [mime]

					// retrieve folder list
					const fileList = await folderRequest(dirPath)

					// filter out the unwanted mimes
					const filteredFiles = fileList.filter(file => file.mime && mimes.indexOf(file.mime) !== -1)

					// sort like the files list
					// TODO: implement global sorting API
					// https://github.com/nextcloud/server/blob/a83b79c5f8ab20ed9b4d751167417a65fa3c42b8/apps/files/lib/Controller/ApiController.php#L247
					this.fileList = filteredFiles.sort((a, b) => sortCompare(a, b, 'basename'))

					// store current position
					this.currentIndex = this.fileList.findIndex(file => file.basename === fileName)
				} else {
					this.currentIndex = 0
					this.fileList = [fileInfo]
				}

				// get saved fileInfo
				fileInfo = this.fileList[this.currentIndex]

				// override mimetype if existing alias
				if (!this.components[mime]) {
					mime = mime.split('/')[0]
				}

				// show file
				this.currentFile = new File(fileInfo, mime, this.components[mime])
				this.updatePreviousNext()

				// if sidebar was opened before, let's update the file
				this.changeSidebar()
			} catch (error) {
				console.error(error)
			}
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
			document.title = `${fileName} - ${OCA.Theming?.name || oc_defaults.name}`
		},

		/**
		 * Registering possible new handers
		 *
		 * @param {object} handler the handler to register
		 * @param {string} handler.id unique handler identifier
		 * @param {Array} handler.mimes list of valid mimes compatible with the handler
		 * @param {object} handler.component a vuejs component to render when a file matching the mime list is opened
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
			if ((!handler.component || typeof handler.component !== 'object')) {
				logger.error('The following handler doesn\'t have a valid component', { handler })
				return
			}

			// force apply mixin
			handler.component.mixins = [...handler.component.mixins || [], Mime]

			// parsing mimes registration
			if (handler.mimes) {
				handler.mimes.forEach(mime => {
					// checking valid mime
					if (this.components[mime]) {
						logger.error('The following mime is already registered', { mime, handler })
						return
					}

					// register file action and groups
					this.registerAction({ mime, group: handler.group })

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

					// register file action and groups if the request alias had a group
					this.registerAction({ mime, group: this.mimeGroups[alias] })

					// register mime's component
					this.components[mime] = this.components[alias]

					// set the handler as registered
					this.registeredHandlers[mime] = handler
				})
			}
		},

		registerAction({ mime, group }) {
			if (!this.isStandalone) {
				// unregistered handler, let's go!
				OCA.Files.fileActions.registerAction({
					name: 'view',
					displayName: t('viewer', 'View'),
					mime,
					permissions: OC.PERMISSION_READ,
					actionHandler: filesActionHandler,
				})
				OCA.Files.fileActions.setDefault(mime, 'view')
			}

			// register groups
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
		},

		cleanup() {
			// reset all properties
			this.currentFile = {}
			this.currentModal = null
			this.fileList = []
			this.initiated = false
			this.theme = null

			// cancel requests
			this.cancelRequestFile()
			this.cancelRequestFolder()

			// restore default
			document.body.style.overflow = null

			// Callback before updating the title
			// If the callback creates a new entry in browser history
			// the title update will affect the new entry
			// rather then the previous one.
			this.onClose()

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
			this.onPrev(fileInfo, oldFileInfo)
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
			this.onNext(fileInfo, oldFileInfo)
			this.updateTitle(this.currentFile.basename)
		},

		/**
		 * Failures handlers
		 */
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

			if (OCA?.Files?.Sidebar) {
				await OCA.Files.Sidebar.open(this.currentFile.filename)
			}
		},

		handleAppSidebarOpen() {
			this.isSidebarShown = true
			const sidebar = document.querySelector('aside.app-sidebar')
			if (sidebar) {
				this.sidebarWidth = sidebar.offsetWidth
			}
		},

		handleAppSidebarClose() {
			this.isSidebarShown = false
		},

		onResize(event) {
			// update sidebar width
			const sidebar = document.querySelector('aside.app-sidebar')
			if (sidebar) {
				this.sidebarWidth = sidebar.offsetWidth
			}
		},

		onPrev(info, oldFileInfo) {
			this.Viewer.onPrev(info, oldFileInfo)
		},

		onNext(info, oldFileInfo) {
			this.Viewer.onNext(info, oldFileInfo)
		},

		onClose() {
			this.Viewer.onClose()
		},

		async onDelete() {
			try {
				const url = this.root + this.currentFile.filename
				await axios.delete(url)
				if (this.hasPrevious) {
					this.previous()
					const currentIndex = this.fileList.findIndex(file => file.basename === this.currentFile.basename)
					this.fileList.splice(currentIndex, 1)
				} else {
					this.close()
				}
			} catch (error) {
				console.error(error)
				showError(error)
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

	::v-deep .modal-container,
	&__content {
		overflow: visible !important;
		cursor: pointer;
	}

	::v-deep .modal-wrapper {
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
		// center views
		display: flex;
		align-items: center;
		justify-content: center;
		width: 100%;
		height: 100%;
	}

	&__file {
		transition: height 100ms ease,
			width 100ms ease;

		// display on page but make it invisible
		&--hidden {
			position: absolute;
			z-index: -1;
			left: -10000px;
		}
	}

	&.theme--undefined.modal-mask {
		background-color: transparent !important;
	}

	&.theme--light {
		&.modal-mask {
			background-color: rgba(255, 255, 255, .92) !important;
		}
		::v-deep .modal-title,
		::v-deep .modal-header .icons-menu button svg {
			color: #000 !important;
		}
	}

	&.theme--default {
		&.modal-mask {
			background-color: var(--color-main-background) !important;
		}
		::v-deep .modal-title,
		::v-deep .modal-header .icons-menu {
			color: var(--color-main-text) !important;

			button svg, a {
				color: var(--color-main-text) !important;
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
