<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<div class="phone" :class="{ 'phone--additional': !primary }">
			<div v-if="!primary" class="phone__label-container">
				<label :for="inputIdWithDefault">{{ inputPlaceholder }}</label>
				<FederationControl
					v-if="!federationDisabled && !primary"
					:readable="propertyReadable"
					:additional="true"
					:additional-value="phone"
					:disabled="federationDisabled"
					:handle-additional-scope-change="saveAdditionalPhoneScope"
					:scope.sync="localScope"
					@update:scope="onScopeChange" />
			</div>
			<div class="phone__input-container">
				<NcTextField
					:id="inputIdWithDefault"
					ref="phoneInput"
					v-model="phoneNumber"
					class="phone__input"
					autocomplete="tel"
					:error="hasError || !!helperText"
					:helper-text="helperTextWithNonConfirmed"
					label-outside
					:placeholder="inputPlaceholder"
					spellcheck="false"
					:success="isSuccess"
					type="tel" />

				<div class="phone__actions">
					<NcActions :aria-label="actionsLabel">
						<NcActionButton
							close-after-click
							:disabled="deleteDisabled"
							@click="deletePhone">
							<template #icon>
								<NcIconSvgWrapper :path="mdiTrashCanOutline" />
							</template>
							{{ deletePhoneLabel }}
						</NcActionButton>
					</NcActions>
				</div>
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import type { AxiosError } from '@nextcloud/axios'
import type { CountryCode } from 'libphonenumber-js'

import { mdiTrashCanOutline } from '@mdi/js'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import debounce from 'debounce'
import { isValidPhoneNumber } from 'libphonenumber-js'
import { computed, nextTick, onMounted, ref } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import FederationControl from '../shared/FederationControl.vue'
import { ACCOUNT_PROPERTY_READABLE_ENUM } from '../../../constants/AccountPropertyConstants.ts'
import {
	removeAdditionalPhone,
	saveAdditionalPhone,
	saveAdditionalPhoneScope,
	savePrimaryPhone,
	updateAdditionalPhone,
} from '../../../service/PersonalInfo/PhoneService.ts'
import { handleError } from '../../../utils/handlers.ts'

const props = withDefaults(defineProps<{
	phone: string
	index?: number
	primary?: boolean
	scope: string
	inputId?: string
}>(), {
	index: 0,
	primary: false,
	inputId: '',
})

const emit = defineEmits<{
	(e: 'update:phone', value: string): void
	(e: 'update:scope', scope: string): void
	(e: 'delete-additional-phone'): void
}>()

const { defaultPhoneRegion } = loadState<{ defaultPhoneRegion?: CountryCode }>('settings', 'personalInfoParameters', {})

const phoneInput = ref<InstanceType<typeof NcTextField>>()
const hasError = ref(false)
const helperText = ref<string>('')
const initialPhone = ref(props.phone)
const isSuccess = ref(false)
const localScope = ref(props.scope)
const propertyReadable = ACCOUNT_PROPERTY_READABLE_ENUM.PHONE_COLLECTION

const debouncePhoneChange = debounce(async (phone: string) => {
	if (isValidPhone(phone)) {
		if (props.primary) {
			await updatePrimaryPhone(phone)
		} else if (phone) {
			if (initialPhone.value === '') {
				await addAdditionalPhone(phone)
			} else {
				await updateAdditionalPhoneEntry(phone)
			}
		}
	}
}, 1000)

const actionsLabel = computed(() => {
	if (props.primary) {
		return t('settings', 'Phone number options')
	}
	return t('settings', 'Options for additional phone number {index}', { index: props.index + 1 })
})

const deleteDisabled = computed(() => {
	if (props.primary) {
		return props.phone === '' || initialPhone.value !== props.phone
	} else if (initialPhone.value !== '') {
		return initialPhone.value !== props.phone
	}
	return false
})

const deletePhoneLabel = computed(() => {
	if (props.primary) {
		return t('settings', 'Remove primary phone number')
	}
	return t('settings', 'Delete phone number')
})

const helperTextWithNonConfirmed = computed(() => {
	if (helperText.value || hasError.value || isSuccess.value) {
		return helperText.value || ''
	}
	return ''
})

const federationDisabled = computed(() => !initialPhone.value)

const inputIdWithDefault = computed(() => props.inputId || `account-property-phone--${props.index}`)

const inputPlaceholder = computed(() => {
	return !props.primary ? t('settings', 'Additional phone number {index}', { index: props.index + 1 }) : undefined
})

const phoneNumber = computed({
	get() {
		return props.phone
	},
	set(value: string) {
		emit('update:phone', value)
		debouncePhoneChange(value.trim())
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

async function deletePhone() {
	if (props.primary) {
		emit('update:phone', '')
		await updatePrimaryPhone('')
	} else {
		await deleteAdditionalPhone()
	}
}

async function updatePrimaryPhone(phone: string) {
	try {
		const responseData = await savePrimaryPhone(phone)
		handleResponse({
			phone,
			status: responseData.ocs?.meta?.status,
		})
	} catch (e) {
		if (phone === '') {
			handleResponse({
				errorMessage: t('settings', 'Unable to delete primary phone number'),
				error: e as AxiosError,
			})
		} else {
			handleResponse({
				errorMessage: t('settings', 'Unable to update primary phone number'),
				error: e as AxiosError,
			})
		}
	}
}

async function addAdditionalPhone(phone: string) {
	try {
		const responseData = await saveAdditionalPhone(phone)
		handleResponse({
			phone,
			status: responseData.ocs?.meta?.status,
		})
	} catch (e) {
		handleResponse({
			errorMessage: t('settings', 'Unable to add additional phone number'),
			error: e as AxiosError,
		})
	}
}

async function updateAdditionalPhoneEntry(phone: string) {
	try {
		const responseData = await updateAdditionalPhone(initialPhone.value, phone)
		handleResponse({
			phone,
			status: responseData.ocs?.meta?.status,
		})
	} catch (e) {
		handleResponse({
			errorMessage: t('settings', 'Unable to update additional phone number'),
			error: e as AxiosError,
		})
	}
}

async function deleteAdditionalPhone() {
	try {
		const responseData = await removeAdditionalPhone(initialPhone.value)
		handleDeleteAdditionalPhone(responseData.ocs?.meta?.status)
	} catch (e) {
		handleResponse({
			errorMessage: t('settings', 'Unable to delete additional phone number'),
			error: e as AxiosError,
		})
	}
}

function handleDeleteAdditionalPhone(status?: string) {
	if (status === 'ok') {
		emit('delete-additional-phone')
	} else {
		handleResponse({
			errorMessage: t('settings', 'Unable to delete additional phone number'),
		})
	}
}

function handleResponse({ phone, status, errorMessage, error }: {
	phone?: string
	status?: string
	errorMessage?: string
	error?: AxiosError
}) {
	if (status === 'ok') {
		if (phone) {
			initialPhone.value = phone
		}
		isSuccess.value = true
		setTimeout(() => {
			isSuccess.value = false
		}, 2000)
	} else {
		handleError(error!, errorMessage!)
		hasError.value = true
		setTimeout(() => {
			hasError.value = false
		}, 2000)
	}
}

function onScopeChange(scope: string) {
	emit('update:scope', scope)
}

onMounted(() => {
	if (!props.primary && initialPhone.value === '') {
		nextTick(() => phoneInput.value?.focus())
	}
})
</script>

<style lang="scss" scoped>
.phone {
	&__label-container {
		height: var(--default-clickable-area);
		display: flex;
		flex-direction: row;
		align-items: center;
		gap: calc(var(--default-grid-baseline) * 2);
	}

	&__input-container {
		position: relative;
	}

	&__input {
		:deep(.input-field__icon--trailing) {
			display: none;
		}
	}

	&__actions {
		position: absolute;
		inset-block-start: 0;
		inset-inline-end: 0;
	}
}
</style>
