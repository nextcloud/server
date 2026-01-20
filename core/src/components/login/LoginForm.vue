<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<form
		ref="loginForm"
		class="login-form"
		method="post"
		name="login"
		:action="loginActionUrl"
		@submit="submit">
		<fieldset class="login-form__fieldset" data-login-form>
			<NcNoteCard
				v-if="apacheAuthFailed"
				:title="t('core', 'Server side authentication failed!')"
				type="warning">
				{{ t('core', 'Please contact your administrator.') }}
			</NcNoteCard>
			<NcNoteCard
				v-if="csrfCheckFailed"
				:heading="t('core', 'Session error')"
				type="error">
				{{ t('core', 'It appears your session token has expired, please refresh the page and try again.') }}
			</NcNoteCard>
			<NcNoteCard v-if="messages.length > 0">
				<div
					v-for="(message, index) in messages"
					:key="index">
					{{ message }}<br>
				</div>
			</NcNoteCard>
			<NcNoteCard
				v-if="internalException"
				:class="t('core', 'An internal error occurred.')"
				type="warning">
				{{ t('core', 'Please try again or contact your administrator.') }}
			</NcNoteCard>
			<div
				id="message"
				class="hidden">
				<img
					class="float-spinner"
					alt=""
					:src="loadingIcon">
				<span id="messageText" />
				<!-- the following div ensures that the spinner is always inside the #message div -->
				<div style="clear: both;" />
			</div>
			<h2 class="login-form__headline" data-login-form-headline>
				{{ headlineText }}
			</h2>
			<NcTextField
				id="user"
				ref="user"
				v-model="user"
				:label="loginText"
				name="user"
				:maxlength="255"
				:class="{ shake: invalidPassword }"
				autocapitalize="none"
				:spellchecking="false"
				:autocomplete="autoCompleteAllowed ? 'username' : 'off'"
				required
				:error="userNameInputLengthIs255"
				:helper-text="userInputHelperText"
				data-login-form-input-user
				@change="updateUsername" />

			<NcPasswordField
				id="password"
				ref="password"
				v-model="password"
				name="password"
				:class="{ shake: invalidPassword }"
				:spellchecking="false"
				autocapitalize="none"
				:autocomplete="autoCompleteAllowed ? 'current-password' : 'off'"
				:label="t('core', 'Password')"
				:helper-text="errorLabel"
				:error="isError"
				:visible="visible"
				data-login-form-input-password
				required />

			<NcCheckboxRadioSwitch
				v-if="remembermeAllowed"
				id="rememberme"
				ref="rememberme"
				v-model="rememberme"
				name="rememberme"
				value="1"
				data-login-form-input-rememberme>
				{{ t('core', 'Remember me') }}
			</NcCheckboxRadioSwitch>

			<LoginButton data-login-form-submit :loading="loading" />

			<input
				v-if="redirectUrl"
				type="hidden"
				name="redirect_url"
				:value="redirectUrl">
			<input
				type="hidden"
				name="timezone"
				:value="timezone">
			<input
				type="hidden"
				name="timezone_offset"
				:value="timezoneOffset">
			<input
				type="hidden"
				name="requesttoken"
				:value="requestToken">
			<input
				v-if="directLogin"
				type="hidden"
				name="direct"
				value="1">
		</fieldset>
	</form>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl, imagePath } from '@nextcloud/router'
import debounce from 'debounce'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import LoginButton from './LoginButton.vue'
import AuthMixin from '../../mixins/auth.js'

export default {
	name: 'LoginForm',

	components: {
		LoginButton,
		NcCheckboxRadioSwitch,
		NcPasswordField,
		NcTextField,
		NcNoteCard,
	},

	mixins: [AuthMixin],

	props: {
		username: {
			type: String,
			default: '',
		},

		redirectUrl: {
			type: [Boolean, String],
			default: false,
		},

		errors: {
			type: Array,
			default: () => [],
		},

		messages: {
			type: Array,
			default: () => [],
		},

		throttleDelay: {
			type: Number,
			default: 0,
		},

		autoCompleteAllowed: {
			type: Boolean,
			default: true,
		},

		remembermeAllowed: {
			type: Boolean,
			default: true,
		},

		directLogin: {
			type: Boolean,
			default: false,
		},

		emailStates: {
			type: Array,
			default() {
				return []
			},
		},
	},

	setup() {
		// non reactive props
		return {
			t,

			// Disable escape and sanitize to prevent special characters to be html escaped
			// For example "J's cloud" would be escaped to "J&#39; cloud". But we do not need escaping as Vue does this in `v-text` automatically
			headlineText: t('core', 'Log in to {productName}', { productName: OC.theme.name }, undefined, { sanitize: false, escape: false }),

			loginTimeout: loadState('core', 'loginTimeout', 300),
			requestToken: window.OC.requestToken,
			timezone: (new Intl.DateTimeFormat())?.resolvedOptions()?.timeZone,
			timezoneOffset: (-new Date().getTimezoneOffset() / 60),
		}
	},

	data(props) {
		return {
			loading: false,
			user: props.username,
			password: '',
			rememberme: ['1'],
			visible: false,
		}
	},

	computed: {
		/**
		 * Reset the login form after a long idle time (debounced)
		 */
		resetFormTimeout() {
			// Infinite timeout, do nothing
			if (this.loginTimeout <= 0) {
				return () => {}
			}
			// Debounce for given timeout (in seconds so convert to milli seconds)
			return debounce(this.handleResetForm, this.loginTimeout * 1000)
		},

		isError() {
			return this.invalidPassword || this.userDisabled
				|| this.throttleDelay > 5000
		},

		errorLabel() {
			if (this.invalidPassword) {
				return t('core', 'Wrong login or password.')
			}
			if (this.userDisabled) {
				return t('core', 'This account is disabled')
			}
			if (this.throttleDelay > 5000) {
				return t('core', 'Too many incorrect login attempts. Please try again in 30 seconds.')
			}
			return undefined
		},

		apacheAuthFailed() {
			return this.errors.indexOf('apacheAuthFailed') !== -1
		},

		csrfCheckFailed() {
			return this.errors.indexOf('csrfCheckFailed') !== -1
		},

		internalException() {
			return this.errors.indexOf('internalexception') !== -1
		},

		invalidPassword() {
			return this.errors.indexOf('invalidpassword') !== -1
		},

		userDisabled() {
			return this.errors.indexOf('userdisabled') !== -1
		},

		loadingIcon() {
			return imagePath('core', 'loading-dark.gif')
		},

		loginActionUrl() {
			return generateUrl('login')
		},

		emailEnabled() {
			return this.emailStates.every((state) => state === '1')
		},

		loginText() {
			if (this.emailEnabled) {
				return t('core', 'Account name or email')
			}
			return t('core', 'Account name')
		},
	},

	watch: {
		/**
		 * Reset form reset after the password was changed
		 */
		password() {
			this.resetFormTimeout()
		},
	},

	mounted() {
		if (this.username === '') {
			this.$refs.user.$refs.inputField.$refs.input.focus()
		} else {
			this.$refs.password.$refs.inputField.$refs.input.focus()
		}
	},

	methods: {
		/**
		 * Handle reset of the login form after a long IDLE time
		 * This is recommended security behavior to prevent password leak on public devices
		 */
		handleResetForm() {
			this.password = ''
		},

		updateUsername() {
			this.$emit('update:username', this.user)
		},

		submit(event) {
			this.visible = false

			if (this.loading) {
				// Prevent the form from being submitted twice
				event.preventDefault()
				return
			}

			this.loading = true
			this.$emit('submit')
		},
	},
}
</script>

<style lang="scss" scoped>
.login-form {
	text-align: start;
	font-size: 1rem;
	margin: 0;

	&__fieldset {
		width: 100%;
		display: flex;
		flex-direction: column;
		gap: .5rem;
	}

	&__headline {
		text-align: center;
		overflow-wrap: anywhere;
	}

	// Only show the error state if the user interacted with the login box
	:deep(input:invalid:not(:user-invalid)) {
		border-color: var(--color-border-maxcontrast) !important;
	}
}
</style>
