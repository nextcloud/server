<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

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
					show-trailing-button
					:trailing-button-label="t('settings', 'Add')"
					trailing-button-icon="arrowEnd"
					@trailing-button-click="submit" />
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

<script>
import { showError } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import logger from '../../logger.ts'
import {
	finishRegistration,
	startRegistration,
} from '../../service/WebAuthnRegistrationSerice.ts'

/**
 * @param {string} text The text to log
 */
function logAndPass(text) {
	return (data) => {
		logger.debug(text)
		return data
	}
}

const RegistrationSteps = Object.freeze({
	READY: 1,
	REGISTRATION: 2,
	NAMING: 3,
	PERSIST: 4,
})

export default {
	name: 'WebAuthnAddDevice',

	components: {
		NcButton,
		NcTextField,
	},

	props: {
		httpWarning: Boolean,
		isHttps: {
			type: Boolean,
			default: false,
		},

		isLocalhost: {
			type: Boolean,
			default: false,
		},
	},

	setup() {
		// non reactive props
		return {
			RegistrationSteps,
		}
	},

	data() {
		return {
			name: '',
			credential: {},
			step: RegistrationSteps.READY,
		}
	},

	watch: {
		/**
		 * Auto focus the name input when naming a device
		 */
		step() {
			if (this.step === RegistrationSteps.NAMING) {
				this.$nextTick(() => this.$refs.nameInput?.focus())
			}
		},
	},

	methods: {
		/**
		 * Start the registration process by loading the authenticator parameters
		 * The next step is the naming of the device
		 */
		async start() {
			this.step = RegistrationSteps.REGISTRATION
			logger.debug('Starting WebAuthn registration')

			try {
				await confirmPassword()
				this.credential = await startRegistration()
				this.step = RegistrationSteps.NAMING
			} catch (err) {
				showError(err)
				this.step = RegistrationSteps.READY
			}
		},

		/**
		 * Save the new device with the given name on the server
		 */
		submit() {
			this.step = RegistrationSteps.PERSIST

			return confirmPassword()
				.then(logAndPass('confirmed password'))
				.then(this.saveRegistrationData)
				.then(logAndPass('registration data saved'))
				.then(() => this.reset())
				.then(logAndPass('app reset'))
				.catch(logger.error)
		},

		async saveRegistrationData() {
			try {
				const device = await finishRegistration(this.name, this.credential)

				logger.info('new device added', { device })

				this.$emit('added', device)
			} catch (err) {
				logger.error('Error persisting webauthn registration', { error: err })
				throw new Error(t('settings', 'Server error while trying to complete WebAuthn device registration'))
			}
		},

		reset() {
			this.name = ''
			this.registrationData = {}
			this.step = RegistrationSteps.READY
		},
	},
}
</script>

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
