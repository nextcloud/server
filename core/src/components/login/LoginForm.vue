<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<form ref="loginForm"
		class="login-form"
		method="post"
		name="login"
		:action="loginActionUrl"
		@submit="submit">
		<fieldset class="login-form__fieldset" data-login-form>
			<NcNoteCard v-if="apacheAuthFailed"
				:title="t('core', 'Server side authentication failed!')"
				type="warning">
				{{ t('core', 'Please contact your administrator.') }}
			</NcNoteCard>
			<NcNoteCard v-if="csrfCheckFailed"
				:heading="t('core', 'Temporary error')"
				type="error">
				{{ t('core', 'Please try again.') }}
			</NcNoteCard>
			<NcNoteCard v-if="messages.length > 0">
				<div v-for="(message, index) in messages"
					:key="index">
					{{ message }}<br>
				</div>
			</NcNoteCard>
			<NcNoteCard v-if="internalException"
				:class="t('core', 'An internal error occurred.')"
				type="warning">
				{{ t('core', 'Please try again or contact your administrator.') }}
			</NcNoteCard>
			<div id="message"
				class="hidden">
				<img class="float-spinner"
					alt=""
					:src="loadingIcon">
				<span id="messageText" />
				<!-- the following div ensures that the spinner is always inside the #message div -->
				<div style="clear: both;" />
			</div>
			<h2 class="login-form__headline" data-login-form-headline v-html="headline" />
			<NcTextField id="user"
				ref="user"
				:label="t('core', 'Account name or email')"
				name="user"
				:value.sync="user"
				:class="{shake: invalidPassword}"
				autocapitalize="none"
				:spellchecking="false"
				:autocomplete="autoCompleteAllowed ? 'username' : 'off'"
				required
				data-login-form-input-user
				@change="updateUsername" />

			<NcPasswordField id="password"
				ref="password"
				name="password"
				:class="{shake: invalidPassword}"
				:value.sync="password"
				:spellchecking="false"
				autocapitalize="none"
				:autocomplete="autoCompleteAllowed ? 'current-password' : 'off'"
				:label="t('core', 'Password')"
				:helper-text="errorLabel"
				:error="isError"
				data-login-form-input-password
				required />

			<LoginButton data-login-form-submit :loading="loading" />

			<input v-if="redirectUrl"
				type="hidden"
				name="redirect_url"
				:value="redirectUrl">
			<input type="hidden"
				name="timezone"
				:value="timezone">
			<input type="hidden"
				name="timezone_offset"
				:value="timezoneOffset">
			<input type="hidden"
				name="requesttoken"
				:value="OC.requestToken">
			<input v-if="directLogin"
				type="hidden"
				name="direct"
				value="1">
		</fieldset>
	</form>
</template>

<script>
import { generateUrl, imagePath } from '@nextcloud/router'

import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

import LoginButton from './LoginButton.vue'

export default {
	name: 'LoginForm',

	components: {
		LoginButton,
		NcPasswordField,
		NcTextField,
		NcNoteCard,
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
		directLogin: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			loading: false,
			timezone: (new Intl.DateTimeFormat())?.resolvedOptions()?.timeZone,
			timezoneOffset: (-new Date().getTimezoneOffset() / 60),
			headline: t('core', 'Log in to {productName}', { productName: OC.theme.name }),
			user: '',
			password: '',
		}
	},

	computed: {
		isError() {
			return this.invalidPassword || this.userDisabled
				|| this.throttleDelay > 5000
		},
		errorLabel() {
			if (this.invalidPassword) {
				return t('core', 'Wrong username or password.')
			}
			if (this.userDisabled) {
				return t('core', 'User disabled')
			}
			if (this.throttleDelay > 5000) {
				return t('core', 'We have detected multiple invalid login attempts from your IP. Therefore your next login is throttled up to 30 seconds.')
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
	},

	mounted() {
		if (this.username === '') {
			this.$refs.user.$refs.inputField.$refs.input.focus()
		} else {
			this.user = this.username
			this.$refs.password.$refs.inputField.$refs.input.focus()
		}
	},

	methods: {
		updateUsername() {
			this.$emit('update:username', this.user)
		},
		submit() {
			this.loading = true
			this.$emit('submit')
		},
	},
}
</script>

<style lang="scss" scoped>
.login-form {
	text-align: left;
	font-size: 1rem;

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
}
</style>
