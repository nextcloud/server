<!--
	- @copyright 2021, Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
	-
	- @license GNU AGPL version 3 or any later version
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
-->

<template>
	<form
		ref="form"
		class="section"
		@submit.stop.prevent="() => {}">
		<HeaderBar
			:is-valid-form="isValidForm"
			:scope.sync="primaryEmail.scope"
			@addAdditionalEmail="onAddAdditionalEmail" />

		<template v-if="displayNameChangeSupported">
			<Email
				:primary="true"
				:scope.sync="primaryEmail.scope"
				:email.sync="primaryEmail.value"
				:active-notification-email.sync="notificationEmail"
				@update:email="onUpdateEmail"
				@update:notification-email="onUpdateNotificationEmail" />
		</template>
		<span v-else>
			{{ primaryEmail.value || t('settings', 'No email address set') }}
		</span>
		<!-- TODO use unique key for additional email when uniqueness can be guaranteed, see https://github.com/nextcloud/server/issues/26866 -->
		<Email v-for="(additionalEmail, index) in additionalEmails"
			:key="additionalEmail.key"
			:index="index"
			:scope.sync="additionalEmail.scope"
			:email.sync="additionalEmail.value"
			:local-verification-state="parseInt(additionalEmail.locallyVerified, 10)"
			:active-notification-email.sync="notificationEmail"
			@update:email="onUpdateEmail"
			@update:notification-email="onUpdateNotificationEmail"
			@deleteAdditionalEmail="onDeleteAdditionalEmail(index)" />
	</form>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'
import '@nextcloud/dialogs/styles/toast.scss'

import HeaderBar from './HeaderBar'
import Email from './Email'
import { savePrimaryEmail, removeAdditionalEmail } from '../../../service/PersonalInfoService'
import { DEFAULT_ADDITIONAL_EMAIL_SCOPE } from '../../../constants/AccountPropertyConstants'

const { additionalEmails, primaryEmail, notificationEmail } = loadState('settings', 'emails', {})
const { displayNameChangeSupported } = loadState('settings', 'accountParameters', {})

export default {
	name: 'EmailSection',

	components: {
		HeaderBar,
		Email,
	},

	data() {
		return {
			additionalEmails: additionalEmails.map(properties => ({ ...properties, key: this.generateUniqueKey() })),
			displayNameChangeSupported,
			primaryEmail,
			isValidForm: true,
			notificationEmail,
		}
	},

	computed: {
		primaryEmailValue: {
			get() {
				return this.primaryEmail.value
			},
			set(value) {
				this.primaryEmail.value = value
			},
		},

		firstAdditionalEmail() {
			if (this.additionalEmails.length) {
				return this.additionalEmails[0].value
			}
			return null
		},
	},

	mounted() {
		this.$nextTick(() => this.updateFormValidity())
	},

	methods: {
		onAddAdditionalEmail() {
			if (this.$refs.form?.checkValidity()) {
				this.additionalEmails.push({ value: '', scope: DEFAULT_ADDITIONAL_EMAIL_SCOPE, key: this.generateUniqueKey() })
				this.$nextTick(() => this.updateFormValidity())
			}
		},

		onDeleteAdditionalEmail(index) {
			this.$delete(this.additionalEmails, index)
			this.$nextTick(() => this.updateFormValidity())
		},

		async onUpdateEmail() {
			this.$nextTick(() => this.updateFormValidity())

			if (this.primaryEmailValue === '' && this.firstAdditionalEmail) {
				const deletedEmail = this.firstAdditionalEmail
				await this.deleteFirstAdditionalEmail()
				this.primaryEmailValue = deletedEmail
				await this.updatePrimaryEmail()
				this.$nextTick(() => this.updateFormValidity())
			}
		},

		async onUpdateNotificationEmail(email) {
			this.notificationEmail = email
		},

		async updatePrimaryEmail() {
			try {
				const responseData = await savePrimaryEmail(this.primaryEmailValue)
				this.handleResponse(responseData.ocs?.meta?.status)
			} catch (e) {
				this.handleResponse('error', 'Unable to update primary email address', e)
			}
		},

		async deleteFirstAdditionalEmail() {
			try {
				const responseData = await removeAdditionalEmail(this.firstAdditionalEmail)
				this.handleDeleteFirstAdditionalEmail(responseData.ocs?.meta?.status)
			} catch (e) {
				this.handleResponse('error', 'Unable to delete additional email address', e)
			}
		},

		handleDeleteFirstAdditionalEmail(status) {
			if (status === 'ok') {
				this.$delete(this.additionalEmails, 0)
			} else {
				this.handleResponse('error', 'Unable to delete additional email address', {})
			}
		},

		handleResponse(status, errorMessage, error) {
			if (status !== 'ok') {
				showError(t('settings', errorMessage))
				this.logger.error(errorMessage, error)
			}
		},

		updateFormValidity() {
			this.isValidForm = this.$refs.form?.checkValidity()
		},

		generateUniqueKey() {
			return Math.random().toString(36).substring(2)
		},
	},
}
</script>

<style lang="scss" scoped>
	form::v-deep button {
		&:disabled {
			cursor: default;
		}
	}
</style>
