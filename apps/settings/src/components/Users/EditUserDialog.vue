<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog
		class="edit-dialog"
		size="small"
		:name="t('settings', 'Edit account') + ' - ' + user.id"
		out-transition
		@closing="$emit('closing')">
		<form
			id="edit-user-form"
			class="edit-dialog__form"
			data-test="form"
			:disabled="saving"
			@submit.prevent="save">
			<UserFormFields
				:formData="editedUser"
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
import { formatFileSize } from '@nextcloud/files'
import { confirmPassword } from '@nextcloud/password-confirmation'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import UserFormFields from './UserFormFields.vue'
import logger from '../../logger.ts'
import { unlimitedQuota } from '../../utils/userUtils.ts'

/**
 * Maps a user store object to the flat, API-aligned shape used by the form.
 * Keeps a clean separation between the store model (e.g. `user.displayname`,
 * `user.quota.quota`) and the form model (e.g. `displayName`, `quota`).
 *
 * @param {object} user The user store object
 * @param {Array} allGroups All available groups from the store
 * @param {Array} quotaOptions Quota preset options
 * @param {object} serverLanguages Server language configuration
 * @return {object} Form-ready data object
 */
function userToFormData(user, allGroups, quotaOptions, serverLanguages) {
	const groups = user.groups
		.map((id) => allGroups.find((g) => g.id === id))
		.filter(Boolean)

	const subadminGroups = (user.subadmin ?? [])
		.map((id) => allGroups.find((g) => g.id === id))
		.filter(Boolean)

	let quota
	if (user.quota?.quota >= 0) {
		const label = formatFileSize(user.quota.quota)
		quota = quotaOptions.find((q) => q.id === label) ?? { id: label, label }
	} else if (user.quota?.quota === 'default') {
		quota = quotaOptions[0]
	} else {
		quota = unlimitedQuota
	}

	return {
		username: user.id,
		displayName: user.displayname ?? '',
		password: '',
		email: user.email ?? '',
		groups,
		subadminGroups,
		quota,
		language: resolveLanguage(user, serverLanguages),
		manager: user.manager ?? '',
	}
}

/**
 * Resolves the user's language code to a { code, name } object.
 *
 * @param {object} user The user store object
 * @param {object} serverLanguages Server language configuration
 * @return {object} Language object with code and name
 */
function resolveLanguage(user, serverLanguages) {
	if (!user.language || user.language === '') {
		return { code: '', name: '' }
	}
	// Look up the display name from the server languages list
	const allLangs = [
		...(serverLanguages?.commonLanguages ?? []),
		...(serverLanguages?.otherLanguages ?? []),
	]
	const match = allLangs.find((lang) => lang.code === user.language)
	if (match) {
		return match
	}
	return { code: user.language, name: user.language }
}

/**
 * Generic shallow diff between initial and current form data.
 * Returns only fields that changed, with API-ready values.
 *
 * @param {object} initial Snapshot of form data at mount time
 * @param {object} current Current form data state
 * @return {object} Changed fields with API-ready values
 */
function diffPayload(initial, current) {
	const payload = {}

	if (current.displayName !== initial.displayName) {
		payload.displayName = current.displayName
	}
	if (current.password !== '') {
		payload.password = current.password
	}
	if (current.email !== initial.email) {
		payload.email = current.email
	}
	if (current.quota.id !== initial.quota.id) {
		payload.quota = current.quota.id
	}
	if (current.language.code !== initial.language.code) {
		payload.language = current.language.code
	}
	const currentManagerId = typeof current.manager === 'object' ? (current.manager.id ?? '') : current.manager
	const initialManagerId = typeof initial.manager === 'object' ? (initial.manager.id ?? '') : initial.manager
	if (currentManagerId !== initialManagerId) {
		payload.manager = currentManagerId
	}

	const currentGroupIds = current.groups.map((g) => g.id).sort()
	const initialGroupIds = initial.groups.map((g) => g.id).sort()
	if (JSON.stringify(currentGroupIds) !== JSON.stringify(initialGroupIds)) {
		payload.groups = currentGroupIds
	}

	const currentSubadminIds = current.subadminGroups.map((g) => g.id).sort()
	const initialSubadminIds = initial.subadminGroups.map((g) => g.id).sort()
	if (JSON.stringify(currentSubadminIds) !== JSON.stringify(initialSubadminIds)) {
		payload.subadminGroups = currentSubadminIds
	}

	return payload
}

export default {
	name: 'EditUserDialog',

	components: {
		NcButton,
		NcDialog,
		UserFormFields,
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
