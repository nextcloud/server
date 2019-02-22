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
		:class="{'icon-loading': loading}"
		:view="currentFile.modal"
		:has-previous="hasPrevious"
		:has-next="hasNext"
		@close="close"
		@previous="previous"
		@next="next">
		<component
			v-show="false"
			:is="previousFile.modal"
			ref="previous-content"
			:key="previousFile.path"
			:mime="previousFile.mime"
			:path="previousFile.path" />
		<component
			:is="currentFile.modal"
			ref="content"
			:key="currentFile.path"
			:mime="currentFile.mime"
			:path="currentFile.path"
			:active="true"
			@loaded="doneLoading" />
		<component
			v-show="false"
			:is="nextFile.modal"
			ref="next-content"
			:key="nextFile.path"
			:mime="nextFile.mime"
			:path="nextFile.path" />
	</modal>
</template>

<script>
import Mime from 'mime-types'
import Vue from 'vue'

import { Modal } from 'nextcloud-vue'

import FileList from 'Services/FileList'

export default {
	name: 'Viewer',

	components: {
		Modal
	},

	data: () => ({
		components: {},
		currentIndex: 0,
		previousFile: {},
		currentFile: {},
		nextFile: {},
		fileList: [],
		handlers: OCA.Viewer.availableHandlers,
		loading: true,
		mimeGroups: {},
		registeredHandlers: [],
		root: `/remote.php/dav/files/${OC.getCurrentUser().uid}`
	}),

	computed: {
		hasPrevious() {
			return this.currentIndex > 0
		},
		hasNext() {
			return this.currentIndex < this.fileList.length - 1
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
			const relativePath = `${fileInfo.dir !== '/' ? fileInfo.dir : ''}/${fileName}`
			const path = `${this.root}${relativePath}`
			const mime = Mime.lookup(path)

			const group = this.mimeGroups[mime]
			const mimes = this.mimeGroups[group]

			if (this.components[mime]) {
				this.currentFile = {
					relativePath,
					path,
					mime,
					modal: this.components[mime]
				}
				console.debug('Opened', path, mime)
			}

			// retrieve and store file List
			this.fileList = await FileList(OC.getCurrentUser().uid, fileInfo.dir, mimes)

			// store current position
			this.currentIndex = this.fileList.findIndex(file => decodeURI(file['d:href'][0]) === this.root + relativePath)

			this.updatePreviousNext()
		},

		/**
		 * Open the view and display the file from the file list
		 *
		 * @param {Object} fileInfo the opened file info
		 */
		openFileFromList(fileInfo) {
			const path = fileInfo['d:href'][0]
			const mime = Mime.lookup(path)
			const modal = this.components[mime]

			if (modal) {
				this.currentFile = {
					path,
					mime,
					modal
				}
				console.debug('Opened', path, mime)
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
				const path = prev['d:href'][0]
				const mime = Mime.lookup(path)
				const modal = this.components[mime]

				if (modal) {
					this.previousFile = {
						path,
						mime,
						modal
					}
				}
			// RESET
			} else {
				this.previousFile = {}
			}

			if (next) {
				const path = next['d:href'][0]
				const mime = Mime.lookup(path)
				const modal = this.components[mime]

				if (modal) {
					this.nextFile = {
						path,
						mime,
						modal
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

				// set the handler as registered
				this.registeredHandlers.push(handler.id)

				// register mime's component
				this.components[mime] = handler.component
				Vue.component(handler.component.name, handler.component)
			})
		},

		/**
		 * Close the viewer
		 */
		close() {
			this.currentFile = {}
			this.currentModal = null
			this.fileList = []
		},

		/**
		 * Open previous available file
		 */
		previous() {
			this.loading = true
			this.currentIndex--

			this.openFileFromList(this.fileList[this.currentIndex])
		},

		/**
		 * Open next available file
		 */
		next() {
			this.loading = true
			this.currentIndex++

			this.openFileFromList(this.fileList[this.currentIndex])
		},

		/**
		 * Component finished loading the data
		 */
		doneLoading() {
			this.loading = false
		}
	}
}
</script>

<style lang="scss">
.modal-mask .modal-container {
	display: flex !important;
	width: auto !important;
	background-color: black !important;
}

.component-fade-enter-active, .component-fade-leave-active {
	transition: opacity .3s ease;
}

.component-fade-enter, .component-fade-leave-to {
	opacity: 0;
}
</style>
