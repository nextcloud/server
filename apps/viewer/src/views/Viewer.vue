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
	<modal
		v-if="currentFile.modal"
		id="viewer-content"
		:class="{'icon-loading': loading}"
		:view="currentFile.modal"
		:actions="actions"
		:enable-slideshow="true"
		:has-previous="hasPrevious"
		:has-next="hasNext"
		:title="currentFileName"
		:disable-swipe="disableSwipe"
		:size="isMobile ? 'full' : 'large'"
		:style="{width: showSidebar ? `calc(100% - ${sidebarWidth}px)` : null}"
		@close="close"
		@previous="previous"
		@next="next">
		<!-- PREVIOUS -->
		<component
			:is="previousFile.modal"
			v-if="!previousFile.failed"
			ref="previous-content"
			:key="getPath(previousFile)"
			:mime="previousFile.mime"
			:path="getPath(previousFile)"
			class="hidden-visually file-view"
			@error="previousFailed" />
		<error
			v-else
			class="hidden-visually" />

		<!-- CURRENT -->
		<component
			:is="currentFile.modal"
			v-if="!currentFile.failed"
			ref="content"
			:key="getPath(currentFile)"
			:mime="currentFile.mime"
			:path="getPath(currentFile)"
			:active="true"
			class="file-view"
			@loaded="doneLoading"
			@error="currentFailed" />
		<error
			v-else
			:name="currentFileName" />

		<!-- NEXT -->
		<component
			:is="nextFile.modal"
			v-if="!nextFile.failed"
			ref="next-content"
			:key="getPath(nextFile)"
			:mime="nextFile.mime"
			:path="getPath(nextFile)"
			class="hidden-visually file-view"
			@error="nextFailed" />
		<error
			v-else
			class="hidden-visually" />
	</modal>
</template>

<script>
import Vue from 'vue'

import Modal from 'nextcloud-vue/dist/Components/Modal'
import { generateRemoteUrl, generateUrl } from 'nextcloud-server/dist/router'

import Error from 'Components/Error'
import FileList from 'Services/FileList'

export default {
	name: 'Viewer',

	components: {
		Modal,
		Error
	},

	data: () => ({
		handlers: OCA.Viewer.availableHandlers,

		components: {},
		mimeGroups: {},
		mimesAliases: {},
		registeredHandlers: [],

		currentIndex: 0,
		previousFile: {},
		currentFile: {},
		nextFile: {},

		fileList: [],

		isMobile: window.outerWidth < 768,
		showSidebar: false,
		sidebarWidth: 0,

		disableSwipe: false,
		failed: false,
		loading: true,

		root: generateRemoteUrl(`dav/files/${OC.getCurrentUser().uid}`)
	}),

	computed: {
		hasPrevious() {
			return this.fileList.length > 1
		},
		hasNext() {
			return this.fileList.length > 1
		},
		currentFileName() {
			if (this.currentFile) {
				return this.currentFile.name
			}
			return ''
		},
		actions() {
			return OCA.Sharing
				? [
					{
						text: t('viewer', 'Share'),
						icon: 'icon-share-white-forced',
						action: this.showSharingSidebar
					}
				]
				: []
		}
	},

	watch: {
		// make sure any late external app can register handlers
		handlers: function() {
			this.registerHandler(this.handlers[this.handlers.length - 1])
		}
	},

	beforeMount() {
		// register on load
		document.addEventListener('DOMContentLoaded', event => {
			this.handlers.forEach(handler => {
				this.registerHandler(handler)
			})
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
			this.loading = true
			this.failed = false

			// prevent scrolling while opened
			document.body.style.overflow = 'hidden'

			const relativePath = `${fileInfo.dir !== '/' ? fileInfo.dir : ''}/${fileName}`
			const path = `${this.root}${relativePath}`

			const mime = fileInfo.$file.data('mime')

			const group = this.mimeGroups[mime]
			const mimes = this.mimeGroups[group]

			// retrieve, sort and store file List
			const fileList = await FileList(OC.getCurrentUser().uid, fileInfo.dir, mimes)
			this.fileList = fileList.sort(OCA.Files.App.fileList._sortComparator)

			// store current position
			this.currentIndex = this.fileList.findIndex(file => file.name === fileName)

			fileInfo = this.fileList[this.currentIndex]
			if (this.components[mime]) {
				this.currentFile = {
					relativePath,
					path,
					mime,
					hasPreview: fileInfo.hasPreview,
					id: fileInfo.id,
					name: fileInfo.name,
					modal: this.components[mime]
				}
			}

			this.updatePreviousNext()
		},

		/**
		 * Open the view and display the file from the file list
		 *
		 * @param {Object} fileInfo the opened file info
		 */
		openFileFromList(fileInfo) {
			const path = fileInfo.href
			const id = fileInfo.id
			const name = fileInfo.name
			const hasPreview = fileInfo.hasPreview
			const mime = fileInfo.mimetype
			const modal = this.components[mime]
			if (modal) {
				this.currentFile = {
					path,
					mime,
					id,
					name,
					hasPreview,
					modal,
					failed: false
				}
			}
			this.updatePreviousNext()
		},

		/**
		 * Update the previous and next file components
		 */
		updatePreviousNext() {
			const prev = this.fileList[this.currentIndex - 1]
			const next = this.fileList[this.currentIndex + 1]

			if (prev) {
				const path = prev.href
				const id = prev.id
				const name = prev.name
				const hasPreview = prev.hasPreview
				const mime = prev.mimetype
				const modal = this.components[mime]

				if (modal) {
					this.previousFile = {
						path,
						mime,
						id,
						name,
						hasPreview,
						modal,
						failed: false
					}
				}
			// RESET
			} else {
				this.previousFile = {}
			}

			if (next) {
				const path = next.href
				const id = next.id
				const name = next.name
				const hasPreview = next.hasPreview
				const mime = next.mimetype
				const modal = this.components[mime]

				if (modal) {
					this.nextFile = {
						path,
						mime,
						id,
						name,
						hasPreview,
						modal,
						failed: false
					}
				}
			// RESET
			} else {
				this.nextFile = {}
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
				console.error(`The following handler doesn't have proper id`, handler)
				return
			}

			// checking valid handler mime data
			if (!handler.mimes || !Array.isArray(handler.mimes)) {
				console.error(`The following handler doesn't have proper mime data`, handler)
				return
			}

			// checking valid handler component data
			if (!handler.component || typeof handler.component !== 'object') {
				console.error(`The following handler doesn't have proper component`, handler)
				return
			}

			handler.mimes.forEach(mime => {
				// checking valid mime
				if (this.components[mime]) {
					console.error(`The following mime is already registered`, mime, handler)
					return
				}

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
				if (handler.group) {
					this.mimeGroups[mime] = handler.group
					// init if undefined
					if (!this.mimeGroups[handler.group]) {
						this.mimeGroups[handler.group] = []
					}
					this.mimeGroups[handler.group].push(mime)
				}

				if (handler.mimesAliases) {
					Object.keys(handler.mimesAliases).forEach(mime => {
						this.mimesAliases[mime] = handler.mimesAliases[mime]
					})
				}

				// set the handler as registered
				this.registeredHandlers.push(handler.id)

				// register mime's component
				this.components[mime] = handler.component
				Vue.component(handler.component.name, handler.component)
			})
		},

		getPath(fileInfo) {
			if (fileInfo.hasPreview) {
				return generateUrl(`/core/preview?fileId=${fileInfo.id}&x=${window.outerWidth}&y=${window.outerHeight}&a=true`)
			}
			return fileInfo.path
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
		},

		/**
		 * Open previous available file
		 */
		previous() {
			this.loading = true
			this.failed = false

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
			this.loading = true
			this.failed = false

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
			this.loading = false
		},

		nextFailed() {
			this.nextFile.failed = true
		},

		/**
		 * Component finished loading the data
		 */
		doneLoading() {
			this.loading = false
		},

		/**
		 * Show the sharing sidebar
		 */

		showSharingSidebar() {
			// Open the sidebar sharing tab
			OCA.Files.App.fileList.showDetailsView(this.currentFileName, 'shareTabView')
			this.showAppsSidebar()
		},

		showAppsSidebar() {
			this.showSidebar = true
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
			this.showSidebar = false
			const sidebar = document.getElementById('app-sidebar')
			if (sidebar) {
				sidebar.classList.remove('app-sidebar--full')
			}
		},

		onResize(event) {
			// Update mobile mode
			this.isMobile = window.outerWidth < 768
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
		background-color: white;
		justify-content: center;
		align-items: center;
	}

	// dark bg while loading to avoid flashing white screen
	&.icon-loading .modal-container {
		background-color: black;
	}
}

.component-fade-enter-active, .component-fade-leave-active {
	transition: opacity .3s ease;
}

.component-fade-enter, .component-fade-leave-to {
	opacity: 0;
}

// force white icon
.icon-share-white-forced {
	background-image: url('~Assets/share-white.svg');
}

.file-view {
	transition: height 100ms ease,
		width 100ms ease;
}

#app-sidebar.app-sidebar--full {
	position: absolute;
	top: 0;
	height: 100%;
	z-index: 15000;
	.thumbnailContainer {
		display: none;
	}
}
</style>
