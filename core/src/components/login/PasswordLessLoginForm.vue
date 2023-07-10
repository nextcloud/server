<template>
	<form v-if="(isHttps || isLocalhost) && hasPublicKeyCredential"
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
				:label-visible="true"
				:label="t('core', 'Username or email')"
				:placeholder="t('core', 'Username or email')"
				:helper-text="!validCredentials ? t('core', 'Your account is not setup for passwordless login.') : ''"
				@update:value="changeUsername" />

			<LoginButton v-if="validCredentials"
				:loading="loading"
				@click="authenticate" />
		</fieldset>
	</form>
	<div v-else-if="!hasPublicKeyCredential" class="update">
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
import {
	startAuthentication,
	finishAuthentication,
} from '../../services/WebAuthnAuthenticationService.js'
import LoginButton from './LoginButton.vue'
import InformationIcon from 'vue-material-design-icons/Information.vue'
import LockOpenIcon from 'vue-material-design-icons/LockOpen.vue'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

class NoValidCredentials extends Error {

}

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
		hasPublicKeyCredential: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			user: this.username,
			loading: false,
			validCredentials: true,
		}
	},
	methods: {
		authenticate() {
			// check required fields
			if (!this.$refs.loginForm.checkValidity()) {
				return
			}

			console.debug('passwordless login initiated')

			this.getAuthenticationData(this.user)
				.then(publicKey => {
					console.debug(publicKey)
					return publicKey
				})
				.then(this.sign)
				.then(this.completeAuthentication)
				.catch(error => {
					if (error instanceof NoValidCredentials) {
						this.validCredentials = false
						return
					}
					console.debug(error)
				})
		},
		changeUsername(username) {
			this.user = username
			this.$emit('update:username', this.user)
		},
		getAuthenticationData(uid) {
			const base64urlDecode = function(input) {
				// Replace non-url compatible chars with base64 standard chars
				input = input
					.replace(/-/g, '+')
					.replace(/_/g, '/')

				// Pad out with standard base64 required padding characters
				const pad = input.length % 4
				if (pad) {
					if (pad === 1) {
						throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding')
					}
					input += new Array(5 - pad).join('=')
				}

				return window.atob(input)
			}

			return startAuthentication(uid)
				.then(publicKey => {
					console.debug('Obtained PublicKeyCredentialRequestOptions')
					console.debug(publicKey)

					if (!Object.prototype.hasOwnProperty.call(publicKey, 'allowCredentials')) {
						console.debug('No credentials found.')
						throw new NoValidCredentials()
					}

					publicKey.challenge = Uint8Array.from(base64urlDecode(publicKey.challenge), c => c.charCodeAt(0))
					publicKey.allowCredentials = publicKey.allowCredentials.map(function(data) {
						return {
							...data,
							id: Uint8Array.from(base64urlDecode(data.id), c => c.charCodeAt(0)),
						}
					})

					console.debug('Converted PublicKeyCredentialRequestOptions')
					console.debug(publicKey)
					return publicKey
				})
				.catch(error => {
					console.debug('Error while obtaining data')
					throw error
				})
		},
		sign(publicKey) {
			const arrayToBase64String = function(a) {
				return window.btoa(String.fromCharCode(...a))
			}

			const arrayToString = function(a) {
				return String.fromCharCode(...a)
			}

			return navigator.credentials.get({ publicKey })
				.then(data => {
					console.debug(data)
					console.debug(new Uint8Array(data.rawId))
					console.debug(arrayToBase64String(new Uint8Array(data.rawId)))
					return {
						id: data.id,
						type: data.type,
						rawId: arrayToBase64String(new Uint8Array(data.rawId)),
						response: {
							authenticatorData: arrayToBase64String(new Uint8Array(data.response.authenticatorData)),
							clientDataJSON: arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
							signature: arrayToBase64String(new Uint8Array(data.response.signature)),
							userHandle: data.response.userHandle ? arrayToString(new Uint8Array(data.response.userHandle)) : null,
						},
					}
				})
				.then(challenge => {
					console.debug(challenge)
					return challenge
				})
				.catch(error => {
					console.debug('GOT AN ERROR!')
					console.debug(error) // Example: timeout, interaction refused...
				})
		},
		completeAuthentication(challenge) {
			console.debug('TIME TO COMPLETE')

			const redirectUrl = this.redirectUrl

			return finishAuthentication(JSON.stringify(challenge))
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
