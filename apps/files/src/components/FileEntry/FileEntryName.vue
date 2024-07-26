<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<!-- Rename input -->
	<form v-if="isRenaming"
		ref="renameForm"
		v-on-click-outside="onRename"
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
import type { Node } from '@nextcloud/files'
import type { PropType } from 'vue'

import axios, { isAxiosError } from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { FileType, NodeStatus, Permission } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import { useNavigation } from '../../composables/useNavigation'
import { useRenamingStore } from '../../store/renaming.ts'
import { getFilenameValidity } from '../../utils/filenameValidity.ts'
import logger from '../../logger.js'

export default defineComponent({
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
		const renamingStore = useRenamingStore()

		return {
			currentView,

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
			if (this.source.status === NodeStatus.FAILED) {
				return {
					is: 'span',
					params: {
						title: t('files', 'This node is unavailable'),
					},
				}
			}

			const enabledDefaultActions = this.$parent?.$refs?.actions?.enabledDefaultActions
			if (enabledDefaultActions?.length > 0) {
				const action = enabledDefaultActions[0]
				const displayName = action.displayName([this.source], this.currentView)
				return {
					is: 'a',
					params: {
						title: displayName,
						role: 'button',
						tabindex: '0',
					},
				}
			}

			if (this.source?.permissions & Permission.READ) {
				return {
					is: 'a',
					params: {
						download: this.source.basename,
						href: this.source.source,
						title: t('files', 'Download file {name}', { name: `${this.basename}${this.extension}` }),
						tabindex: '0',
					},
				}
			}

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

		newName() {
			// Check validity of the new name
			const newName = this.newName.trim?.() || ''
			const input = (this.$refs.renameInput as Vue|undefined)?.$el.querySelector('input')
			if (!input) {
				return
			}

			let validity = getFilenameValidity(newName)
			// Checking if already exists
			if (validity === '' && this.checkIfNodeExists(newName)) {
				validity = t('files', 'Another entry with the same name already exists.')
			}
			this.$nextTick(() => {
				if (this.isRenaming) {
					input.setCustomValidity(validity)
					input.reportValidity()
				}
			})
		},
	},

	methods: {
		checkIfNodeExists(name: string) {
			return this.nodes.find(node => node.basename === name && node !== this.source)
		},

		startRenaming() {
			this.$nextTick(() => {
				// Using split to get the true string length
				const input = (this.$refs.renameInput as Vue|undefined)?.$el.querySelector('input')
				if (!input) {
					logger.error('Could not find the rename input')
					return
				}
				input.focus()
				const length = this.source.basename.length - (this.source.extension ?? '').length
				input.setSelectionRange(0, length)

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
			const newName = this.newName.trim?.() || ''
			const form = this.$refs.renameForm as HTMLFormElement
			if (!form.checkValidity()) {
				showError(t('files', 'Invalid filename.') + ' ' + getFilenameValidity(newName))
				return
			}

			const oldName = this.source.basename
			const oldEncodedSource = this.source.encodedSource
			if (oldName === newName) {
				this.stopRenaming()
				return
			}

			// Set loading state
			this.$set(this.source, 'status', NodeStatus.LOADING)

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
					this.$refs.basename?.focus()
				})
			} catch (error) {
				logger.error('Error while renaming file', { error })
				this.source.rename(oldName)
				this.$refs.renameInput?.focus()

				if (isAxiosError(error)) {
					// TODO: 409 means current folder does not exist, redirect ?
					if (error?.response?.status === 404) {
						showError(t('files', 'Could not rename "{oldName}", it does not exist any more', { oldName }))
						return
					} else if (error?.response?.status === 412) {
						showError(t('files', 'The name "{newName}" is already used in the folder "{dir}". Please choose a different name.', { newName, dir: this.currentDir }))
						return
					}
				}

				// Unknown error
				showError(t('files', 'Could not rename "{oldName}"', { oldName }))
			} finally {
				this.$set(this.source, 'status', undefined)
			}
		},

		t,
	},
})
</script>
