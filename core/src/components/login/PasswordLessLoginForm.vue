<!--
 - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<form v-if="(isHttps || isLocalhost) && supportsWebauthn"
		ref="loginForm"
		aria-labelledby="password-less-login-form-title"
		class="password-less-login-form"
		method="post"
		name="login"
		@submit.prevent="submit">
		<h2 id="password-less-login-form-title">
			{{ t('core', 'Log in with a device') }}
		</h2>

		<NcTextField
			:model-value="user"
			:autocomplete="autoCompleteAllowed ? 'on' : 'off'"
			:error="!validCredentials"
			:label="t('core', 'Login or email (optional)')"
			:placeholder="t('core', 'Login or email (optional)')"
			:helper-text="helperText"
			@update:value="changeUsername" />

		<LoginButton
			:loading="loading"
			@click="authenticate" />
	</form>

	<NcEmptyContent v-else-if="!isHttps && !isLocalhost"
		:name="t('core', 'Your connection is not secure')"
		:description="t('core', 'Passwordless authentication is only available over a secure connection.')">
		<template #icon>
			<LockOpenIcon />
		</template>
	</NcEmptyContent>

	<NcEmptyContent v-else
		:name="t('core', 'Browser not supported')"
		:description="t('core', 'Passwordless authentication is not supported in your browser.')">
		<template #icon>
			<InformationIcon />
		</template>
	</NcEmptyContent>
</template>

<script type="ts">
import { browserSupportsWebAuthn } from '@simplewebauthn/browser'
import { defineComponent } from 'vue'
import {
	NoValidCredentials,
	startAuthentication,
	finishAuthentication,
} from '../../services/WebAuthnAuthenticationService.ts'

import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcTextField from '@nextcloud/vue/components/NcTextField'

import InformationIcon from 'vue-material-design-icons/InformationOutline.vue'
import LoginButton from './LoginButton.vue'
import LockOpenIcon from 'vue-material-design-icons/LockOpen.vue'
import logger from '../../logger'

export default defineComponent({
	name: 'PasswordLessLoginForm',
	components: {
		LoginButton,
		InformationIcon,
		LockOpenIcon,
		NcEmptyContent,
		NcTextField,
	},
	props: {
		username: {
			type: String,
			default: '',
		},
		redirectUrl: {
			type: [String, Boolean],
			default: false,
		},
		autoCompleteAllowed: {
			type: Boolean,
			default: true,
		},
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
		return {
			supportsWebauthn: browserSupportsWebAuthn(),
		}
	},

	data() {
		return {
			user: this.username,
			loading: false,
			validCredentials: true,
			helperText: this.t('core', 'Leave empty to use a discoverable credential.'),
		}
	},
	methods: {
		async authenticate() {
			// check required fields
			if (!this.$refs.loginForm.checkValidity()) {
				return
			}

			console.debug('passwordless login initiated')

			this.loading = true
			try {
				const trimmed = this.user.trim()
				const params = await startAuthentication(trimmed !== '' ? trimmed : undefined)
				await this.completeAuthentication(params)
			} catch (error) {
				this.loading = false
				if (error instanceof NoValidCredentials && this.user.trim() === '') {
					this.helperText = this.t('core', 'No discoverable credential found. Please enter your login or email and try again.')
					this.validCredentials = false
					return
				}
				logger.debug(error)
			}
		},
		changeUsername(username) {
			this.user = username
			this.validCredentials = true
			this.helperText = this.t('core', 'Leave empty to use a discoverable credential.')
			this.$emit('update:username', this.user)
		},
		completeAuthentication(challenge) {
			const redirectUrl = this.redirectUrl

			return finishAuthentication(challenge)
				.then(({ defaultRedirectUrl }) => {
					console.debug('Logged in redirecting')
					// Redirect url might be false so || should be used instead of ??.
					window.location.href = redirectUrl || defaultRedirectUrl
				})
				.catch(error => {
					console.debug('GOT AN ERROR WHILE SUBMITTING CHALLENGE!')
					console.debug(error) // Example: timeout, interaction refused...
					this.loading = false
				})
		},
		submit() {
			if (!this.loading) {
				void this.authenticate()
			}
		},
	},
})
</script>

<style lang="scss" scoped>
	.password-less-login-form {
		display: flex;
		flex-direction: column;
		gap: 0.5rem;
		margin: 0;
	}
</style>
