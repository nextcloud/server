<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section class="section-phones">
		<HeaderBar
			:input-id="inputId"
			:readable="primaryPhone.readable"
			:is-editable="true"
			:is-multi-value-supported="true"
			:is-valid-section="isValidSection"
			:scope.sync="primaryPhone.scope"
			@add-additional="onAddAdditionalPhone" />

		<PhoneSectionEntry
			:input-id="inputId"
			primary
			:scope.sync="primaryPhone.scope"
			:phone.sync="primaryPhone.value"
			@update:phone="onUpdatePhone" />

		<template v-if="additionalPhones.length">
			<PhoneSectionEntry
				v-for="(additionalPhone, index) in additionalPhones"
				:key="additionalPhone.key"
				class="section-phones__additional-phone"
				:index="index"
				:scope.sync="additionalPhone.scope"
				:phone.sync="additionalPhone.value"
				@update:phone="onUpdatePhone"
				@delete-additional-phone="onDeleteAdditionalPhone(index)" />
		</template>
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { isValidPhoneNumber } from 'libphonenumber-js'
import HeaderBar from '../shared/HeaderBar.vue'
import PhoneSectionEntry from './PhoneSectionEntry.vue'
import { ACCOUNT_PROPERTY_READABLE_ENUM, DEFAULT_ADDITIONAL_PHONE_SCOPE, NAME_READABLE_ENUM } from '../../../constants/AccountPropertyConstants.js'
import { removeAdditionalPhone, savePrimaryPhone } from '../../../service/PersonalInfo/PhoneService.js'
import { handleError } from '../../../utils/handlers.ts'

const { phoneMap: { additionalPhones, primaryPhone }, defaultPhoneRegion } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'PhoneSection',

	components: {
		HeaderBar,
		PhoneSectionEntry,
	},

	data() {
		return {
			accountProperty: ACCOUNT_PROPERTY_READABLE_ENUM.PHONE,
			additionalPhones: additionalPhones.map((properties) => ({ ...properties, key: this.generateUniqueKey() })),
			primaryPhone: { ...primaryPhone, readable: NAME_READABLE_ENUM[primaryPhone.name] },
		}
	},

	computed: {
		firstAdditionalPhone() {
			if (this.additionalPhones.length) {
				return this.additionalPhones[0].value
			}
			return null
		},

		inputId() {
			return `account-property-${this.primaryPhone.name}`
		},

		isValidSection() {
			return this.isValidPhone(this.primaryPhone.value)
				&& this.additionalPhones.map(({ value }) => value).every(this.isValidPhone)
		},

		primaryPhoneValue: {
			get() {
				return this.primaryPhone.value
			},

			set(value) {
				this.primaryPhone.value = value
			},
		},
	},

	methods: {
		isValidPhone(value) {
			if (value === '') {
				return true
			}
			if (defaultPhoneRegion) {
				return isValidPhoneNumber(value, defaultPhoneRegion)
			}
			return isValidPhoneNumber(value)
		},

		onAddAdditionalPhone() {
			if (this.isValidSection) {
				this.additionalPhones.push({ value: '', scope: DEFAULT_ADDITIONAL_PHONE_SCOPE, key: this.generateUniqueKey() })
			}
		},

		onDeleteAdditionalPhone(index) {
			this.$delete(this.additionalPhones, index)
		},

		async onUpdatePhone() {
			if (this.primaryPhoneValue === '' && this.firstAdditionalPhone) {
				const deletedPhone = this.firstAdditionalPhone
				await this.deleteFirstAdditionalPhone()
				this.primaryPhoneValue = deletedPhone
				await this.updatePrimaryPhone()
			}
		},

		async updatePrimaryPhone() {
			try {
				const responseData = await savePrimaryPhone(this.primaryPhoneValue)
				this.handleResponse(responseData.ocs?.meta?.status)
			} catch (e) {
				this.handleResponse(
					'error',
					t('settings', 'Unable to update primary phone number'),
					e,
				)
			}
		},

		async deleteFirstAdditionalPhone() {
			try {
				const responseData = await removeAdditionalPhone(this.firstAdditionalPhone)
				this.handleDeleteFirstAdditionalPhone(responseData.ocs?.meta?.status)
			} catch (e) {
				this.handleResponse(
					'error',
					t('settings', 'Unable to delete additional phone number'),
					e,
				)
			}
		},

		handleDeleteFirstAdditionalPhone(status) {
			if (status === 'ok') {
				this.$delete(this.additionalPhones, 0)
			} else {
				this.handleResponse(
					'error',
					t('settings', 'Unable to delete additional phone number'),
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
.section-phones {
	padding: 10px 10px;

	&__additional-phone {
		margin-top: calc(var(--default-grid-baseline) * 3);
	}
}
</style>
