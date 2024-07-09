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
				{{ t('files_sharing', 'Collect files from others even if they don\'t have an account.') }}
				{{ t('files_sharing', 'To ensure you can receive files, verify you have enough storage available.') }}
			</p>
		</NcNoteCard>

		<!-- Main form -->
		<form ref="form"
			class="file-request-dialog__form"
			aria-labelledby="file-request-dialog-description"
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
				:isShareByMailEnabled="isShareByMailEnabled"
				:share="share"
				@add-email="email => emails.push(email)"
				@remove-email="onRemoveEmail" />
		</form>

		<!-- Controls -->
		<template #actions>
			<!-- Cancel the creation -->
			<NcButton :aria-label="t('files_sharing', 'Cancel')"
				:disabled="loading"
				:title="t('files_sharing', 'Cancel the file request creation')"
				data-cy-file-request-dialog-controls="cancel"
				type="tertiary"
				@click="onCancel">
				{{ t('files_sharing', 'Cancel') }}
			</NcButton>

			<!-- Align right -->
			<span class="dialog__actions-separator" />

			<!-- Back -->
			<NcButton v-show="currentStep === STEP.SECOND"
				:aria-label="t('files_sharing', 'Previous step')"
				:disabled="loading"
				data-cy-file-request-dialog-controls="back"
				type="tertiary"
				@click="currentStep = STEP.FIRST">
				{{ t('files_sharing', 'Previous') }}
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
import type { AxiosError } from 'axios'
import { Permission, type Folder, type Node } from '@nextcloud/files'
import type { OCSResponse } from '@nextcloud/typings/ocs'
import type { PropType } from 'vue'

import { defineComponent } from 'vue'
import { emit } from '@nextcloud/event-bus'
import { generateOcsUrl } from '@nextcloud/router'
import { getCapabilities } from '@nextcloud/capabilities'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate, translatePlural } from '@nextcloud/l10n'
import { Type } from '@nextcloud/sharing'
import axios from '@nextcloud/axios'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

import IconCheck from 'vue-material-design-icons/Check.vue'
import IconNext from 'vue-material-design-icons/ArrowRight.vue'

import FileRequestDatePassword from './NewFileRequestDialog/NewFileRequestDialogDatePassword.vue'
import FileRequestFinish from './NewFileRequestDialog/NewFileRequestDialogFinish.vue'
import FileRequestIntro from './NewFileRequestDialog/NewFileRequestDialogIntro.vue'
import Share from '../models/Share'
import logger from '../services/logger'

enum STEP {
	FIRST = 0,
	SECOND = 1,
	LAST = 2,
}

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

			n: translatePlural,
			t: translate,

			isShareByMailEnabled: getCapabilities()?.files_sharing?.sharebymail?.enabled === true
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
				return this.t('files_sharing', 'Close')
			}
			return this.n('files_sharing', 'Close and send email', 'Close and send {count} emails', this.emails.length, { count: this.emails.length })
		},
	},

	methods: {
		onPageNext() {
			const form = this.$refs.form as HTMLFormElement
			if (!form.checkValidity()) {
				form.reportValidity()
			}

			// custom destination validation
			// cannot share root
			if (this.destination === '/' || this.destination === '') {
				const destinationInput = form.querySelector('input[name="destination"]') as HTMLInputElement
				destinationInput?.setCustomValidity(this.t('files_sharing', 'Please select a folder, you cannot share the root directory.'))
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
				showSuccess(this.t('files_sharing', 'File request created'))
				this.$emit('close')
				return
			}

			await this.setShareEmails()
			await this.sendEmails()
			showSuccess(this.t('files_sharing', 'File request created and emails sent'))
			this.$emit('close')
		},

		async createShare() {
			this.loading = true

			// Format must be YYYY-MM-DD
			const expireDate = this.expirationDate ? this.expirationDate.toISOString().split('T')[0] : undefined
			const shareUrl = generateOcsUrl('apps/files_sharing/api/v1/shares')
			try {
				const request = await axios.post<OCSResponse>(shareUrl, {
					shareType: Type.SHARE_TYPE_EMAIL,
					permissions: Permission.CREATE,
	
					label: this.label,
					path: this.destination,
					note: this.note,

					password: this.password || undefined,
					expireDate,

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
						? this.t('files_sharing', 'Error creating the share: {errorMessage}', { errorMessage })
						: this.t('files_sharing', 'Error creating the share'),
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

			const shareUrl = generateOcsUrl('apps/files_sharing/api/v1/shares/' + this.share.id)
			try {
				// Convert link share to email share
				const request = await axios.put<OCSResponse>(shareUrl, {
					attributes: JSON.stringify([{
						value: this.emails,
						key: 'emails',
						scope: 'shareWith',
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

		async sendEmails () {
			this.loading = true

			// This should never happen™
			if (!this.share || !this.share?.id) {
				throw new Error('Share ID is missing')
			}

			const shareUrl = generateOcsUrl('apps/files_sharing/api/v1/shares/' + this.share.id + '/send-email')
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

		onEmailSendError(error: AxiosError<OCSResponse>|any) {
			const errorMessage = error.response?.data?.ocs?.meta?.message
			showError(
				errorMessage
					? this.t('files_sharing', 'Error sending emails: {errorMessage}', { errorMessage })
					: this.t('files_sharing', 'Error sending emails'),
			)
			logger.error('Error while sending emails', { error, errorMessage })
		},
	},
})
</script>

<style scoped lang="scss">
.file-request-dialog {
	--margin: 36px;
	--secondary-margin: 18px;

	&__header {
		margin: 0 var(--margin);
	}

	&__form {
		position: relative;
		overflow: auto;
		padding: var(--secondary-margin) var(--margin);
		// overlap header bottom padding
		margin-top: calc(-1 * var(--secondary-margin));
	}

	:deep(fieldset) {
		display: flex;
		flex-direction: column;
		width: 100%;
		margin-top: var(--secondary-margin);

		:deep(legend) {
			display: flex;
			align-items: center;
			width: 100%;
		}
	}

	:deep(.dialog__actions) {
		width: auto;
		margin-inline: 12px;
		// align left and remove margin
		margin-left: 0;
		span.dialog__actions-separator {
			margin-left: auto;
		}
	}

	:deep(.input-field__helper-text-message) {
		// reduce helper text standing out
		color: var(--color-text-maxcontrast);
	}
}
</style>
