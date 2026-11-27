<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { RegistrationResponseJSON } from '@simplewebauthn/browser'
import type { WebAuthnDevice } from '../../../service/WebAuthnRegistrationSerice.ts'

import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { nextTick, ref, useTemplateRef } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import logger from '../../../logger.ts'
import {
	finishRegistration,
	startRegistration,
} from '../../../service/WebAuthnRegistrationSerice.ts'

defineProps<{
	/** Whether the page is served over HTTPS */
	isHttps?: boolean
	/** Whether the page is served from localhost */
	isLocalhost?: boolean
}>()

const emit = defineEmits<{
	/** A device was registered on the server */
	added: [device: WebAuthnDevice]
}>()

const RegistrationSteps = Object.freeze({
	READY: 1,
	REGISTRATION: 2,
	NAMING: 3,
	PERSIST: 4,
})

const name = ref('')
const credential = ref<RegistrationResponseJSON>()
const step = ref<number>(RegistrationSteps.READY)

const nameInput = useTemplateRef('nameInput')

/**
 * Start the registration process by loading the authenticator parameters
 * The next step is the naming of the device
 */
async function start() {
	step.value = RegistrationSteps.REGISTRATION
	logger.debug('Starting WebAuthn registration')

	try {
		await confirmPassword()
		credential.value = await startRegistration()
		step.value = RegistrationSteps.NAMING
		await nextTick()
		nameInput.value?.focus()
	} catch (err) {
		showError(String(err))
		step.value = RegistrationSteps.READY
	}
}

/**
 * Save the new device with the given name on the server
 */
async function submit() {
	step.value = RegistrationSteps.PERSIST

	try {
		await confirmPassword()
		logger.debug('confirmed password')
		await saveRegistrationData()
		logger.debug('registration data saved')
		reset()
		logger.debug('app reset')
	} catch (error) {
		logger.error(String(error), { error })
	}
}

/**
 * Finish the registration on the server and add the new device to the list
 */
async function saveRegistrationData() {
	try {
		const device = await finishRegistration(name.value, credential.value!)

		logger.info('new device added', { device })

		emit('added', device)
	} catch (err) {
		logger.error('Error persisting webauthn registration', { error: err })
		throw new Error(t('settings', 'Server error while trying to complete WebAuthn device registration'), { cause: err })
	}
}

/**
 * Back to the start, ready for the next device
 */
function reset() {
	name.value = ''
	step.value = RegistrationSteps.READY
}
</script>

<template>
	<div v-if="!isHttps && !isLocalhost">
		{{ t('settings', 'Passwordless authentication requires a secure connection.') }}
	</div>
	<div v-else>
		<NcButton
			v-if="step === RegistrationSteps.READY"
			variant="primary"
			@click="start">
			{{ t('settings', 'Add WebAuthn device') }}
		</NcButton>

		<div
			v-else-if="step === RegistrationSteps.REGISTRATION"
			class="new-webauthn-device">
			<span class="icon-loading-small webauthn-loading" />
			{{ t('settings', 'Please authorize your WebAuthn device.') }}
		</div>

		<div
			v-else-if="step === RegistrationSteps.NAMING"
			class="new-webauthn-device">
			<span class="icon-loading-small webauthn-loading" />
			<form @submit.prevent="submit">
				<NcTextField
					ref="nameInput"
					v-model="name"
					class="new-webauthn-device__name"
					:label="t('settings', 'Device name')"
					showTrailingButton
					:trailingButtonLabel="t('settings', 'Add')"
					trailingButtonIcon="arrowEnd"
					@trailingButtonClick="submit" />
			</form>
		</div>

		<div
			v-else-if="step === RegistrationSteps.PERSIST"
			class="new-webauthn-device">
			<span class="icon-loading-small webauthn-loading" />
			{{ t('settings', 'Adding your device …') }}
		</div>

		<div v-else>
			Invalid registration step. This should not have happened.
		</div>
	</div>
</template>

<style scoped lang="scss">
.webauthn-loading {
	display: inline-block;
	vertical-align: sub;
	margin-inline: 2px;
}

.new-webauthn-device {
	display: flex;
	gap: 22px;
	align-items: center;

	&__name {
		max-width: min(100vw, 400px);
	}
}
</style>
