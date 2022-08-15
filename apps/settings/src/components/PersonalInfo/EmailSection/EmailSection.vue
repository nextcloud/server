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
	-
-->

<template>
	<section>
		<HeaderBar :account-property="accountProperty"
			label-for="email"
			:handle-scope-change="savePrimaryEmailScope"
			:is-editable="true"
			:is-multi-value-supported="true"
			:is-valid-section="isValidSection"
			:scope.sync="primaryEmail.scope"
			@add-additional="onAddAdditionalEmail" />

		<template v-if="displayNameChangeSupported">
			<Email :primary="true"
				:scope.sync="primaryEmail.scope"
				:email.sync="primaryEmail.value"
				:active-notification-email.sync="notificationEmail"
				@update:email="onUpdateEmail"
				@update:notification-email="onUpdateNotificationEmail" />
		</template>

		<span v-else>
			{{ primaryEmail.value || t('settings', 'No email address set') }}
		</span>

		<template v-if="additionalEmails.length">
			<em class="additional-emails-label">{{ t('settings', 'Additional emails') }}</em>
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
				@delete-additional-email="onDeleteAdditionalEmail(index)" />
		</template>
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'

import Email from './Email'
import HeaderBar from '../shared/HeaderBar'

import { ACCOUNT_PROPERTY_READABLE_ENUM, DEFAULT_ADDITIONAL_EMAIL_SCOPE } from '../../../constants/AccountPropertyConstants'
import { savePrimaryEmail, savePrimaryEmailScope, removeAdditionalEmail } from '../../../service/PersonalInfo/EmailService'
import { validateEmail } from '../../../utils/validate'
import logger from '../../../logger'

const { emailMap: { additionalEmails, primaryEmail, notificationEmail } } = loadState('settings', 'personalInfoParameters', {})
const { displayNameChangeSupported } = loadState('settings', 'accountParameters', {})

export default {
	name: 'EmailSection',

	components: {
		HeaderBar,
		Email,
	},

	data() {
		return {
			accountProperty: ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL,
			additionalEmails: additionalEmails.map(properties => ({ ...properties, key: this.generateUniqueKey() })),
			displayNameChangeSupported,
			primaryEmail,
			savePrimaryEmailScope,
			notificationEmail,
		}
	},

	computed: {
		firstAdditionalEmail() {
			if (this.additionalEmails.length) {
				return this.additionalEmails[0].value
			}
			return null
		},

		isValidSection() {
			return validateEmail(this.primaryEmail.value)
				&& this.additionalEmails.map(({ value }) => value).every(validateEmail)
		},

		primaryEmailValue: {
			get() {
				return this.primaryEmail.value
			},
			set(value) {
				this.primaryEmail.value = value
			},
		},
	},

	methods: {
		onAddAdditionalEmail() {
			if (this.isValidSection) {
				this.additionalEmails.push({ value: '', scope: DEFAULT_ADDITIONAL_EMAIL_SCOPE, key: this.generateUniqueKey() })
			}
		},

		onDeleteAdditionalEmail(index) {
			this.$delete(this.additionalEmails, index)
		},

		async onUpdateEmail() {
			if (this.primaryEmailValue === '' && this.firstAdditionalEmail) {
				const deletedEmail = this.firstAdditionalEmail
				await this.deleteFirstAdditionalEmail()
				this.primaryEmailValue = deletedEmail
				await this.updatePrimaryEmail()
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
				this.handleResponse(
					'error',
					t('settings', 'Unable to update primary email address'),
					e
				)
			}
		},

		async deleteFirstAdditionalEmail() {
			try {
				const responseData = await removeAdditionalEmail(this.firstAdditionalEmail)
				this.handleDeleteFirstAdditionalEmail(responseData.ocs?.meta?.status)
			} catch (e) {
				this.handleResponse(
					'error',
					t('settings', 'Unable to delete additional email address'),
					e
				)
			}
		},

		handleDeleteFirstAdditionalEmail(status) {
			if (status === 'ok') {
				this.$delete(this.additionalEmails, 0)
			} else {
				this.handleResponse(
					'error',
					t('settings', 'Unable to delete additional email address'),
					{}
				)
			}
		},

		handleResponse(status, errorMessage, error) {
			if (status !== 'ok') {
				showError(errorMessage)
				logger.error(errorMessage, error)
			}
		},

		generateUniqueKey() {
			return Math.random().toString(36).substring(2)
		},
	},
}
</script>

<style lang="scss" scoped>
section {
	padding: 10px 10px;

	&::v-deep button:disabled {
		cursor: default;
	}

	.additional-emails-label {
		display: block;
		margin-top: 16px;
	}
}
</style>
