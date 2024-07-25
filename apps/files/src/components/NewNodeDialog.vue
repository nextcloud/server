<!--
  - @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
  -
  - @author Ferdinand Thiessen <opensource@fthiessen.de>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<NcDialog :name="name"
		:open="open"
		close-on-click-outside
		out-transition
		@update:open="onClose">
		<template #actions>
			<NcButton type="primary"
				:disabled="!isUniqueName"
				@click="onCreate">
				{{ t('files', 'Create') }}
			</NcButton>
		</template>
		<form @submit.prevent="onCreate">
			<NcTextField ref="input"
				class="dialog__input"
				:error="!isUniqueName"
				:helper-text="errorMessage"
				:label="label"
				:value.sync="localDefaultName"
				@keyup="checkInputValidity" />
		</form>
	</NcDialog>
</template>

<script lang="ts">
import type { PropType } from 'vue'

import { defineComponent } from 'vue'
import { translate as t } from '@nextcloud/l10n'
import { getUniqueName } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import logger from '../logger.js'

interface ICanFocus {
	focus: () => void
}

const forbiddenCharacters = loadState<string>('files', 'forbiddenCharacters', '').split('')

export default defineComponent({
	name: 'NewNodeDialog',
	components: {
		NcButton,
		NcDialog,
		NcTextField,
	},
	props: {
		/**
		 * The name to be used by default
		 */
		defaultName: {
			type: String,
			default: t('files', 'New folder'),
		},
		/**
		 * Other files that are in the current directory
		 */
		otherNames: {
			type: Array as PropType<string[]>,
			default: () => [],
		},
		/**
		 * Open state of the dialog
		 */
		open: {
			type: Boolean,
			default: true,
		},
		/**
		 * Dialog name
		 */
		name: {
			type: String,
			default: t('files', 'Create new folder'),
		},
		/**
		 * Input label
		 */
		label: {
			type: String,
			default: t('files', 'Folder name'),
		},
	},
	emits: {
		close: (name: string|null) => name === null || name,
	},
	data() {
		return {
			localDefaultName: this.defaultName || t('files', 'New folder'),
		}
	},
	computed: {
		errorMessage() {
			if (this.isUniqueName) {
				return ''
			} else {
				return t('files', 'A file or folder with that name already exists.')
			}
		},
		uniqueName() {
			return getUniqueName(this.localDefaultName, this.otherNames)
		},
		isUniqueName() {
			return this.localDefaultName === this.uniqueName
		},
	},
	watch: {
		defaultName() {
			this.localDefaultName = this.defaultName || t('files', 'New folder')
		},

		/**
		 * Ensure the input is focussed even if the dialog is already mounted but not open
		 */
		open() {
			this.$nextTick(() => this.focusInput())
		},
	},
	mounted() {
		// on mounted lets use the unique name
		this.localDefaultName = this.uniqueName
		this.$nextTick(() => this.focusInput())
	},
	methods: {
		t,

		/**
		 * Focus the filename input field
		 */
		focusInput() {
			if (this.open) {
				this.$nextTick(() => (this.$refs.input as unknown as ICanFocus)?.focus?.())
			}
		},

		onCreate() {
			this.$emit('close', this.localDefaultName)
		},
		onClose(state: boolean) {
			if (!state) {
				this.$emit('close', null)
			}
		},

		/**
		 * Check if the file name is valid and update the
		 * input validity using browser's native validation.
		 * @param event the keyup event
		 */
		checkInputValidity(event: KeyboardEvent) {
			const input = event.target as HTMLInputElement
			const newName = this.localDefaultName.trim?.() || ''
			logger.debug('Checking input validity', { newName })
			try {
				this.isFileNameValid(newName)
				input.setCustomValidity('')
				input.title = ''
			} catch (e) {
				if (e instanceof Error) {
					input.setCustomValidity(e.message)
					input.title = e.message
				} else {
					input.setCustomValidity(t('files', 'Invalid file name'))
				}
			} finally {
				input.reportValidity()
			}
		},

		isFileNameValid(name: string) {
			const trimmedName = name.trim()
			const char = trimmedName.indexOf('/') !== -1
				? '/'
				: forbiddenCharacters.find((char) => trimmedName.includes(char))

			if (trimmedName === '.' || trimmedName === '..') {
				throw new Error(t('files', '"{name}" is an invalid file name.', { name }))
			} else if (trimmedName.length === 0) {
				throw new Error(t('files', 'File name cannot be empty.'))
			} else if (char) {
				throw new Error(t('files', '"{char}" is not allowed inside a file name.', { char }))
			} else if (trimmedName.match(window.OC.config.blacklist_files_regex)) {
				throw new Error(t('files', '"{name}" is not an allowed filetype.', { name }))
			}

			return true
		},
	},
})
</script>

<style lang="scss" scoped>
.dialog__input {
	:deep(input:invalid) {
		// Show red border on invalid input
		border-color: var(--color-error);
		color: red;
	}
}
</style>
