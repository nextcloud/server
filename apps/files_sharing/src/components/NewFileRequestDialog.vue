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
			data-cy-file-request-dialog-form
			@submit.prevent.stop="onSubmit">
			<FileRequestIntro v-if="currentStep === STEP.FIRST"
				:context="context"
				:destination.sync="destination"
				:disabled="loading"
				:label.sync="label"
				:note.sync="note" />

			<FileRequestDatePassword v-else-if="currentStep === STEP.SECOND"
				:deadline.sync="deadline"
				:disabled="loading"
				:password.sync="password" />

			<FileRequestFinish v-else-if="share"
				:emails="emails"
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
				{{ continueButtonLabel }}
			</NcButton>

			<!-- Finish -->
			<NcButton v-else
				:aria-label="t('files_sharing', 'Close the creation dialog')"
				data-cy-file-request-dialog-controls="finish"
				type="primary"
				@click="$emit('close')">
				<template #icon>
					<IconCheck :size="20" />
				</template>
				{{ finishButtonLabel }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script lang="ts">
import type { AxiosError } from 'axios'
import type { Folder, Node } from '@nextcloud/files'
import type { OCSResponse } from '@nextcloud/typings/ocs'
import type { PropType } from 'vue'

import { defineComponent } from 'vue'
import { emit } from '@nextcloud/event-bus'
import { generateOcsUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'
import { translate, translatePlural } from '@nextcloud/l10n'
import { Type } from '@nextcloud/sharing'
import axios from '@nextcloud/axios'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

import IconCheck from 'vue-material-design-icons/Check.vue'
import IconNext from 'vue-material-design-icons/ArrowRight.vue'

import FileRequestDatePassword from './NewFileRequestDialog/FileRequestDatePassword.vue'
import FileRequestFinish from './NewFileRequestDialog/FileRequestFinish.vue'
import FileRequestIntro from './NewFileRequestDialog/FileRequestIntro.vue'
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
			n: translatePlural,
			t: translate,
			STEP,
		}
	},

	data() {
		return {
			currentStep: STEP.FIRST,
			loading: false,

			destination: this.context.path || '/',
			label: '',
			note: '',

			deadline: null as Date | null,
			password: null as string | null,

			share: null as Share | null,
			emails: [] as string[],
		}
	},

	computed: {
		continueButtonLabel() {
			if (this.currentStep === STEP.LAST) {
				return this.t('files_sharing', 'Close')
			}
			return this.t('files_sharing', 'Continue')
		},

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

		onSubmit() {
			this.$emit('submit')
		},

		async createShare() {
			this.loading = true

			const shareUrl = generateOcsUrl('apps/files_sharing/api/v1/shares')
			// Format must be YYYY-MM-DD
			const expireDate = this.deadline ? this.deadline.toISOString().split('T')[0] : undefined
			try {
				const request = await axios.post(shareUrl, {
					path: this.destination,
					shareType: Type.SHARE_TYPE_EMAIL,
					publicUpload: 'true',
					password: this.password || undefined,
					expireDate,
					label: this.label,
					attributes: JSON.stringify({ is_file_request: true })
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
