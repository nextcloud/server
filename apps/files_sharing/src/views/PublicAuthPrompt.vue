<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog :buttons="dialogButtons"
		class="public-auth-prompt"
		data-cy-public-auth-prompt-dialog
		is-form
		:can-close="false"
		:name="title"
		@submit="onSubmit">
		<p v-if="subtitle" class="public-auth-prompt__subtitle">
			{{ subtitle }}
		</p>

		<!-- Header -->
		<NcNoteCard class="public-auth-prompt__header"
			:text="notice"
			type="info" />

		<!-- Form -->
		<NcTextField ref="input"
			class="public-auth-prompt__input"
			data-cy-public-auth-prompt-dialog-name
			:label="t('files_sharing', 'Name')"
			:placeholder="t('files_sharing', 'Enter your name')"
			:required="!cancellable"
			:value.sync="name"
			minlength="2"
			name="name" />
	</NcDialog>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { getBuilder } from '@nextcloud/browser-storage'
import { loadState } from '@nextcloud/initial-state'
import { setGuestNickname } from '@nextcloud/auth'
import { t } from '@nextcloud/l10n'

import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { showError } from '@nextcloud/dialogs'

import { getGuestNameValidity } from '../services/GuestNameValidity'

const storage = getBuilder('files_sharing').build()

// TODO: move to @nextcloud/auth
export default defineComponent({
	name: 'PublicAuthPrompt',

	components: {
		NcDialog,
		NcNoteCard,
		NcTextField,
	},

	props: {
		/**
		 * Preselected nickname
		 * @default '' No name preselected by default
		 */
		nickname: {
			type: String,
			default: '',
		},

		/**
		 * Dialog title
		 */
		title: {
			type: String,
			default: t('files_sharing', 'Guest identification'),
		},

		/**
		 * Dialog subtitle
		 * @default 'Enter your name to access the file'
		 */
		subtitle: {
			type: String,
			default: '',
		},

		/**
		 * Dialog notice
		 * @default 'You are currently not identified.'
		 */
		notice: {
			type: String,
			default: t('files_sharing', 'You are currently not identified.'),
		},

		/**
		 * Dialog submit button label
		 * @default 'Submit name'
		 */
		submitLabel: {
			type: String,
			default: t('files_sharing', 'Submit name'),
		},

		/**
		 * Whether the dialog is cancellable
		 * @default false
		 */
		cancellable: {
			type: Boolean,
			default: false,
		},
	},

	setup() {
		return {
			t,
		}
	},

	data() {
		return {
			name: '',
		}
	},

	computed: {
		dialogButtons() {
			const cancelButton = {
				label: t('files_sharing', 'Cancel'),
				type: 'tertiary',
				callback: () => this.$emit('close'),
			}

			const submitButton = {
				label: this.submitLabel,
				type: 'primary',
				nativeType: 'submit',
			}

			// If the dialog is cancellable, add a cancel button
			if (this.cancellable) {
				return [cancelButton, submitButton]
			}

			 return [submitButton]
		},
	},

	watch: {
		/** Reset name to pre-selected nickname (e.g. Talk / Collabora ) */
		nickname: {
			handler() {
				this.name = this.nickname
			},
			immediate: true,
		},

		name() {
			// Check validity of the new name
			const newName = this.name.trim?.() || ''
			const input = (this.$refs.input as Vue|undefined)?.$el.querySelector('input')
			if (!input) {
				return
			}

			const validity = getGuestNameValidity(newName)
			input.setCustomValidity(validity)
			input.reportValidity()
		},
	},

	methods: {
		onSubmit() {
			const nickname = this.name.trim()

			if (nickname === '') {
				// Show error if the nickname is empty
				showError(t('files_sharing', 'You cannot leave the name empty.'))
				return
			}

			if (nickname.length < 2) {
				// Show error if the nickname is too short
				showError(t('files_sharing', 'Please enter a name with at least 2 characters.'))
				return
			}

			try {
				// Set the nickname
				setGuestNickname(nickname)
			} catch (e) {
				showError(t('files_sharing', 'Failed to set nickname.'))
			}

			// Set the dialog as shown
			storage.setItem('public-auth-prompt-shown', 'true')

			// Close the dialog
			this.$emit('close', this.name)
		},
	},
})
</script>
<style scoped lang="scss">
.public-auth-prompt {
	&__subtitle {
		// Smaller than dialog title
		font-size: 1.25em;
		margin-block: 0 calc(3 * var(--default-grid-baseline));
	}

	&__header {
		margin-block: 0 calc(3 * var(--default-grid-baseline));
	}

	&__input {
		margin-block: calc(4 * var(--default-grid-baseline)) calc(2 * var(--default-grid-baseline));
	}
}
</style>
