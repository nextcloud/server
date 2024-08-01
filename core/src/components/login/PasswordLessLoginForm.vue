<!--
 - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<form v-if="(isHttps || isLocalhost) && supportsWebauthn"
		ref="loginForm"
		method="post"
		name="login"
		@submit.prevent="submit">
		<h2>{{ t('core', 'Log in with a device') }}</h2>
		<fieldset>
			<NcTextField required
				:value="user"
				:autocomplete="autoCompleteAllowed ? 'on' : 'off'"
				:error="!validCredentials"
				:label="t('core', 'Login or email')"
				:placeholder="t('core', 'Login or email')"
				:helper-text="!validCredentials ? t('core', 'Your account is not setup for passwordless login.') : ''"
				@update:value="changeUsername" />

			<LoginButton v-if="validCredentials"
				:loading="loading"
				@click="authenticate" />
		</fieldset>
	</form>
	<div v-else-if="!supportsWebauthn" class="update">
		<InformationIcon size="70" />
		<h2>{{ t('core', 'Browser not supported') }}</h2>
		<p class="infogroup">
			{{ t('core', 'Passwordless authentication is not supported in your browser.') }}
		</p>
	</div>
	<div v-else-if="!isHttps && !isLocalhost" class="update">
		<LockOpenIcon size="70" />
		<h2>{{ t('core', 'Your connection is not secure') }}</h2>
		<p class="infogroup">
			{{ t('core', 'Passwordless authentication is only available over a secure connection.') }}
		</p>
	</div>
</template>

<script>
import { browserSupportsWebAuthn } from '@simplewebauthn/browser'
import {
	startAuthentication,
	finishAuthentication,
} from '../../services/WebAuthnAuthenticationService.ts'
import LoginButton from './LoginButton.vue'
import InformationIcon from 'vue-material-design-icons/Information.vue'
import LockOpenIcon from 'vue-material-design-icons/LockOpen.vue'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import logger from '../../logger'

export default {
	name: 'PasswordLessLoginForm',
	components: {
		LoginButton,
		InformationIcon,
		LockOpenIcon,
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
		}
	},
	methods: {
		async authenticate() {
			// check required fields
			if (!this.$refs.loginForm.checkValidity()) {
				return
			}

			console.debug('passwordless login initiated')

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
					console.debug('Logged in redirecting')
					// Redirect url might be false so || should be used instead of ??.
					window.location.href = redirectUrl || defaultRedirectUrl
				})
				.catch(error => {
					console.debug('GOT AN ERROR WHILE SUBMITTING CHALLENGE!')
					console.debug(error) // Example: timeout, interaction refused...
				})
		},
		submit() {
			// noop
		},
	},
}
</script>

<style lang="scss" scoped>
	fieldset {
		display: flex;
		flex-direction: column;
		gap: 0.5rem;

		:deep(label) {
			text-align: initial;
		}
	}

	.update {
		margin: 0 auto;
	}
</style>
