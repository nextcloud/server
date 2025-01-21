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
		<span class="files-list__row-name-text" dir="auto">
			<!-- Keep the filename stuck to the extension to avoid whitespace rendering issues-->
			<span class="files-list__row-name-" v-text="basename" />
			<span class="files-list__row-name-ext" v-text="extension" />
		</span>
	</component>
</template>

<script lang="ts">
import type { FileAction, Node } from '@nextcloud/files'
import type { PropType } from 'vue'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { FileType, NodeStatus } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent, inject } from 'vue'

import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import { useNavigation } from '../../composables/useNavigation'
import { useFileListWidth } from '../../composables/useFileListWidth.ts'
import { useRouteParameters } from '../../composables/useRouteParameters.ts'
import { useRenamingStore } from '../../store/renaming.ts'
import { getFilenameValidity } from '../../utils/filenameValidity.ts'
import logger from '../../logger.ts'

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
		// The file list is guaranteed to be only shown with active view - thus we can set the `loaded` flag
		const { currentView } = useNavigation(true)
		const { directory } = useRouteParameters()
		const filesListWidth = useFileListWidth()
		const renamingStore = useRenamingStore()

		const defaultFileAction = inject<FileAction | undefined>('defaultFileAction')

		return {
			currentView,
			defaultFileAction,
			directory,
			filesListWidth,

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

			if (this.defaultFileAction) {
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
			if (newName === oldName) {
				this.stopRenaming()
				return
			}

			try {
				const status = await this.renamingStore.rename()
				if (status) {
					showSuccess(t('files', 'Renamed "{oldName}" to "{newName}"', { oldName, newName }))
					this.$nextTick(() => {
						const nameContainer = this.$refs.basename as HTMLElement | undefined
						nameContainer?.focus()
					})
				} else {
					// Was cancelled - meaning the renaming state is just reset
				}
			} catch (error) {
				logger.error(error as Error)
				showError((error as Error).message)
				// And ensure we reset to the renaming state
				this.startRenaming()
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
