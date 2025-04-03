<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section class="section-emails">
		<HeaderBar :input-id="inputId"
			:readable="primaryEmail.readable"
			:is-editable="true"
			:is-multi-value-supported="true"
			:is-valid-section="isValidSection"
			:scope.sync="primaryEmail.scope"
			@add-additional="onAddAdditionalEmail" />

		<template v-if="emailChangeSupported">
			<Email :input-id="inputId"
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

		<template v-if="additionalEmails.length">
			<!-- TODO use unique key for additional email when uniqueness can be guaranteed, see https://github.com/nextcloud/server/issues/26866 -->
			<Email v-for="(additionalEmail, index) in additionalEmails"
				:key="additionalEmail.key"
				class="section-emails__additional-email"
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

import Email from './Email.vue'
import HeaderBar from '../shared/HeaderBar.vue'

import { ACCOUNT_PROPERTY_READABLE_ENUM, DEFAULT_ADDITIONAL_EMAIL_SCOPE, NAME_READABLE_ENUM } from '../../../constants/AccountPropertyConstants.js'
import { savePrimaryEmail, removeAdditionalEmail } from '../../../service/PersonalInfo/EmailService.js'
import { validateEmail } from '../../../utils/validate.js'
import { handleError } from '../../../utils/handlers.ts'

const { emailMap: { additionalEmails, primaryEmail, notificationEmail } } = loadState('settings', 'personalInfoParameters', {})
const { emailChangeSupported } = loadState('settings', 'accountParameters', {})

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
			emailChangeSupported,
			primaryEmail: { ...primaryEmail, readable: NAME_READABLE_ENUM[primaryEmail.name] },
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

		inputId() {
			return `account-property-${this.primaryEmail.name}`
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
					e,
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
					e,
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
					{},
				)
			}
		},

		handleResponse(status, errorMessage, error) {
			if (status !== 'ok') {
				handleError(error, errorMessage)
			}
		},

		generateUniqueKey() {
			return Math.random().toString(36).substring(2)
		},
	},
}
</script>

<style lang="scss" scoped>
.section-emails {
	padding: 10px 10px;

	&__additional-email {
		margin-top: calc(var(--default-grid-baseline) * 3);
	}
}
</style>
