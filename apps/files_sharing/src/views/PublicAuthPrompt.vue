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
			minlength="2"
			name="name"
			required
			:value.sync="name" />
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

import { getGuestNameValidity } from '../services/GuestNameValidity'

const storage = getBuilder('files_sharing').build()

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
			return [{
				label: t('files_sharing', 'Submit name'),
				type: 'primary',
				nativeType: 'submit',
			}]
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

			// Set the nickname
			setGuestNickname(nickname)

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
