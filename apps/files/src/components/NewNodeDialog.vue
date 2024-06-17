<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
				:error="!isUniqueName"
				:helper-text="errorMessage"
				:label="label"
				:value.sync="localDefaultName" />
		</form>
	</NcDialog>
</template>

<script lang="ts">
import type { PropType } from 'vue'

import { defineComponent } from 'vue'
import { translate as t } from '@nextcloud/l10n'
import { getUniqueName } from '@nextcloud/files'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

interface ICanFocus {
	focus: () => void
}

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
	},
})
</script>
