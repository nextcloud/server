<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiStar, mdiStarOutline, mdiTrashCanOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { useDebounceFn } from '@vueuse/core'
import { computed, nextTick, onMounted, ref, useTemplateRef } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import FederationControl from '../shared/FederationControl.vue'
import { ACCOUNT_PROPERTY_READABLE_ENUM, VERIFICATION_ENUM } from '../../../../constants/AccountPropertyConstants.ts'
import {
	removeAdditionalEmail,
	saveAdditionalEmail,
	saveAdditionalEmailScope,
	saveNotificationEmail,
	savePrimaryEmail,
	updateAdditionalEmail,
} from '../../../../service/PersonalInfo/EmailService.js'
import { handleError } from '../../../../utils/handlers.ts'
import { validateEmail } from '../../../../utils/validate.js'

/** The email address */
const email = defineModel<string>('email', { required: true })

/** Federation scope of the address */
const scope = defineModel<string>('scope', { required: true })

const props = withDefaults(defineProps<{
	/** Position of an additional address */
	index?: number
	/** Whether this is the primary address */
	primary?: boolean
	/** The address notifications are sent to, empty for the primary one */
	activeNotificationEmail?: string
	/** Verification state of an additional address */
	localVerificationState?: number
	/** Id of the text field */
	inputId?: string
}>(), {
	index: 0,
	primary: false,
	activeNotificationEmail: '',
	localVerificationState: VERIFICATION_ENUM.NOT_VERIFIED,
	inputId: '',
})

const emit = defineEmits<{
	/** Another address was picked for notifications, empty for the primary one */
	'update:notificationEmail': [email: string]
	/** The primary address was deleted on the server */
	deletePrimaryEmail: []
	/** The additional address was deleted on the server */
	deleteAdditionalEmail: []
}>()

const propertyReadable = ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL

const emailField = useTemplateRef('emailField')

const hasError = ref(false)
const helperText = ref<string | null>(null)
const initialEmail = ref(email.value)
const isSuccess = ref(false)

const actionsLabel = computed(() => props.primary
	? t('settings', 'Email options')
	: t('settings', 'Options for additional email address {index}', { index: props.index + 1 }))

const deleteDisabled = computed(() => {
	if (props.primary) {
		// Disable for empty primary email as there is nothing to delete
		// OR when initialEmail (reflects server state) and email (current input) are not the same
		return email.value === '' || initialEmail.value !== email.value
	} else if (initialEmail.value !== '') {
		return initialEmail.value !== email.value
	}
	return false
})

const deleteEmailLabel = computed(() => props.primary
	? t('settings', 'Remove primary email')
	: t('settings', 'Delete email'))

const isConfirmedAddress = computed(() => props.primary || props.localVerificationState === VERIFICATION_ENUM.VERIFIED)

const helperTextWithNonConfirmed = computed(() => {
	if (helperText.value || hasError.value || isSuccess.value) {
		return helperText.value || ''
	}
	return isConfirmedAddress.value ? '' : t('settings', 'This address is not confirmed')
})

const isNotificationEmail = computed(() => (email.value && email.value === props.activeNotificationEmail)
	|| (props.primary && props.activeNotificationEmail === ''))

const setNotificationMailLabel = computed(() => isNotificationEmail.value
	? t('settings', 'Unset as primary email')
	: t('settings', 'Set as primary email'))

const entryLabel = computed(() => {
	const label = props.primary
		? t('settings', 'Primary email address')
		: t('settings', 'Additional email address {index}', { index: props.index + 1 })
	if (!props.primary && initialEmail.value && !isConfirmedAddress.value) {
		return t('settings', '{label} (confirmation sent)', { label })
	}
	return label
})

onMounted(async () => {
	if (!props.primary && initialEmail.value === '') {
		// nextTick is needed here, otherwise it may not always work
		// https://stackoverflow.com/questions/51922767/autofocus-input-on-mount-vue-ios/63485725#63485725
		await nextTick()
		emailField.value?.focus()
	}
})

const debounceEmailChange = useDebounceFn(async (newEmail: string) => {
	helperText.value = emailField.value?.$el.querySelector('input')?.validationMessage || null
	if (helperText.value !== null) {
		return
	}
	if (!validateEmail(newEmail) && newEmail !== '') {
		return
	}
	if (props.primary) {
		await updatePrimaryEmail(newEmail)
	} else if (newEmail) {
		if (initialEmail.value === '') {
			await saveEmail(() => saveAdditionalEmail(newEmail), newEmail, t('settings', 'Unable to add additional email address'))
		} else {
			await saveEmail(() => updateAdditionalEmail(initialEmail.value, newEmail), newEmail, t('settings', 'Unable to update additional email address'))
		}
	}
}, 1000)

/**
 * @param newEmail The entered address
 */
function onInput(newEmail: string | number) {
	// the model only updates once the parent re-renders, so don't read it back here
	const entered = String(newEmail)
	email.value = entered
	debounceEmailChange(entered.trim())
}

/**
 * Delete the address on the server
 */
async function deleteEmail() {
	if (props.primary) {
		// the parent promotes an additional address into this entry
		if (await updatePrimaryEmail('')) {
			email.value = ''
			emit('deletePrimaryEmail')
		}
	} else {
		await deleteAdditionalEmail()
	}
}

/**
 * @param newEmail The new primary address, empty to remove it
 * @return Whether it was saved
 */
async function updatePrimaryEmail(newEmail: string): Promise<boolean> {
	return saveEmail(
		() => savePrimaryEmail(newEmail),
		newEmail,
		newEmail === ''
			? t('settings', 'Unable to delete primary email address')
			: t('settings', 'Unable to update primary email address'),
	)
}

/**
 * @param save Stores the address on the server
 * @param newEmail The address being stored
 * @param errorMessage Shown when saving fails
 * @return Whether it was saved
 */
async function saveEmail(save: () => Promise<unknown>, newEmail: string, errorMessage: string): Promise<boolean> {
	hasError.value = false
	try {
		await save()
		// Ensure that local state reflects server state
		if (newEmail) {
			// the server stores addresses lower-cased and later matches them exactly
			initialEmail.value = newEmail.toLowerCase()
			if (email.value.trim() === newEmail) {
				email.value = initialEmail.value
			}
		}
		showSuccess()
		return true
	} catch (error) {
		// the field still holds the unsaved address, so keep the error until the next attempt
		handleError(error, errorMessage)
		hasError.value = true
		return false
	}
}

/**
 * Pick this address for notifications, or go back to the primary one
 */
async function setNotificationMail() {
	const newNotificationEmail = (props.primary || isNotificationEmail.value) ? '' : initialEmail.value
	try {
		await saveNotificationEmail(newNotificationEmail)
		emit('update:notificationEmail', newNotificationEmail)
		showSuccess()
	} catch (error) {
		showFailure(error, t('settings', 'Unable to choose this email for notifications'))
	}
}

/**
 * Delete the additional address on the server
 */
async function deleteAdditionalEmail() {
	try {
		await removeAdditionalEmail(initialEmail.value)
		emit('deleteAdditionalEmail')
		if (isNotificationEmail.value) {
			emit('update:notificationEmail', '')
		}
	} catch (error) {
		showFailure(error, t('settings', 'Unable to delete additional email address'))
	}
}

/**
 * Highlight the field as saved for a moment
 */
function showSuccess() {
	isSuccess.value = true
	setTimeout(() => {
		isSuccess.value = false
	}, 2000)
}

/**
 * @param error The error that occurred
 * @param errorMessage Shown to the user
 */
function showFailure(error: unknown, errorMessage: string) {
	handleError(error, errorMessage)
	hasError.value = true
	setTimeout(() => {
		hasError.value = false
	}, 2000)
}
</script>

<template>
	<div :class="$style.propertySection">
		<div :class="$style.property">
			<NcTextField
				:id="inputId || `account-property-email--${index}`"
				ref="emailField"
				:modelValue="email"
				:class="$style.field"
				autocapitalize="none"
				autocomplete="email"
				:error="hasError || !!helperText"
				:helperText="helperTextWithNonConfirmed"
				:label="entryLabel"
				spellcheck="false"
				:success="isSuccess"
				type="email"
				@update:modelValue="onInput" />

			<div :class="$style.scope">
				<FederationControl
					v-if="initialEmail"
					v-model:scope="scope"
					:readable="propertyReadable"
					:additional="!primary"
					:additionalValue="initialEmail"
					:handleAdditionalScopeChange="saveAdditionalEmailScope">
					<NcActionButton
						v-if="!primary || !isNotificationEmail"
						closeAfterClick
						:disabled="!isConfirmedAddress"
						@click="setNotificationMail">
						<template #icon>
							<NcIconSvgWrapper :path="isNotificationEmail ? mdiStar : mdiStarOutline" />
						</template>
						{{ setNotificationMailLabel }}
					</NcActionButton>
					<NcActionButton
						closeAfterClick
						:disabled="deleteDisabled"
						@click="deleteEmail">
						<template #icon>
							<NcIconSvgWrapper :path="mdiTrashCanOutline" />
						</template>
						{{ deleteEmailLabel }}
					</NcActionButton>
				</FederationControl>

				<NcActions
					v-else
					:aria-label="actionsLabel">
					<NcActionButton
						closeAfterClick
						:disabled="deleteDisabled"
						@click="deleteEmail">
						<template #icon>
							<NcIconSvgWrapper :path="mdiTrashCanOutline" />
						</template>
						{{ deleteEmailLabel }}
					</NcActionButton>
				</NcActions>
			</div>
		</div>
	</div>
</template>

<style module>
.propertySection {
	padding: 6px 0;
}

.property {
	position: relative;
}

.field {
	width: 100%;
}

.scope {
	position: absolute;
	inset-block-start: 0;
	inset-inline-start: calc(100% + 8px);
	display: flex;
	align-items: center;
	justify-content: center;
	width: var(--default-clickable-area);
	height: var(--default-clickable-area);
}
</style>
