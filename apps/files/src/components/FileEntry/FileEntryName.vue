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

	<component :is="linkTo.is"
		v-else
		ref="basename"
		:aria-hidden="isRenaming"
		class="files-list__row-name-link"
		data-cy-files-list-row-name-link
		v-bind="linkTo.params">
		<!-- Filename -->
		<span class="files-list__row-name-text">
			<!-- Keep the filename stuck to the extension to avoid whitespace rendering issues-->
			<span class="files-list__row-name-" v-text="basename" />
			<span class="files-list__row-name-ext" v-text="extension" />
		</span>
	</component>
</template>

<script lang="ts">
import type { FileAction, Node } from '@nextcloud/files'
import type { PropType } from 'vue'

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { FileType, InvalidFilenameError, InvalidFilenameErrorReason, NodeStatus, validateFilename } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { isAxiosError} from 'axios'
import Vue, { inject } from 'vue'

import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import { useNavigation } from '../../composables/useNavigation'
import { useRouteParameters } from '../../composables/useRouteParameters.ts'
import { useRenamingStore } from '../../store/renaming.ts'
import logger from '../../logger.js'

const forbiddenCharacters = loadState<string>('files', 'forbiddenCharacters', '').split('')

export default Vue.extend({
	name: 'FileEntryName',

	components: {
		NcTextField,
	},

	props: {
		/**
		 * The filename without extension
		 */
		basename: {
			type: String,
			required: true,
		},
		/**
		 * The extension of the filename
		 */
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
		const { currentView } = useNavigation()
		const { directory } = useRouteParameters()
		const renamingStore = useRenamingStore()

		const defaultFileAction = inject<FileAction | undefined>('defaultFileAction')

		return {
			currentView,
			defaultFileAction,
			directory,

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
				[FileType.File]: t('files', 'Filename'),
				[FileType.Folder]: t('files', 'Folder name'),
			}
			return matchLabel[this.source.type]
		},

		linkTo() {
			if (this.source.attributes.failed) {
				return {
					is: 'span',
					params: {
						title: t('files', 'This node is unavailable'),
					},
				}
			}

			if (this.defaultFileAction && this.currentView) {
				const displayName = this.defaultFileAction.displayName([this.source], this.currentView)
				return {
					is: 'button',
					params: {
						'aria-label': displayName,
						title: displayName,
						tabindex: '0',
					},
				}
			}

			// nothing interactive here, there is no default action
			// so if not even the download action works we only can show the list entry
			return {
				is: 'span',
			}
		},
	},

	watch: {
		/**
		 * If renaming starts, select the filename
		 * in the input, without the extension.
		 * @param renaming
		 */
		isRenaming: {
			immediate: true,
			handler(renaming: boolean) {
				if (renaming) {
					this.startRenaming()
				}
			},
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
		isFileNameValid(name: string) {
			if (name.trim() === '') {
				throw new Error(t('files', 'File name cannot be empty.'))
			} else if (this.checkIfNodeExists(name)) {
				throw new Error(t('files', '{newName} already exists.', { newName: name }))
			}

			try {
				validateFilename(name)
			} catch (error) {
				if (!(error instanceof InvalidFilenameError)) {
					logger.error(error as Error)
					return
				}
				switch (error.reason) {
				case InvalidFilenameErrorReason.Character:
					throw new Error(t('files', '"{segment}" is not allowed inside a filename.', { segment: error.segment }))
				case InvalidFilenameErrorReason.ReservedName:
					throw new Error(t('files', '"{segment}" is a forbidden file or folder name.', { segment: error.segment }))
				case InvalidFilenameErrorReason.Extension:
					if (error.segment.startsWith('.')) {
						throw new Error(t('files', '"{segment}" is not an allowed filetype.', { segment: error.segment }))
					} else {
						throw new Error(t('files', 'Filenames must not end with "{segment}".', { segment: error.segment }))
					}
				}
			}
		},
		checkIfNodeExists(name: string) {
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
						Overwrite: 'F',
					},
				})

				// Success 🎉
				emit('files:node:updated', this.source)
				emit('files:node:renamed', this.source)
				showSuccess(t('files', 'Renamed "{oldName}" to "{newName}"', { oldName, newName }))

				// Reset the renaming store
				this.stopRenaming()
				this.$nextTick(() => {
					const nameContainter = this.$refs.basename as HTMLElement | undefined
					nameContainter?.focus()
				})
			} catch (error) {
				logger.error('Error while renaming file', { error })
				// Rename back as it failed
				this.source.rename(oldName)
				// And ensure we reset to the renaming state
				this.startRenaming()

				if (isAxiosError(error)) {
					// TODO: 409 means current folder does not exist, redirect ?
					if (error?.response?.status === 404) {
						showError(t('files', 'Could not rename "{oldName}", it does not exist any more', { oldName }))
						return
					} else if (error?.response?.status === 412) {
						showError(t('files', 'The name "{newName}" is already used in the folder "{dir}". Please choose a different name.', { newName, dir: this.directory }))
						return
					}
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

<style scoped lang="scss">
button.files-list__row-name-link {
	background-color: unset;
	border: none;
	font-weight: normal;

	&:active {
		// No active styles - handled by the row entry
		background-color: unset !important;
	}
}
</style>
