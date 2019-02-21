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
		v-if="currentModal"
		:class="{'icon-loading': loading}"
		:view="currentModal"
		:has-previous="currentIndex > 0"
		:has-next="currentIndex < fileList.length - 1"
		@close="close"
		@previous="previous"
		@next="next">
		<component
			:is="currentModal"
			ref="wrapper"
			:mime="currentFile.mime"
			:path="currentFile.path"
			@loaded="doneLoading" />
	</modal>
</template>

<script>
import Mime from 'mime-types'
import Vue from 'vue'
import smoothReflow from 'vue-smooth-reflow'

import { Modal } from 'nextcloud-vue'

import FileList from 'Services/FileList'

export default {
	name: 'Viewer',

	components: {
		Modal
	},

	mixins: [
		smoothReflow
	],

	data: () => ({
		components: {},
		currentIndex: 0,
		currentModal: null,
		currentFile: {},
		fileList: [],
		mimeGroups: {},
		handlers: OCA.Viewer.availableHandlers,
		loading: true,
		root: `/remote.php/dav/files/${OC.getCurrentUser().uid}`
	}),

	watch: {
		// make sure any late external app can register handlers
		handlers: function() {
			this.registerHandlers()
		}
	},

	beforeMount() {
		// register on load
		document.addEventListener('DOMContentLoaded', event => {
			this.registerHandlers()
		})

	},

	mounted() {
		this.$smoothReflow({
			el: this.$refs.wrapper
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
				this.currentModal = this.components[mime]
				this.currentFile = {
					relativePath,
					path,
					mime
				}
				console.debug('Opened', path, mime)
			}

			// retrieve and store file List
			this.fileList = await FileList(OC.getCurrentUser().uid, fileInfo.dir, mimes)
			// store current position
			this.currentIndex = this.fileList.findIndex(file => file['d:href'][0] === this.root + relativePath)
		},

		/**
		 * Open the view and display the file from the file list
		 *
		 * @param {Object} fileInfo the opened file info
		 */
		openFileFromList(fileInfo) {
			const path = fileInfo['d:href'][0]
			const mime = Mime.lookup(path)

			if (this.components[mime]) {
				this.currentModal = this.components[mime]
				this.currentFile = {
					path,
					mime
				}
				console.debug('Opened', path, mime)
			}

		},

		/**
		 * Registering possible new handers
		 */
		registerHandlers() {
			this.handlers.forEach(handler => {

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

					// register mime's component
					this.components[mime] = handler.component
					Vue.component(handler.component.name, handler.component)
				})
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

<style>
.modal-mask .modal-container {
	display: flex !important;
	width: auto !important;
}
</style>
