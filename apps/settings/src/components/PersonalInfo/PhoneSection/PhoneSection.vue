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

<script setup lang="ts">
import type { AxiosError } from '@nextcloud/axios'
import type { CountryCode } from 'libphonenumber-js'
import type { IAccountProperty } from '../../../constants/AccountPropertyConstants.ts'

import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { isValidPhoneNumber } from 'libphonenumber-js'
import { computed, ref } from 'vue'
import HeaderBar from '../shared/HeaderBar.vue'
import PhoneSectionEntry from './PhoneSectionEntry.vue'
import { DEFAULT_ADDITIONAL_PHONE_SCOPE, NAME_READABLE_ENUM } from '../../../constants/AccountPropertyConstants.ts'
import { removeAdditionalPhone, savePrimaryPhone } from '../../../service/PersonalInfo/PhoneService.ts'
import { handleError } from '../../../utils/handlers.ts'

type AdditionalPhoneEntry = Partial<IAccountProperty> & {
	value: string
	scope: IAccountProperty['scope']
	key: string
}

interface PrimaryPhoneEntry extends IAccountProperty {
	readable: string
}

interface PersonalInfoParameters {
	phoneMap: {
		additionalPhones: IAccountProperty[]
		primaryPhone: IAccountProperty
	}
	defaultPhoneRegion?: CountryCode
}

function generateUniqueKey(): string {
	return Math.random().toString(36).substring(2)
}

const { phoneMap: { additionalPhones: initialAdditionalPhones, primaryPhone: initialPrimaryPhone }, defaultPhoneRegion } = loadState<PersonalInfoParameters>('settings', 'personalInfoParameters', {
	phoneMap: {
		additionalPhones: [],
		primaryPhone: { name: '', value: '', scope: DEFAULT_ADDITIONAL_PHONE_SCOPE, verified: 0 },
	},
})

const additionalPhones = ref<AdditionalPhoneEntry[]>(initialAdditionalPhones.map((properties) => ({ ...properties, key: generateUniqueKey() })))

const primaryPhone = ref<PrimaryPhoneEntry>({
	...initialPrimaryPhone,
	readable: NAME_READABLE_ENUM[initialPrimaryPhone.name as keyof typeof NAME_READABLE_ENUM],
})

const firstAdditionalPhone = computed(() => {
	if (additionalPhones.value.length) {
		return additionalPhones.value[0].value
	}
	return null
})

const inputId = computed(() => `account-property-${primaryPhone.value.name}`)

const primaryPhoneValue = computed({
	get() {
		return primaryPhone.value.value
	},
	set(value: string) {
		primaryPhone.value.value = value
	},
})

function isValidPhone(value: string): boolean {
	if (value === '') {
		return true
	}
	if (defaultPhoneRegion) {
		return isValidPhoneNumber(value, defaultPhoneRegion)
	}
	return isValidPhoneNumber(value)
}

const isValidSection = computed(() => {
	return isValidPhone(primaryPhone.value.value)
		&& additionalPhones.value.map(({ value }) => value).every(isValidPhone)
})

function onAddAdditionalPhone() {
	if (isValidSection.value) {
		additionalPhones.value.push({ value: '', scope: DEFAULT_ADDITIONAL_PHONE_SCOPE, key: generateUniqueKey() })
	}
}

function onDeleteAdditionalPhone(index: number) {
	additionalPhones.value.splice(index, 1)
}

async function onUpdatePhone() {
	if (primaryPhoneValue.value === '' && firstAdditionalPhone.value) {
		const deletedPhone = firstAdditionalPhone.value
		await deleteFirstAdditionalPhone()
		primaryPhoneValue.value = deletedPhone
		await updatePrimaryPhone()
	}
}

async function updatePrimaryPhone() {
	try {
		const responseData = await savePrimaryPhone(primaryPhoneValue.value)
		handleResponse(responseData.ocs?.meta?.status)
	} catch (e) {
		handleResponse(
			'error',
			t('settings', 'Unable to update primary phone number'),
			e as AxiosError,
		)
	}
}

async function deleteFirstAdditionalPhone() {
	try {
		const responseData = await removeAdditionalPhone(firstAdditionalPhone.value!)
		handleDeleteFirstAdditionalPhone(responseData.ocs?.meta?.status)
	} catch (e) {
		handleResponse(
			'error',
			t('settings', 'Unable to delete additional phone number'),
			e as AxiosError,
		)
	}
}

function handleDeleteFirstAdditionalPhone(status?: string) {
	if (status === 'ok') {
		additionalPhones.value.splice(0, 1)
	} else {
		handleResponse(
			'error',
			t('settings', 'Unable to delete additional phone number'),
		)
	}
}

function handleResponse(status?: string, errorMessage?: string, error?: AxiosError) {
	if (status !== 'ok') {
		handleError(error!, errorMessage!)
	}
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
