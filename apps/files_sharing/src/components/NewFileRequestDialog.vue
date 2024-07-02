<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog can-close
		class="file-request-dialog"
		data-cy-file-request-dialog
		:close-on-click-outside="false"
		:name="t('files_sharing', 'Create a file request')"
		size="normal"
		@closing="onCancel">
		<!-- Header -->
		<NcNoteCard v-show="currentStep === STEP.FIRST" type="info" class="file-request-dialog__header">
			<p id="file-request-dialog-description" class="file-request-dialog__description">
				{{ t('files_sharing', 'You can use file requests to collect files from others, even if they don\'t have an account.') }}
				{{ t('files_sharing', 'The files will be saved in a folder of your choice.') }}
				{{ t('files_sharing', 'To ensure you can receive the necessary files, please verify your available storage capacity') }}
			</p>
		</NcNoteCard>

		<!-- Main form -->
		<form ref="form"
			class="file-request-dialog__form"
			aria-labelledby="file-request-dialog-description"
			data-cy-file-request-dialog-form
			@submit.prevent.stop="onSubmit">
			<template v-if="currentStep === STEP.FIRST">
				<!-- Request label -->
				<fieldset class="file-request-dialog__label" data-cy-file-request-dialog-fieldset="label">
					<legend>
						{{ t('files_sharing', 'What are you requesting ?') }}
					</legend>
					<NcTextField :value.sync="label"
						:label-outside="true"
						:placeholder="t('files_sharing', 'Birthday party photos, History assignment…')"
						:required="false" />
				</fieldset>

				<!-- Request destination -->
				<fieldset class="file-request-dialog__destination" data-cy-file-request-dialog-fieldset="destination">
					<legend>
						{{ t('files_sharing', 'Where should these files go ?') }}
					</legend>
					<NcTextField :value.sync="destinationPath"
						:helper-text="t('files_sharing', 'The uploaded files are visible only to you unless you choose to share them.')"
						:label-outside="true"
						:placeholder="t('files_sharing', 'Select a destination')"
						:readonly="true"
						:required="false"
						:show-trailing-button="destinationPath !== context.path"
						:trailing-button-icon="'undo'"
						:trailing-button-label="t('files_sharing', 'Revert to default')"
						@click="onPickDestination"
						@trailing-button-click="destination = ''">
						<IconFolder :size="18" />
					</NcTextField>
				</fieldset>
			</template>

			<template v-else-if="currentStep === STEP.SECOND">
				<!-- Expiration date -->
				<fieldset class="file-request-dialog__expiration" data-cy-file-request-dialog-fieldset="expiration">
					<NcNoteCard v-if="defaultExpireDateEnforced" type="info">
						{{ t('files_sharing', 'Your administrator has enforced a default expiration date with a maximum {days} days.', { days: defaultExpireDate }) }}
					</NcNoteCard>

					<!-- Enable expiration -->
					<legend>{{ t('files_sharing', 'When should the request expire ?') }}</legend>
					<NcCheckboxRadioSwitch v-show="!defaultExpireDateEnforced"
						:checked="defaultExpireDateEnforced || deadline !== null"
						:disabled="defaultExpireDateEnforced"
						@update:checked="onToggleDeadline">
						{{ t('files_sharing', 'Set a submission deadline') }}
					</NcCheckboxRadioSwitch>

					<!-- Date picker -->
					<NcDateTimePickerNative v-if="deadline !== null"
						id="file-request-dialog-deadline"
						:hide-label="true"
						:placeholder="t('files_sharing', 'Select a date')"
						:max="maxDate"
						:min="minDate"
						:required="defaultExpireDateEnforced"
						:type="'date'"
						:value.sync="deadline" />
				</fieldset>

				<!-- Password -->
				<fieldset class="file-request-dialog__password" data-cy-file-request-dialog-fieldset="password">
					<NcNoteCard v-if="enforcePasswordForPublicLink" type="info">
						{{ t('files_sharing', 'Your administrator has enforced a password protection.') }}
					</NcNoteCard>

					<!-- Enable password -->
					<legend>{{ t('files_sharing', 'What password should be used for the request ?') }}</legend>
					<NcCheckboxRadioSwitch v-show="!enforcePasswordForPublicLink"
						:checked="enforcePasswordForPublicLink || password !== null"
						:disabled="enforcePasswordForPublicLink"
						@update:checked="onTogglePassword">
						{{ t('files_sharing', 'Set a password') }}
					</NcCheckboxRadioSwitch>

					<div v-if="password !== null" class="file-request-dialog__password-field">
						<NcPasswordField ref="passwordField"
							:value.sync="password"
							:check-password-strength="true"
							:label-outside="true"
							:placeholder="t('files_sharing', 'Enter a valid password')"
							:required="false" />
						<NcButton :aria-label="t('files_sharing', 'Generate a new password')"
							:title="t('files_sharing', 'Generate a new password')"
							type="tertiary-no-background"
							@click="generatePassword(); showPassword()">
							<template #icon>
								<IconPasswordGen :size="20" />
							</template>
						</NcButton>
					</div>
				</fieldset>
			</template>
		</form>

		<!-- Controls -->
		<template #actions>
			<!-- Cancel the creation -->
			<NcButton :aria-label="t('files_sharing', 'Cancel')"
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
				data-cy-file-request-dialog-controls="back"
				type="tertiary"
				@click="currentStep = STEP.FIRST">
				{{ t('files_sharing', 'Previous') }}
			</NcButton>

			<!-- Next -->
			<NcButton v-if="currentStep !== STEP.LAST"
				:aria-label="t('files_sharing', 'Continue')"
				data-cy-file-request-dialog-controls="next"
				@click="onPageNext">
				<template #icon>
					<IconNext :size="20" />
				</template>
				{{ currentStep === STEP.FIRST ? t('files_sharing', 'Continue') : t('files_sharing', 'Create') }}
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
				{{ t('files_sharing', 'Finish') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script lang="ts">
import type { PropType } from 'vue'
import type { Folder, Node } from '@nextcloud/files'

import { defineComponent } from 'vue'
import { getFilePickerBuilder } from '@nextcloud/dialogs'
import { translate } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcDateTimePickerNative from '@nextcloud/vue/dist/Components/NcDateTimePickerNative.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import IconCheck from 'vue-material-design-icons/Check.vue'
import IconFolder from 'vue-material-design-icons/Folder.vue'
import IconNext from 'vue-material-design-icons/ArrowRight.vue'
import IconPasswordGen from 'vue-material-design-icons/AutoFix.vue'

import GeneratePassword from '../utils/GeneratePassword'

enum STEP {
	FIRST = 0,
	SECOND = 1,
	LAST = 2,
}

export default defineComponent({
	name: 'NewFileRequestDialog',

	components: {
		IconCheck,
		IconFolder,
		IconNext,
		IconPasswordGen,
		NcButton,
		NcCheckboxRadioSwitch,
		NcDateTimePickerNative,
		NcDialog,
		NcNoteCard,
		NcTextField,
		NcPasswordField,
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
			t: translate,
			STEP,

			// Default expiration date if defaultExpireDateEnabled is true
			defaultExpireDate: window.OC.appConfig.core.defaultExpireDate as number,
			// Default expiration date is enabled for public links (can be disabled)
			defaultExpireDateEnabled: window.OC.appConfig.core.defaultExpireDateEnabled === true,
			// Default expiration date is enforced for public links (can't be disabled)
			defaultExpireDateEnforced: window.OC.appConfig.core.defaultExpireDateEnforced === true,

			// Default password protection is enabled for public links (can be disabled)
			enableLinkPasswordByDefault: window.OC.appConfig.core.enableLinkPasswordByDefault === true,
			// Password protection is enforced for public links (can't be disabled)
			enforcePasswordForPublicLink: window.OC.appConfig.core.enforcePasswordForPublicLink === true,
		}
	},

	data() {
		return {
			currentStep: STEP.FIRST,
			label: '',
			destination: '',
			deadline: null as Date | null,
			password: null as string | null,

			maxDate: null as Date | null,
			minDate: new Date(new Date().setDate(new Date().getDate() + 1)),
		}
	},

	computed: {
		destinationPath: {
			get(): string {
				return this.destination
					|| this.context.path
					|| '/'
			},
			set(value: string) {
				this.destination = value
			},
		},
	},

	mounted() {
		// If defined, we set the default expiration date
		if (this.defaultExpireDate > 0) {
			this.deadline = new Date(new Date().setDate(new Date().getDate() + this.defaultExpireDate))
		}

		// If enforced, we cannot set a date before the default expiration days (see admin settings)
		if (this.defaultExpireDateEnforced) {
			this.maxDate = new Date(new Date().setDate(new Date().getDate() + this.defaultExpireDate))
		}

		// If enabled by default, we generate a valid password
		if (this.enableLinkPasswordByDefault) {
			this.generatePassword()
		}
	},

	methods: {
		onPageNext() {
			const form = this.$refs.form as HTMLFormElement
			if (!form.checkValidity()) {
				form.reportValidity()
			}

			if (this.currentStep === STEP.FIRST) {
				this.currentStep = STEP.SECOND
				return
			}

			this.currentStep = STEP.LAST
		},

		onPickDestination() {
			const filepicker = getFilePickerBuilder(this.t('files_sharing', 'Select a destination'))
				.addMimeTypeFilter('httpd/unix-directory')
				.allowDirectories(true)
				.addButton({
					label: this.t('files_sharing', 'Select'),
					callback: this.onPickedDestination,
				})
				.setFilter(node => node.path !== '/')
				.startAt(this.destinationPath)
				.build()
			try {
				filepicker.pick()
			} catch (e) {
				// ignore cancel
			}
		},
		onPickedDestination(nodes: Node[]) {
			const node = nodes[0]
			if (node) {
				this.destination = node.path
			}
		},

		onToggleDeadline(checked: boolean) {
			this.deadline = checked ? new Date() : null
		},

		async onTogglePassword(checked: boolean) {
			if (checked) {
				this.generatePassword()
				return
			}
			this.password = null
		},

		generatePassword() {
			GeneratePassword().then(password => {
				this.password = password
			})
		},

		showPassword() {
			// @ts-expect-error isPasswordHidden is private
			this.$refs.passwordField.isPasswordHidden = false
		},

		onCancel() {
			this.$emit('close')
		},

		onSubmit() {
			this.$emit('submit')
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
		padding: 0 var(--margin);
		// overlap header bottom padding
		margin-top: calc(-1 * var(--secondary-margin));
		padding-bottom: var(--margin);
	}

	fieldset {
		display: flex;
		flex-direction: column;
		width: 100%;
		margin-top: calc(var(--secondary-margin) * 1.5);

		:deep(legend) {
			display: flex;
			align-items: center;
			width: 100%;
		}

		.file-request-dialog__password-field {
			display: flex;
			align-items: flex-start;
			gap: 8px;
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
