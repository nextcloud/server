<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<!-- Request note -->
		<NcNoteCard type="success">
			{{ t('files_sharing', 'You can now share the link below to allow others to upload files to your directory.') }}
		</NcNoteCard>

		<!-- Copy share link -->
		<NcInputField ref="clipboard"
			:value="shareLink"
			:label="t('files_sharing', 'Share link')"
			:readonly="true"
			:show-trailing-button="true"
			:trailing-button-label="t('files_sharing', 'Copy to clipboard')"
			@click="copyShareLink"
			@click-trailing-button="copyShareLink">
			<template #trailing-button-icon>
				<IconCheck v-if="isCopied" :size="20" @click="isCopied = false" />
				<IconClipboard v-else :size="20" @click="copyShareLink" />
			</template>
		</NcInputField>

		<template v-if="isShareByMailEnabled">
			<!-- Email share-->
			<NcTextField :value.sync="email"
				:label="t('files_sharing', 'Send link via email')"
				:placeholder="t('files_sharing', 'Enter an email address or paste a list')"
				type="email"
				@keypress.enter.stop="addNewEmail"
				@paste.stop.prevent="onPasteEmails" />

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
							:is-guest="true"
							:size="24"
							:user="mail" />
					</template>
				</NcChip>
			</div>
		</template>
	</div>
</template>

<script lang="ts">
import type { PropType } from 'vue'
import Share from '../../models/Share'

import { defineComponent } from 'vue'
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate, translatePlural } from '@nextcloud/l10n'

import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcInputField from '@nextcloud/vue/dist/Components/NcInputField.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcChip from '@nextcloud/vue/dist/Components/NcChip.js'

import IconCheck from 'vue-material-design-icons/Check.vue'
import IconClipboard from 'vue-material-design-icons/Clipboard.vue'
import { getCapabilities } from '@nextcloud/capabilities'

export default defineComponent({
	name: 'FileRequestFinish',

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
	},

	emits: ['add-email', 'remove-email'],

	setup() {
		return {
			n: translatePlural,
			t: translate,
			isShareByMailEnabled: getCapabilities()?.files_sharing?.sharebymail?.enabled === true,
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
			return window.location.protocol + '//' + window.location.host + generateUrl('/s/') + this.share.token
		},
	},

	methods: {
		async copyShareLink(event: MouseEvent) {
			if (!navigator.clipboard) {
				// Clipboard API not available
				showError(this.t('files_sharing', 'Clipboard is not available'))
				return
			}

			await navigator.clipboard.writeText(this.shareLink)

			showSuccess(this.t('files_sharing', 'Link copied to clipboard'))
			this.isCopied = true
			event.target?.select?.()

			setTimeout(() => {
				this.isCopied = false
			}, 3000)
		},

		addNewEmail(e: KeyboardEvent) {
			if (e.target instanceof HTMLInputElement) {
				if (e.target.checkValidity() === false) {
					e.target.reportValidity()
					return
				}

				// The email is already in the list
				if (this.emails.includes(this.email.trim())) {
					e.target.setCustomValidity(this.t('files_sharing', 'Email already added'))
					e.target.reportValidity()
					return
				}

				if (!this.isValidEmail(this.email.trim())) {
					e.target.setCustomValidity(this.t('files_sharing', 'Invalid email address'))
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
				showError(this.n('files_sharing', 'The following email address is not valid: {emails}', 'The following email addresses are not valid: {emails}', invalidEmails.length, { emails: invalidEmails.join(', ') }))
			}

			// Warn about duplicate emails
			if (duplicateEmails.length > 0) {
				showError(this.n('files_sharing', '1 email address already added', '{count} email addresses already added', duplicateEmails.length, { count: duplicateEmails.length }))
			}

			if (validEmails.length > 0) {
				showSuccess(this.n('files_sharing', '1 email address added', '{count} email addresses added', validEmails.length, { count: validEmails.length }))
			}

			this.email = ''
		},

		isValidEmail(email) {
			const regExpEmail = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
			return regExpEmail.test(email)
		},
	},
})
</script>
<style scoped>
.input-field,
.file-request-dialog__emails {
	margin-top: var(--secondary-margin);
}

.file-request-dialog__emails {
	display: flex;
	gap: var(--default-grid-baseline);
	flex-wrap: wrap;
}
</style>
