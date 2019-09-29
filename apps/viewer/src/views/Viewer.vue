<!--
 - @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -
 -->

<template>
	<Modal
		v-if="currentFile.modal"
		id="viewer-content"
		:class="{'icon-loading': !currentFile.loaded && !currentFile.failed}"
		:view="currentFile.modal"
		:enable-slideshow="hasPrevious || hasNext"
		:spread-navigation="true"
		:has-previous="hasPrevious"
		:has-next="hasNext"
		:title="currentFile.name"
		:enable-swipe="canSwipe"
		:size="isMobile || isFullscreen ? 'full' : 'large'"
		:style="{width: shownSidebar ? `calc(100% - ${sidebarWidth}px)` : null}"
		@close="close"
		@previous="previous"
		@next="next">
		<!-- ACTIONS -->
		<template #actions>
			<ActionButton
				icon="icon-menu-sidebar-white-forced"
				@click="showSidebar">
				{{ t('viewer', 'Open sidebar') }}
			</ActionButton>
		</template>

		<!-- PREVIOUS -->
		<component
			:is="previousFile.modal"
			v-if="previousFile && !previousFile.failed"
			:key="getPreviewIfAny(previousFile)"
			ref="previous-content"
			:dav-path="previousFile.path"
			:file-id="previousFile.id"
			:file-list="fileList"
			:file-name="previousFile.name"
			:mime="previousFile.mime"
			:path="getPreviewIfAny(previousFile)"
			class="hidden-visually file-view"
			@error="previousFailed" />
		<Error
			v-else-if="previousFile"
			class="hidden-visually"
			:name="previousFile.name" />

		<!-- CURRENT -->
		<component
			:is="currentFile.modal"
			v-if="!currentFile.failed"
			:key="getPreviewIfAny(currentFile)"
			ref="content"
			:active="true"
			:can-swipe.sync="canSwipe"
			:dav-path="currentFile.path"
			:file-id="currentFile.id"
			:file-list="fileList"
			:file-name="currentFile.name"
			:is-full-screen="isFullscreen"
			:loaded.sync="currentFile.loaded"
			:mime="currentFile.mime"
			:path="getPreviewIfAny(currentFile)"
			:sidebar-shown="shownSidebar"
			class="file-view active"
			@error="currentFailed" />
		<Error
			v-else
			:name="currentFile.name" />

		<!-- NEXT -->
		<component
			:is="nextFile.modal"
			v-if="nextFile && !nextFile.failed"
			:key="getPreviewIfAny(nextFile)"
			ref="next-content"
			:dav-path="nextFile.path"
			:file-id="nextFile.id"
			:file-list="fileList"
			:file-name="nextFile.name"
			:mime="nextFile.mime"
			:path="getPreviewIfAny(nextFile)"
			class="hidden-visually file-view"
			@error="nextFailed" />
		<Error
			v-else-if="nextFile"
			class="hidden-visually" />
	</Modal>
</template>

<script>
import Vue from 'vue'

import isMobile from 'nextcloud-vue/dist/Mixins/isMobile'
import isFullscreen from 'nextcloud-vue/dist/Mixins/isFullscreen'
import { generateRemoteUrl } from 'nextcloud-server/dist/router'

import Error from '../components/Error'
import PreviewUrl from '../mixins/PreviewUrl'
import File from '../models/file'
import FileList from '../services/FileList'
import Modal from 'nextcloud-vue/dist/Components/Modal'
import ActionButton from 'nextcloud-vue/dist/Components/ActionButton'

export default {
	name: 'Viewer',

	components: {
		ActionButton,
		Modal,
		Error
	},

	mixins: [isMobile, isFullscreen, PreviewUrl],

	data: () => ({
		handlers: OCA.Viewer.availableHandlers,

		components: {},
		mimeGroups: {},
		registeredHandlers: [],

		currentIndex: 0,
		previousFile: {},
		currentFile: {},
		nextFile: {},

		fileList: [],

		isLoaded: false,

		shownSidebar: false,
		sidebarWidth: 0,

		canSwipe: true,
		failed: false,

		root: generateRemoteUrl(`dav/files/${OC.getCurrentUser().uid}`)
	}),

	computed: {
		hasPrevious() {
			return this.fileList.length > 1
		},
		hasNext() {
			return this.fileList.length > 1
		}
	},

	watch: {
		// make sure any late external app can register handlers
		// should not happens if external apps do not wait for
		// the DOMContentLoaded event!
		handlers: function() {
			// make sure the viewer is done registering handlers
			// so we only register handlers added AFTER the init
			// of the viewer
			if (this.isLoaded) {
				console.error('Please do NOT wait for the DOMContentLoaded before registering your viewer handler')
				const handler = this.handlers[this.handlers.length - 1]
				// register all primary components mimes
				this.registerHandler(handler)
				// then register aliases. We need to have the components
				// first so we can bind the alias to them.
				this.registerHandlerAlias(handler)
			}
		}
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
		})

		window.addEventListener('resize', this.onResize)
	},

	beforeDestroy() {
		window.removeEventListener('resize', this.onResize)
	},

	methods: {
		/**
		 * Open the view and display the clicked file
		 *
		 * @param {string} fileName the opened file name
		 * @param {Object} fileInfo the opened file info
		 */
		async openFile(fileName, fileInfo) {
			// do not open the same file again
			if (fileName === this.currentFile.name) {
				return
			}

			// prevent scrolling while opened
			document.body.style.overflow = 'hidden'

			// swap title with original one
			const title = document.getElementsByTagName('head')[0].getElementsByTagName('title')[0]
			if (title && !title.dataset.old) {
				title.dataset.old = document.title
				document.title = `${fileName} - ${OC.theme.title}`
			}

			// retrieve, sort and store file List
			let fileList = await FileList(OC.getCurrentUser().uid, this.encodeFilePath(fileInfo.dir, fileName))

			let mime = fileList.find(file => file.name === fileName).mimetype

			// check if part of a group, if so retrieve full files list
			const group = this.mimeGroups[mime]
			if (group) {
				const mimes = this.mimeGroups[group]
					? this.mimeGroups[group]
					: [mime]

				// retrieve folder list
				fileList = await FileList(OC.getCurrentUser().uid, this.encodeFilePath(fileInfo.dir, ''))

				// filter out the unwanted mimes
				fileList = fileList.filter(file => file.mimetype && mimes.indexOf(file.mimetype) !== -1)

				// sort like the files list
				this.fileList = fileList.sort(OCA.Files.App.fileList._sortComparator)

				// store current position
				this.currentIndex = this.fileList.findIndex(file => file.name === fileName)
			} else {
				this.currentIndex = 0
				this.fileList = fileList
			}

			// get saved fileInfo
			fileInfo = this.fileList[this.currentIndex]

			// override mimetype if existing alias
			if (!this.components[mime]) {
				mime = mime.split('/')[0]
			}

			if (this.components[mime]) {
				this.currentFile = new File(fileInfo, mime, this.components[mime])
				this.updatePreviousNext()
			} else {
				console.error(`The following file could not be displayed`, fileName, fileInfo)
				this.currentFile.failed = true
			}
		},

		/**
		 * Get an url encoded path
		 *
		 * @param {string} dir path of the files directory
		 * @param {string} fileName unencoded file name
		 * @returns {string} url encoded file path
		 */
		encodeFilePath(dir, fileName) {
			const pathSections = (dir !== '/' ? dir : '').split('/')
			pathSections.push(fileName)
			let relativePath = ''
			pathSections.forEach((section) => {
				if (section !== '') {
					relativePath += '/' + encodeURIComponent(section)
				}
			})
			return relativePath
		},

		/**
		 * Open the view and display the file from the file list
		 *
		 * @param {Object} fileInfo the opened file info
		 */
		openFileFromList(fileInfo) {
			// override mimetype if existing alias
			const mime = fileInfo.mimetype
			this.currentFile = new File(fileInfo, mime, this.components[mime])
			this.updatePreviousNext()
		},

		/**
		 * Update the previous and next file components
		 */
		updatePreviousNext() {
			const prev = this.fileList[this.currentIndex - 1]
			const next = this.fileList[this.currentIndex + 1]

			if (prev) {
				const mime = prev.mimetype
				if (this.components[mime]) {
					this.previousFile = new File(prev, mime, this.components[mime])
				}
			} else {
				// RESET
				this.previousFile = null
			}

			if (next) {
				const mime = next.mimetype
				if (this.components[mime]) {
					this.nextFile = new File(next, mime, this.components[mime])
				}
			} else {
				// RESET
				this.nextFile = null
			}

		},

		/**
		 * Registering possible new handers
		 *
		 * @param {Object} handler the handler to register
		 * @param {String} handler.id unique handler identifier
		 * @param {Array} handler.mimes list of valid mimes compatible with the handler
		 * @param {Object} handler.component a vuejs component to render when a file matching the mime list is opened
		 * @param {String} [handler.group] a group name to be associated with for the slideshow
		 */
		registerHandler(handler) {
			// checking if handler is not already registered
			if (handler.id && this.registeredHandlers.indexOf(handler.id) > -1) {
				console.error(`The following handler is already registered`, handler)
				return
			}

			// checking valid handler id
			if (!handler.id || handler.id.trim() === '' || typeof handler.id !== 'string') {
				console.error(`The following handler doesn't have a valid id`, handler)
				return
			}

			// checking if no valid mimes data but alias. If so, skipping...
			if (!(handler.mimes && Array.isArray(handler.mimes)) && handler.mimesAliases) {
				return
			}

			// Nothing available to process! Failure
			if (!(handler.mimes && Array.isArray(handler.mimes)) && !handler.mimesAliases) {
				console.error(`The following handler doesn't have a valid mime array`, handler)
				return
			}

			// checking valid handler component data
			if ((!handler.component || typeof handler.component !== 'object')) {
				console.error(`The following handler doesn't have a valid component`, handler)
				return
			}

			// parsing mimes registration
			if (handler.mimes) {
				handler.mimes.forEach(mime => {
					// checking valid mime
					if (this.components[mime]) {
						console.error(`The following mime is already registered`, mime, handler)
						return
					}

					// register file action and groups
					this.registerAction({ mime, group: handler.group })

					// register mime's component
					this.components[mime] = handler.component
					Vue.component(handler.component.name, handler.component)

					// set the handler as registered
					this.registeredHandlers.push(handler.id)
				})
			}
		},

		registerHandlerAlias(handler) {
			// parsing aliases registration
			if (handler.mimesAliases) {
				Object.keys(handler.mimesAliases).forEach(mime => {

					if (handler.mimesAliases && typeof handler.mimesAliases !== 'object') {
						console.error(`The following handler doesn't have a valid mimesAliases object`, handler)
						return

					}

					// this is the targeted alias
					const alias = handler.mimesAliases[mime]

					// checking valid mime
					if (this.components[mime]) {
						console.error(`The following mime is already registered`, mime, handler)
						return
					}
					if (!this.components[alias]) {
						console.error(`The requested alias does not exists`, alias, mime, handler)
						return
					}

					// register file action and groups if the request alias had a group
					this.registerAction({ mime, group: this.mimeGroups[alias] })

					// register mime's component
					this.components[mime] = this.components[alias]

					// set the handler as registered
					this.registeredHandlers.push(handler.id)
				})
			}
		},

		registerAction({ mime, group }) {
			// unregistered handler, let's go!
			OCA.Files.fileActions.registerAction({
				name: 'view',
				displayName: t('viewer', 'View'),
				mime: mime,
				permissions: OC.PERMISSION_READ,
				actionHandler: this.openFile
			})
			OCA.Files.fileActions.setDefault(mime, 'view')

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
			this.currentFile = {}
			this.currentModal = null
			this.fileList = []
			this.hideAppsSidebar()

			// restore default
			document.body.style.overflow = null

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
			this.currentIndex--
			if (this.currentIndex < 0) {
				this.currentIndex = this.fileList.length - 1
			}

			this.openFileFromList(this.fileList[this.currentIndex])
		},

		/**
		 * Open next available file
		 */
		next() {
			this.currentIndex++
			if (this.currentIndex > this.fileList.length - 1) {
				this.currentIndex = 0
			}

			this.openFileFromList(this.fileList[this.currentIndex])
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

		showSidebar() {
			// Open the sidebar sharing tab
			OCA.Files.App.fileList.showDetailsView(this.currentFile.name)
			this.showAppsSidebar()
		},

		showAppsSidebar() {
			this.shownSidebar = true
			const sidebar = document.getElementById('app-sidebar')
			if (sidebar) {
				sidebar.classList.add('app-sidebar--full')
			}

			// overriding closing function
			const origHideAppsSidebar = OC.Apps.hideAppSidebar
			OC.Apps.hideAppSidebar = ($el) => {
				this.hideAppsSidebar()
				origHideAppsSidebar($el)
			}

			this.sidebarWidth = sidebar.offsetWidth
		},

		hideAppsSidebar() {
			this.shownSidebar = false
			const sidebar = document.getElementById('app-sidebar')
			if (sidebar) {
				sidebar.classList.remove('app-sidebar--full')
			}
		},

		onResize(event) {
			// update sidebar width
			const sidebar = document.getElementById('app-sidebar')
			if (sidebar) {
				this.sidebarWidth = sidebar.offsetWidth
			}
		}
	}
}
</script>

<style lang="scss">
#viewer-content.modal-mask {
	transition: width ease 100ms;
	.modal-container {
		display: flex !important;
		width: auto !important;
		border-radius: 0 !important;
		// let the mime components manage their own background-color
		background-color: transparent;
		justify-content: center;
		align-items: center;
	}
}

.component-fade-enter-active, .component-fade-leave-active {
	transition: opacity .3s ease;
}

.component-fade-enter, .component-fade-leave-to {
	opacity: 0;
}

// force white icon
.icon-menu-sidebar-white-forced {
	background-image: url('~Assets/menu-sidebar-white.svg');
}

.file-view {
	transition: height 100ms ease,
		width 100ms ease;
}

#app-sidebar.app-sidebar--full {
	position: fixed;
	top: 0;
	height: 100%;
	z-index: 2025;
	.thumbnailContainer {
		display: none;
	}
}

// put autocomplete over full sidebar
// TODO: remove when new sharing sidebar (18)
// is the min-version of viewer
.ui-autocomplete {
	z-index: 2050 !important;
}
</style>
