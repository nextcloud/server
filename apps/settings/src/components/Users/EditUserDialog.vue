<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog
		class="edit-dialog"
		size="small"
		:name="t('settings', 'Edit account')"
		outTransition
		@closing="$emit('closing')">
		<form
			id="edit-user-form"
			class="edit-dialog__form"
			data-test="form"
			:disabled="saving"
			@submit.prevent="save">
			<UserFormFields
				:fieldConfig="fieldConfig"
				:errors="fieldErrors"
				:quotaOptions="quotaOptions" />
		</form>

		<template #actions>
			<NcButton
				class="edit-dialog__submit"
				data-test="submit"
				form="edit-user-form"
				variant="primary"
				type="submit"
				:disabled="saving">
				{{ saving ? t('settings', 'Saving\u00A0…') : t('settings', 'Save') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import UserFormFields from './UserFormFields.vue'
import logger from '../../logger.ts'
import { diffPayload, userToFormData } from './userFormUtils.ts'

export default {
	name: 'EditUserDialog',

	components: {
		NcButton,
		NcDialog,
		UserFormFields,
	},

	// Children inject this reactive object and mutate its properties via v-model.
	// Do not reassign editedUser entirely, the injected reference would go stale.
	provide() {
		return {
			formData: this.editedUser,
		}
	},

	props: {
		user: {
			type: Object,
			required: true,
		},

		quotaOptions: {
			type: Array,
			required: true,
		},
	},

	emits: ['closing'],

	data() {
		const allGroups = this.$store.getters.getGroups
		const serverLanguages = this.$store.getters.getServerData.languages
		const formData = userToFormData(this.user, allGroups, this.quotaOptions, serverLanguages)
		return {
			/** Snapshot of initial state for diffing */
			initialData: structuredClone(formData),
			/** Mutable form state */
			editedUser: formData,
			saving: false,
			fieldErrors: {},
		}
	},

	computed: {
		settings() {
			return this.$store.getters.getServerData
		},

		fieldConfig() {
			return {
				username: {
					show: true,
					disabled: true,
					label: t('settings', 'Account name'),
				},

				password: {
					show: this.settings.canChangePassword && this.user.backendCapabilities.setPassword,
					label: t('settings', 'New password'),
				},
			}
		},
	},

	methods: {
		async save() {
			this.fieldErrors = {}

			const payload = diffPayload(this.initialData, this.editedUser)
			if (Object.keys(payload).length === 0) {
				this.$emit('closing')
				return
			}

			this.saving = true
			try {
				await confirmPassword()
				await this.$store.dispatch('editUserMultiField', {
					userid: this.user.id,
					payload,
				})
				showSuccess(t('settings', 'Account updated'))
				this.$emit('closing')
			} catch (error) {
				const errors = error.response?.data?.ocs?.data?.errors
				if (errors && typeof errors === 'object') {
					this.fieldErrors = errors
				} else {
					logger.error('Failed to update account', { error })
					showError(t('settings', 'Failed to update account'))
				}
			} finally {
				this.saving = false
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.edit-dialog {
	&__form {
		padding: 0 8px;
	}

	&__submit {
		margin-top: 4px;
		margin-bottom: 8px;
	}

	:deep {
		.dialog__actions {
			margin: auto;
		}
	}
}
</style>
