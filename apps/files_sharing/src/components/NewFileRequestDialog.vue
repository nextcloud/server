<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog can-close
		class="file-request-dialog"
		data-cy-file-request-dialog
		:close-on-click-outside="false"
		:name="currentStep !== STEP.LAST ? t('files_sharing', 'Create a file request') : t('files_sharing', 'File request created')"
		size="normal"
		@closing="onCancel">
		<!-- Header -->
		<NcNoteCard v-show="currentStep === STEP.FIRST" type="info" class="file-request-dialog__header">
			<p id="file-request-dialog-description" class="file-request-dialog__description">
				{{ t('files_sharing', 'Collect files from others even if they do not have an account.') }}
				{{ t('files_sharing', 'To ensure you can receive files, verify you have enough storage available.') }}
			</p>
		</NcNoteCard>

		<!-- Main form -->
		<form ref="form"
			class="file-request-dialog__form"
			aria-describedby="file-request-dialog-description"
			:aria-label="t('files_sharing', 'File request')"
			aria-live="polite"
			data-cy-file-request-dialog-form
			@submit.prevent.stop="">
			<FileRequestIntro v-show="currentStep === STEP.FIRST"
				:context="context"
				:destination.sync="destination"
				:disabled="loading"
				:label.sync="label"
				:note.sync="note" />

			<FileRequestDatePassword v-show="currentStep === STEP.SECOND"
				:disabled="loading"
				:expiration-date.sync="expirationDate"
				:password.sync="password" />

			<FileRequestFinish v-if="share"
				v-show="currentStep === STEP.LAST"
				:emails="emails"
				:is-share-by-mail-enabled="isShareByMailEnabled"
				:share="share"
				@add-email="email => emails.push(email)"
				@remove-email="onRemoveEmail" />
		</form>

		<!-- Controls -->
		<template #actions>
			<!-- Back -->
			<NcButton v-show="currentStep === STEP.SECOND"
				:aria-label="t('files_sharing', 'Previous step')"
				:disabled="loading"
				data-cy-file-request-dialog-controls="back"
				type="tertiary"
				@click="currentStep = STEP.FIRST">
				{{ t('files_sharing', 'Previous step') }}
			</NcButton>

			<!-- Align right -->
			<span class="dialog__actions-separator" />

			<!-- Cancel the creation -->
			<NcButton v-if="currentStep !== STEP.LAST"
				:aria-label="t('files_sharing', 'Cancel')"
				:disabled="loading"
				:title="t('files_sharing', 'Cancel the file request creation')"
				data-cy-file-request-dialog-controls="cancel"
				type="tertiary"
				@click="onCancel">
				{{ t('files_sharing', 'Cancel') }}
			</NcButton>

			<!-- Cancel email and just close -->
			<NcButton v-else-if="emails.length !== 0"
				:aria-label="t('files_sharing', 'Close without sending emails')"
				:disabled="loading"
				:title="t('files_sharing', 'Close without sending emails')"
				data-cy-file-request-dialog-controls="cancel"
				type="tertiary"
				@click="onCancel">
				{{ t('files_sharing', 'Close') }}
			</NcButton>

			<!-- Next -->
			<NcButton v-if="currentStep !== STEP.LAST"
				:aria-label="t('files_sharing', 'Continue')"
				:disabled="loading"
				data-cy-file-request-dialog-controls="next"
				@click="onPageNext">
				<template #icon>
					<NcLoadingIcon v-if="loading" />
					<IconNext v-else :size="20" />
				</template>
				{{ t('files_sharing', 'Continue') }}
			</NcButton>

			<!-- Finish -->
			<NcButton v-else
				:aria-label="finishButtonLabel"
				:disabled="loading"
				data-cy-file-request-dialog-controls="finish"
				type="primary"
				@click="onFinish">
				<template #icon>
					<NcLoadingIcon v-if="loading" />
					<IconCheck v-else :size="20" />
				</template>
				{{ finishButtonLabel }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script lang="ts">
import type { AxiosError } from '@nextcloud/axios'
import type { Folder, Node } from '@nextcloud/files'
import type { OCSResponse } from '@nextcloud/typings/ocs'
import type { PropType } from 'vue'

import { defineComponent } from 'vue'
import { emit } from '@nextcloud/event-bus'
import { generateOcsUrl } from '@nextcloud/router'
import { Permission } from '@nextcloud/files'
import { ShareType } from '@nextcloud/sharing'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { n, t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

import IconCheck from 'vue-material-design-icons/Check.vue'
import IconNext from 'vue-material-design-icons/ArrowRight.vue'

import Config from '../services/ConfigService'
import FileRequestDatePassword from './NewFileRequestDialog/NewFileRequestDialogDatePassword.vue'
import FileRequestFinish from './NewFileRequestDialog/NewFileRequestDialogFinish.vue'
import FileRequestIntro from './NewFileRequestDialog/NewFileRequestDialogIntro.vue'
import logger from '../services/logger'
import Share from '../models/Share.ts'

enum STEP {
	FIRST = 0,
	SECOND = 1,
	LAST = 2,
}

const sharingConfig = new Config()

export default defineComponent({
	name: 'NewFileRequestDialog',

	components: {
		FileRequestDatePassword,
		FileRequestFinish,
		FileRequestIntro,
		IconCheck,
		IconNext,
		NcButton,
		NcDialog,
		NcLoadingIcon,
		NcNoteCard,
	},

	props: {
		context: {
			type: Object as PropType<Folder>,
			required: true,
		},
		content: {
			type: Array as PropType<Node[]>,
			required: true,
		},
	},

	setup() {
		return {
			STEP,
			n,
			t,

			isShareByMailEnabled: sharingConfig.isMailShareAllowed,
		}
	},

	data() {
		return {
			currentStep: STEP.FIRST,
			loading: false,

			destination: this.context.path || '/',
			label: '',
			note: '',

			expirationDate: null as Date | null,
			password: null as string | null,

			share: null as Share | null,
			emails: [] as string[],
		}
	},

	computed: {
		finishButtonLabel() {
			if (this.emails.length === 0) {
				return t('files_sharing', 'Close')
			}
			return n('files_sharing', 'Send email and close', 'Send {count} emails and close', this.emails.length, { count: this.emails.length })
		},
	},

	methods: {
		onPageNext() {
			const form = this.$refs.form as HTMLFormElement

			// Reset custom validity
			form.querySelectorAll('input').forEach(input => input.setCustomValidity(''))

			// custom destination validation
			// cannot share root
			if (this.destination === '/' || this.destination === '') {
				const destinationInput = form.querySelector('input[name="destination"]') as HTMLInputElement
				destinationInput?.setCustomValidity(t('files_sharing', 'Please select a folder, you cannot share the root directory.'))
				form.reportValidity()
				return
			}

			// If the form is not valid, show the error message
			if (!form.checkValidity()) {
				form.reportValidity()
				return
			}

			if (this.currentStep === STEP.FIRST) {
				this.currentStep = STEP.SECOND
				return
			}

			this.createShare()
		},

		onRemoveEmail(email: string) {
			const index = this.emails.indexOf(email)
			this.emails.splice(index, 1)
		},

		onCancel() {
			this.$emit('close')
		},

		async onFinish() {
			if (this.emails.length === 0 || this.isShareByMailEnabled === false) {
				showSuccess(t('files_sharing', 'File request created'))
				this.$emit('close')
				return
			}

			if (sharingConfig.isMailShareAllowed && this.emails.length > 0) {
				await this.setShareEmails()
				await this.sendEmails()
				showSuccess(n('files_sharing', 'File request created and email sent', 'File request created and {count} emails sent', this.emails.length, { count: this.emails.length }))
			} else {
				showSuccess(t('files_sharing', 'File request created'))
			}

			this.$emit('close')
		},

		async createShare() {
			this.loading = true

			let expireDate = ''
			if (this.expirationDate) {
				const year = this.expirationDate.getFullYear()
				const month = (this.expirationDate.getMonth() + 1).toString().padStart(2, '0')
				const day = this.expirationDate.getDate().toString().padStart(2, '0')

				// Format must be YYYY-MM-DD
				expireDate = `${year}-${month}-${day}`
			}
			const shareUrl = generateOcsUrl('apps/files_sharing/api/v1/shares')
			try {
				const request = await axios.post<OCSResponse>(shareUrl, {
					// Always create a file request, but without mail share
					// permissions, only a share link will be created.
					shareType: sharingConfig.isMailShareAllowed ? ShareType.Email : ShareType.Link,
					permissions: Permission.CREATE,

					label: this.label,
					path: this.destination,
					note: this.note,

					password: this.password || undefined,
					expireDate: expireDate || undefined,

					// Empty string
					shareWith: '',
					attributes: JSON.stringify([{
						value: true,
						key: 'enabled',
						scope: 'fileRequest',
					}]),
				})

				// If not an ocs request
				if (!request?.data?.ocs) {
					throw request
				}

				const share = new Share(request.data.ocs.data)
				this.share = share

				logger.info('New file request created', { share })
				emit('files_sharing:share:created', { share })

				// Move to the last page
				this.currentStep = STEP.LAST
			} catch (error) {
				const errorMessage = (error as AxiosError<OCSResponse>)?.response?.data?.ocs?.meta?.message
				showError(
					errorMessage
						? t('files_sharing', 'Error creating the share: {errorMessage}', { errorMessage })
						: t('files_sharing', 'Error creating the share'),
				)
				logger.error('Error while creating share', { error, errorMessage })
				throw error
			} finally {
				this.loading = false
			}
		},

		async setShareEmails() {
			this.loading = true

			// This should never happen™
			if (!this.share || !this.share?.id) {
				throw new Error('Share ID is missing')
			}

			const shareUrl = generateOcsUrl('apps/files_sharing/api/v1/shares/{id}', { id: this.share.id })
			try {
				// Convert link share to email share
				const request = await axios.put<OCSResponse>(shareUrl, {
					attributes: JSON.stringify([{
						value: this.emails,
						key: 'emails',
						scope: 'shareWith',
					},
					{
						value: true,
						key: 'enabled',
						scope: 'fileRequest',
					}]),
				})

				// If not an ocs request
				if (!request?.data?.ocs) {
					throw request
				}
			} catch (error) {
				this.onEmailSendError(error)
				throw error
			} finally {
				this.loading = false
			}
		},

		async sendEmails() {
			this.loading = true

			// This should never happen™
			if (!this.share || !this.share?.id) {
				throw new Error('Share ID is missing')
			}

			const shareUrl = generateOcsUrl('apps/files_sharing/api/v1/shares/{id}/send-email', { id: this.share.id })
			try {
				// Convert link share to email share
				const request = await axios.post<OCSResponse>(shareUrl, {
					password: this.password || undefined,
				})

				// If not an ocs request
				if (!request?.data?.ocs) {
					throw request
				}
			} catch (error) {
				this.onEmailSendError(error)
				throw error
			} finally {
				this.loading = false
			}
		},

		onEmailSendError(error: AxiosError<OCSResponse>) {
			const errorMessage = error.response?.data?.ocs?.meta?.message
			showError(
				errorMessage
					? t('files_sharing', 'Error sending emails: {errorMessage}', { errorMessage })
					: t('files_sharing', 'Error sending emails'),
			)
			logger.error('Error while sending emails', { error, errorMessage })
		},
	},
})
</script>

<style lang="scss">
.file-request-dialog {
	--margin: 18px;

	&__header {
		margin: 0 var(--margin);
	}

	&__form {
		position: relative;
		overflow: auto;
		padding: var(--margin) var(--margin);
		// overlap header bottom padding
		margin-top: calc(-1 * var(--margin));
	}

	fieldset {
		display: flex;
		flex-direction: column;
		width: 100%;
		margin-top: var(--margin);

		legend {
			display: flex;
			align-items: center;
			width: 100%;
		}
	}

	// Using a NcNoteCard was a bit much sometimes.
	// Using a simple paragraph instead does it.
	&__info {
		color: var(--color-text-maxcontrast);
		padding-block: 4px;
		display: flex;
		align-items: center;
		.file-request-dialog__info-icon {
			margin-inline-end: 8px;
		}
	}

	.dialog__actions {
		width: auto;
		margin-inline: 12px;
		span.dialog__actions-separator {
			margin-inline-start: auto;
		}
	}

	.input-field__helper-text-message {
		// reduce helper text standing out
		color: var(--color-text-maxcontrast);
	}
}
</style>
