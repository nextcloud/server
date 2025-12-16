<!--
 - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<form
		v-if="(isHttps || isLocalhost) && supportsWebauthn"
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
			required
			:model-value="user"
			:autocomplete="autoCompleteAllowed ? 'on' : 'off'"
			:error="!validCredentials"
			:label="t('core', 'Login or email')"
			:placeholder="t('core', 'Login or email')"
			:helper-text="!validCredentials ? t('core', 'Your account is not setup for passwordless login.') : ''"
			@update:value="changeUsername" />

		<LoginButton
			v-if="validCredentials"
			:loading="loading"
			@click="authenticate" />
	</form>

	<NcEmptyContent
		v-else-if="!isHttps && !isLocalhost"
		:name="t('core', 'Your connection is not secure')"
		:description="t('core', 'Passwordless authentication is only available over a secure connection.')">
		<template #icon>
			<LockOpenIcon />
		</template>
	</NcEmptyContent>

	<NcEmptyContent
		v-else
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
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import InformationIcon from 'vue-material-design-icons/InformationOutline.vue'
import LockOpenIcon from 'vue-material-design-icons/LockOpen.vue'
import LoginButton from './LoginButton.vue'
import logger from '../../logger.js'
import {
	finishAuthentication,
	NoValidCredentials,
	startAuthentication,
} from '../../services/WebAuthnAuthenticationService.ts'

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
			type: [Boolean, String],
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
		}
	},

	methods: {
		async authenticate() {
			// check required fields
			if (!this.$refs.loginForm.checkValidity()) {
				return
			}

			logger.debug('passwordless login initiated')

			try {
				const params = await startAuthentication(this.user)
				await this.completeAuthentication(params)
			} catch (error) {
				if (error instanceof NoValidCredentials) {
					this.validCredentials = false
					return
				}
				logger.debug(error)
			}
		},

		changeUsername(username) {
			this.user = username
			this.$emit('update:username', this.user)
		},

		completeAuthentication(challenge) {
			const redirectUrl = this.redirectUrl

			return finishAuthentication(challenge)
				.then(({ defaultRedirectUrl }) => {
					logger.debug('Logged in redirecting') // Redirect url might be false so || should be used instead of ??.
					window.location.href = redirectUrl || defaultRedirectUrl
				})
				.catch((error) => {
					logger.debug('GOT AN ERROR WHILE SUBMITTING CHALLENGE!', { error }) // Example: timeout, interaction refused...
				})
		},

		submit() {
			// noop
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
