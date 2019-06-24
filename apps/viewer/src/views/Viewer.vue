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

import Error from 'Components/Error'
import PreviewUrl from 'Mixins/PreviewUrl'
import File from 'Models/file'
import FileList from 'Services/FileList'
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
		mimesAliases: {},
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
		handlers: function() {
			// make sure the viewer is done registering handlers
			// so we only register handlers added AFTER the init
			// of the viewer
			if (this.isLoaded) {
				this.registerHandler(this.handlers[this.handlers.length - 1])
			}
		}
	},

	beforeMount() {
		// register on load
		document.addEventListener('DOMContentLoaded', event => {
			this.handlers.forEach(handler => {
				this.registerHandler(handler)
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
			// prevent scrolling while opened
			document.body.style.overflow = 'hidden'

			const relativePath = `${fileInfo.dir !== '/' ? fileInfo.dir : ''}/${fileName}`

			let mime = fileInfo.$file.data('mime')

			const group = this.mimeGroups[mime]
			const mimes = this.mimeGroups[group]
				? this.mimeGroups[group]
				: [mime]

			// if no group, only fetch the file info
			const infoPath = group
				? fileInfo.dir
				: relativePath

			// retrieve, sort and store file List
			const fileList = await FileList(OC.getCurrentUser().uid, infoPath, mimes)
			this.fileList = fileList.sort(OCA.Files.App.fileList._sortComparator)

			// store current position
			this.currentIndex = this.fileList.findIndex(file => file.name === fileName)

			// get saved fileInfo
			fileInfo = this.fileList[this.currentIndex]

			// override mimetype if existing alias
			mime = this.getAliasIfAny(mime)

			if (this.components[mime]) {
				this.currentFile = new File(fileInfo, mime, this.components[mime])
				this.updatePreviousNext()
			} else {
				console.error(`The following file could not be displayed`, fileName, fileInfo)
				this.currentFile.failed = true
			}
		},

		/**
		 * Open the view and display the file from the file list
		 *
		 * @param {Object} fileInfo the opened file info
		 */
		openFileFromList(fileInfo) {
			// override mimetype if existing alias
			const mime = this.getAliasIfAny(fileInfo.mimetype)
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
				const mime = this.getAliasIfAny(prev.mimetype)
				if (this.components[mime]) {
					this.previousFile = new File(prev, mime, this.components[mime])
				}
			} else {
				// RESET
				this.previousFile = null
			}

			if (next) {
				const mime = this.getAliasIfAny(next.mimetype)
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

			// checking if no valid mimes data and no mimes Aliases
			if (!(handler.mimes && Array.isArray(handler.mimes)) && !handler.mimesAliases) {
				console.error(`The following handler doesn't have a valid mime array`, handler)
				return
			}

			if (handler.mimesAliases && typeof handler.mimesAliases !== 'object') {
				console.error(`The following handler doesn't have a valid mimesAliases object`, handler)
				return

			}

			// checking valid handler component data AND no alias (we can register alias without component)
			if ((!handler.component || typeof handler.component !== 'object') && !handler.mimesAliases) {
				console.error(`The following handler doesn't have a valid component`, handler)
				return
			}

			const register = ({ mime, handler }) => {
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

				// set the handler as registered
				this.registeredHandlers.push(handler.id)
			}

			// parsing mimes registration
			if (handler.mimes) {
				handler.mimes.forEach(mime => {
					// checking valid mime
					if (this.components[mime]) {
						console.error(`The following mime is already registered`, mime, handler)
						return
					}

					register({ mime, handler })

					// register mime's component
					this.components[mime] = handler.component
					Vue.component(handler.component.name, handler.component)
				})
			}

			// parsing aliases registration
			if (handler.mimesAliases) {
				Object.keys(handler.mimesAliases).forEach(mime => {
					// checking valid mime
					if (this.components[mime]) {
						console.error(`The following mime is already registered`, mime, handler)
						return
					}

					register({ mime, handler })

					this.mimesAliases[mime] = handler.mimesAliases[mime]
				})
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
		},

		/**
		 * Return the alias if exists
		 *
		 * @param {String} mime the mime type
		 * @returns {String} the mime type or the mime alias
		 */
		getAliasIfAny(mime) {
			return this.mimesAliases[mime]
				? this.mimesAliases[mime]
				: mime
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
	z-index: 15000;
	.thumbnailContainer {
		display: none;
	}
}
</style>
