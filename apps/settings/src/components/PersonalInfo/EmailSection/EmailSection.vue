<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section class="section-emails">
		<template v-if="emailChangeSupported">
			<EmailSectionEntry
				:input-id="inputId"
				primary
				:scope.sync="primaryEmail.scope"
				:email.sync="primaryEmail.value"
				:active-notification-email.sync="notificationEmail"
				@update:email="onUpdateEmail"
				@update:notification-email="onUpdateNotificationEmail" />

			<!-- TODO use unique key for additional email when uniqueness can be guaranteed, see https://github.com/nextcloud/server/issues/26866 -->
			<EmailSectionEntry
				v-for="(additionalEmail, index) in additionalEmails"
				:key="additionalEmail.key"
				:index="index"
				:scope.sync="additionalEmail.scope"
				:email.sync="additionalEmail.value"
				:local-verification-state="parseInt(additionalEmail.locallyVerified, 10)"
				:active-notification-email.sync="notificationEmail"
				@update:email="onUpdateEmail"
				@update:notification-email="onUpdateNotificationEmail"
				@delete-additional-email="onDeleteAdditionalEmail(index)" />

			<NcFormBox class="section-emails__add">
				<NcFormBoxButton
					:label="t('settings', 'Additional address')"
					:disabled="!isValidSection"
					@click="onAddAdditionalEmail">
					<template #icon>
						<Plus :size="20" />
					</template>
				</NcFormBoxButton>
			</NcFormBox>
		</template>

		<span v-else>
			{{ primaryEmail.value || t('settings', 'No email address set') }}
		</span>
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import Plus from 'vue-material-design-icons/Plus.vue'
import EmailSectionEntry from './EmailSectionEntry.vue'
import { DEFAULT_ADDITIONAL_EMAIL_SCOPE } from '../../../constants/AccountPropertyConstants.js'
import { removeAdditionalEmail, savePrimaryEmail } from '../../../service/PersonalInfo/EmailService.js'
import { handleError } from '../../../utils/handlers.ts'
import { validateEmail } from '../../../utils/validate.js'

const { emailMap: { additionalEmails, primaryEmail, notificationEmail } } = loadState('settings', 'personalInfoParameters', {})
const { emailChangeSupported } = loadState('settings', 'accountParameters', {})

export default {
	name: 'EmailSection',

	components: {
		EmailSectionEntry,
		NcFormBox,
		NcFormBoxButton,
		Plus,
	},

	data() {
		return {
			additionalEmails: additionalEmails.map((properties) => ({ ...properties, key: this.generateUniqueKey() })),
			emailChangeSupported,
			primaryEmail: { ...primaryEmail },
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
	display: flex;
	flex-direction: column;
	gap: 6px;
	padding: 6px 0;

	&__add {
		margin-inline-end: 52px;
	}
}
</style>
