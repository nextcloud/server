<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog class="public-auth-prompt"
		data-cy-public-auth-prompt-dialog
		dialog-classes="public-auth-prompt__dialog"
		:can-close="false"
		:name="dialogName">
		<h3 v-if="owner" class="public-auth-prompt__subtitle">
			{{ t('files_sharing', '{ownerDisplayName} shared a folder with you.', { ownerDisplayName }) }}
		</h3>

		<!-- Header -->
		<NcNoteCard type="info" class="public-auth-prompt__header">
			<p id="public-auth-prompt-dialog-description" class="public-auth-prompt__description">
				{{ t('files_sharing', 'To upload files, you need to provide your name first.') }}
			</p>
		</NcNoteCard>

		<!-- Form -->
		<form ref="form"
			aria-describedby="public-auth-prompt-dialog-description"
			class="public-auth-prompt__form"
			@submit.prevent.stop="">
			<NcTextField ref="input"
				class="public-auth-prompt__input"
				data-cy-public-auth-prompt-dialog-name
				:label="t('files_sharing', 'Enter your name')"
				:minlength="2"
				:required="true"
				:value.sync="name"
				name="name" />
		</form>

		<!-- Submit -->
		<template #actions>
			<NcButton ref="submit"
				data-cy-public-auth-prompt-dialog-submit
				:disabled="name.trim() === ''"
				@click="onSubmit">
				{{ t('files_sharing', 'Submit name') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import { loadState } from '@nextcloud/initial-state'

export default defineComponent({
	name: 'PublicAuthPrompt',

	components: {
		NcButton,
		NcDialog,
		NcNoteCard,
		NcTextField,
	},

	setup() {
		return {
			t,

			owner: loadState('files_sharing', 'owner', ''),
			ownerDisplayName: loadState('files_sharing', 'ownerDisplayName', ''),
			label: loadState('files_sharing', 'label', ''),
			note: loadState('files_sharing', 'note', ''),
			filename: loadState('files_sharing', 'filename', ''),
		}
	},

	data() {
		return {
			name: '',
		}
	},

	computed: {
		dialogName() {
			return this.t('files_sharing', 'Upload files to {folder}', { folder: this.label || this.filename })
		},
	},

	beforeMount() {
		// Pre-load the name from local storage if already set by another app
		// like Talk, Colabora or Text...
		const talkNick = localStorage.getItem('nick')
		if (talkNick) {
			this.name = talkNick
		}
	},

	methods: {
		onSubmit() {
			const form = this.$refs.form as HTMLFormElement
			if (!form.checkValidity()) {
				form.reportValidity()
				return
			}

			if (this.name.trim() === '') {
				return
			}

			localStorage.setItem('nick', this.name)
			this.$emit('close')
		},
	},
})
</script>
<style lang="scss">
.public-auth-prompt {
	&__subtitle {
		// Smaller than dialog title
		font-size: 16px;
		margin-block: 12px;
	}

	&__header {
		// Fix extra margin generating an unwanted gap
		margin-block: 12px;
	}

	&__form {
		// Double the margin of the header
		margin-block: 24px;
	}
}
</style>
