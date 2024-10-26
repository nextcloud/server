<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<!-- Request note -->
		<NcNoteCard type="success">
			{{ t('files_sharing', 'You can now share the link below to allow people to upload files to your directory.') }}
		</NcNoteCard>

		<!-- Copy share link -->
		<NcInputField ref="clipboard"
			:value="shareLink"
			:label="t('files_sharing', 'Share link')"
			:readonly="true"
			:show-trailing-button="true"
			:trailing-button-label="t('files_sharing', 'Copy to clipboard')"
			data-cy-file-request-dialog-fieldset="link"
			@click="copyShareLink"
			@trailing-button-click="copyShareLink">
			<template #trailing-button-icon>
				<IconCheck v-if="isCopied" :size="20" />
				<IconClipboard v-else :size="20" />
			</template>
		</NcInputField>

		<template v-if="isShareByMailEnabled">
			<!-- Email share-->
			<NcTextField :value.sync="email"
				:label="t('files_sharing', 'Send link via email')"
				:placeholder="t('files_sharing', 'Enter an email address or paste a list')"
				data-cy-file-request-dialog-fieldset="email"
				type="email"
				@keypress.enter.stop="addNewEmail"
				@paste.stop.prevent="onPasteEmails"
				@focusout.native="addNewEmail" />

			<!-- Email list -->
			<div v-if="emails.length > 0" class="file-request-dialog__emails">
				<NcChip v-for="mail in emails"
					:key="mail"
					:aria-label-close="t('files_sharing', 'Remove email')"
					:text="mail"
					@close="$emit('remove-email', mail)">
					<template #icon>
						<NcAvatar :disable-menu="true"
							:disable-tooltip="true"
							:display-name="mail"
							:is-no-user="true"
							:show-user-status="false"
							:size="24" />
					</template>
				</NcChip>
			</div>
		</template>
	</div>
</template>

<script lang="ts">
import type { PropType } from 'vue'
import Share from '../../models/Share.ts'

import { defineComponent } from 'vue'
import { generateUrl, getBaseUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { n, t } from '@nextcloud/l10n'

import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcInputField from '@nextcloud/vue/dist/Components/NcInputField.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcChip from '@nextcloud/vue/dist/Components/NcChip.js'

import IconCheck from 'vue-material-design-icons/Check.vue'
import IconClipboard from 'vue-material-design-icons/ClipboardText.vue'

export default defineComponent({
	name: 'NewFileRequestDialogFinish',

	components: {
		IconCheck,
		IconClipboard,
		NcAvatar,
		NcInputField,
		NcNoteCard,
		NcTextField,
		NcChip,
	},

	props: {
		share: {
			type: Object as PropType<Share>,
			required: true,
		},
		emails: {
			type: Array as PropType<string[]>,
			required: true,
		},
		isShareByMailEnabled: {
			type: Boolean,
			required: true,
		},
	},

	emits: ['add-email', 'remove-email'],

	setup() {
		return {
			n, t,
		}
	},

	data() {
		return {
			isCopied: false,
			email: '',
		}
	},

	computed: {
		shareLink() {
			return generateUrl('/s/{token}', { token: this.share.token }, { baseURL: getBaseUrl() })
		},
	},

	methods: {
		async copyShareLink(event: MouseEvent) {
			if (this.isCopied) {
				this.isCopied = false
				return
			}

			if (!navigator.clipboard) {
				// Clipboard API not available
				window.prompt(t('files_sharing', 'Automatically copying failed, please copy the share link manually'), this.shareLink)
				return
			}

			await navigator.clipboard.writeText(this.shareLink)

			showSuccess(t('files_sharing', 'Link copied to clipboard'))
			this.isCopied = true
			event.target?.select?.()

			setTimeout(() => {
				this.isCopied = false
			}, 3000)
		},

		addNewEmail(e: KeyboardEvent) {
			if (this.email.trim() === '') {
				return
			}

			if (e.target instanceof HTMLInputElement) {
				// Reset the custom validity
				e.target.setCustomValidity('')

				// Check if the field is valid
				if (e.target.checkValidity() === false) {
					e.target.reportValidity()
					return
				}

				// The email is already in the list
				if (this.emails.includes(this.email.trim())) {
					e.target.setCustomValidity(t('files_sharing', 'Email already added'))
					e.target.reportValidity()
					return
				}

				// Check if the email is valid
				if (!this.isValidEmail(this.email.trim())) {
					e.target.setCustomValidity(t('files_sharing', 'Invalid email address'))
					e.target.reportValidity()
					return
				}

				this.$emit('add-email', this.email.trim())
				this.email = ''
			}
		},

		// Handle dumping a list of emails
		onPasteEmails(e: ClipboardEvent) {
			const clipboardData = e.clipboardData
			if (!clipboardData) {
				return
			}

			const pastedText = clipboardData.getData('text')
			const emails = pastedText.split(/[\s,;]+/).filter(Boolean).map((email) => email.trim())

			const duplicateEmails = emails.filter((email) => this.emails.includes(email))
			const validEmails = emails.filter((email) => this.isValidEmail(email) && !duplicateEmails.includes(email))
			const invalidEmails = emails.filter((email) => !this.isValidEmail(email))
			validEmails.forEach((email) => this.$emit('add-email', email))

			// Warn about invalid emails
			if (invalidEmails.length > 0) {
				showError(n('files_sharing', 'The following email address is not valid: {emails}', 'The following email addresses are not valid: {emails}', invalidEmails.length, { emails: invalidEmails.join(', ') }))
			}

			// Warn about duplicate emails
			if (duplicateEmails.length > 0) {
				showError(n('files_sharing', '{count} email address already added', '{count} email addresses already added', duplicateEmails.length, { count: duplicateEmails.length }))
			}

			if (validEmails.length > 0) {
				showSuccess(n('files_sharing', '{count} email address added', '{count} email addresses added', validEmails.length, { count: validEmails.length }))
			}

			this.email = ''
		},

		// No need to have a fancy regex, just check for an @
		isValidEmail(email: string): boolean {
			return email.includes('@')
		},
	},
})
</script>
<style scoped>
.input-field,
.file-request-dialog__emails {
	margin-top: var(--margin);
}

.file-request-dialog__emails {
	display: flex;
	gap: var(--default-grid-baseline);
	flex-wrap: wrap;
}
</style>
