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
	<!-- Rename input -->
	<form v-if="isRenaming"
		v-on-click-outside="stopRenaming"
		:aria-label="t('files', 'Rename file')"
		class="files-list__row-rename"
		@submit.prevent.stop="onRename">
		<NcTextField ref="renameInput"
			:label="renameLabel"
			:autofocus="true"
			:minlength="1"
			:required="true"
			:value.sync="newName"
			enterkeyhint="done"
			@keyup="checkInputValidity"
			@keyup.esc="stopRenaming" />
	</form>

	<a v-else
		ref="basename"
		:aria-hidden="isRenaming"
		class="files-list__row-name-link"
		data-cy-files-list-row-name-link
		v-bind="linkTo"
		@click="$emit('click', $event)">
		<!-- File name -->
		<span class="files-list__row-name-text">
			<!-- Keep the displayName stuck to the extension to avoid whitespace rendering issues-->
			<span class="files-list__row-name-" v-text="displayName" />
			<span class="files-list__row-name-ext" v-text="extension" />
		</span>
	</a>
</template>

<script lang="ts">
import { emit } from '@nextcloud/event-bus'
import { FileType, NodeStatus, Permission } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import Vue, { PropType } from 'vue'

import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import { useRenamingStore } from '../../store/renaming.ts'
import logger from '../../logger.js'

const forbiddenCharacters = loadState('files', 'forbiddenCharacters', '') as string

export default Vue.extend({
	name: 'FileEntryName',

	components: {
		NcTextField,
	},

	props: {
		displayName: {
			type: String,
			required: true,
		},
		extension: {
			type: String,
			required: true,
		},
		filesListWidth: {
			type: Number,
			required: true,
		},
		nodes: {
			type: Array as PropType<Node[]>,
			required: true,
		},
		source: {
			type: Object as PropType<Node>,
			required: true,
		},
		gridMode: {
			type: Boolean,
			default: false,
		},
	},

	setup() {
		const renamingStore = useRenamingStore()
		return {
			renamingStore,
		}
	},

	computed: {
		isRenaming() {
			return this.renamingStore.renamingNode === this.source
		},
		isRenamingSmallScreen() {
			return this.isRenaming && this.filesListWidth < 512
		},
		newName: {
			get() {
				return this.renamingStore.newName
			},
			set(newName) {
				this.renamingStore.newName = newName
			},
		},

		renameLabel() {
			const matchLabel: Record<FileType, string> = {
				[FileType.File]: t('files', 'File name'),
				[FileType.Folder]: t('files', 'Folder name'),
			}
			return matchLabel[this.source.type]
		},

		linkTo() {
			if (this.source.attributes.failed) {
				return {
					title: t('files', 'This node is unavailable'),
					is: 'span',
				}
			}

			const enabledDefaultActions = this.$parent?.$refs?.actions?.enabledDefaultActions
			if (enabledDefaultActions?.length > 0) {
				const action = enabledDefaultActions[0]
				const displayName = action.displayName([this.source], this.currentView)
				return {
					title: displayName,
					role: 'button',
				}
			}

			if (this.source?.permissions & Permission.READ) {
				return {
					download: this.source.basename,
					href: this.source.source,
					title: t('files', 'Download file {name}', { name: this.displayName }),
				}
			}

			return {
				is: 'span',
			}
		},
	},

	watch: {
		/**
		 * If renaming starts, select the file name
		 * in the input, without the extension.
		 * @param renaming
		 */
		isRenaming(renaming: boolean) {
			if (renaming) {
				this.startRenaming()
			}
		},
	},

	methods: {
		/**
		 * Check if the file name is valid and update the
		 * input validity using browser's native validation.
		 * @param event the keyup event
		 */
		checkInputValidity(event?: KeyboardEvent) {
			const input = event.target as HTMLInputElement
			const newName = this.newName.trim?.() || ''
			logger.debug('Checking input validity', { newName })
			try {
				this.isFileNameValid(newName)
				input.setCustomValidity('')
				input.title = ''
			} catch (e) {
				input.setCustomValidity(e.message)
				input.title = e.message
			} finally {
				input.reportValidity()
			}
		},
		isFileNameValid(name) {
			const trimmedName = name.trim()
			if (trimmedName === '.' || trimmedName === '..') {
				throw new Error(t('files', '"{name}" is an invalid file name.', { name }))
			} else if (trimmedName.length === 0) {
				throw new Error(t('files', 'File name cannot be empty.'))
			} else if (trimmedName.indexOf('/') !== -1) {
				throw new Error(t('files', '"/" is not allowed inside a file name.'))
			} else if (trimmedName.match(OC.config.blacklist_files_regex)) {
				throw new Error(t('files', '"{name}" is not an allowed filetype.', { name }))
			} else if (this.checkIfNodeExists(name)) {
				throw new Error(t('files', '{newName} already exists.', { newName: name }))
			}

			const toCheck = trimmedName.split('')
			toCheck.forEach(char => {
				if (forbiddenCharacters.indexOf(char) !== -1) {
					throw new Error(this.t('files', '"{char}" is not allowed inside a file name.', { char }))
				}
			})

			return true
		},
		checkIfNodeExists(name) {
			return this.nodes.find(node => node.basename === name && node !== this.source)
		},

		startRenaming() {
			this.$nextTick(() => {
				// Using split to get the true string length
				const extLength = (this.source.extension || '').split('').length
				const length = this.source.basename.split('').length - extLength
				const input = this.$refs.renameInput?.$refs?.inputField?.$refs?.input
				if (!input) {
					logger.error('Could not find the rename input')
					return
				}
				input.setSelectionRange(0, length)
				input.focus()

				// Trigger a keyup event to update the input validity
				input.dispatchEvent(new Event('keyup'))
			})
		},
		stopRenaming() {
			if (!this.isRenaming) {
				return
			}

			// Reset the renaming store
			this.renamingStore.$reset()
		},

		// Rename and move the file
		async onRename() {
			const oldName = this.source.basename
			const oldEncodedSource = this.source.encodedSource
			const newName = this.newName.trim?.() || ''
			if (newName === '') {
				showError(t('files', 'Name cannot be empty'))
				return
			}

			if (oldName === newName) {
				this.stopRenaming()
				return
			}

			// Checking if already exists
			if (this.checkIfNodeExists(newName)) {
				showError(t('files', 'Another entry with the same name already exists'))
				return
			}

			// Set loading state
			this.loading = 'renaming'
			Vue.set(this.source, 'status', NodeStatus.LOADING)

			// Update node
			this.source.rename(newName)

			logger.debug('Moving file to', { destination: this.source.encodedSource, oldEncodedSource })
			try {
				await axios({
					method: 'MOVE',
					url: oldEncodedSource,
					headers: {
						Destination: this.source.encodedSource,
					},
				})

				// Success 🎉
				emit('files:node:updated', this.source)
				emit('files:node:renamed', this.source)
				showSuccess(t('files', 'Renamed "{oldName}" to "{newName}"', { oldName, newName }))

				// Reset the renaming store
				this.stopRenaming()
				this.$nextTick(() => {
					this.$refs.basename.focus()
				})
			} catch (error) {
				logger.error('Error while renaming file', { error })
				this.source.rename(oldName)
				this.$refs.renameInput.focus()

				// TODO: 409 means current folder does not exist, redirect ?
				if (error?.response?.status === 404) {
					showError(t('files', 'Could not rename "{oldName}", it does not exist any more', { oldName }))
					return
				} else if (error?.response?.status === 412) {
					showError(t('files', 'The name "{newName}" is already used in the folder "{dir}". Please choose a different name.', { newName, dir: this.currentDir }))
					return
				}

				// Unknown error
				showError(t('files', 'Could not rename "{oldName}"', { oldName }))
			} finally {
				this.loading = false
				Vue.set(this.source, 'status', undefined)
			}
		},


		t,
	},
})
</script>
