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
		<fieldset class="login-form__fieldset">
			<div v-if="apacheAuthFailed"
				class="warning">
				{{ t('core', 'Server side authentication failed!') }}<br>
				<small>{{ t('core', 'Please contact your administrator.') }}
				</small>
			</div>
			<div v-for="(message, index) in messages"
				:key="index"
				class="warning">
				{{ message }}<br>
			</div>
			<div v-if="internalException"
				class="warning">
				{{ t('core', 'An internal error occurred.') }}<br>
				<small>{{ t('core', 'Please try again or contact your administrator.') }}
				</small>
			</div>
			<div id="message"
				class="hidden">
				<img class="float-spinner"
					alt=""
					:src="loadingIcon">
				<span id="messageText" />
				<!-- the following div ensures that the spinner is always inside the #message div -->
				<div style="clear: both;" />
			</div>
			<NcTextField id="user"
				:label="t('core', 'Username or email')"
				:labelVisible="true"
				ref="user"
				name="user"
				:class="{shake: invalidPassword}"
				:value.sync="user"
				autocapitalize="none"
				:spellchecking="false"
				:autocomplete="autoCompleteAllowed ? 'username' : 'off'"
				:aria-label="t('core', 'Username or email')"
				required
				@change="updateUsername" />

			<NcPasswordField id="password"
				ref="password"
				name="password"
				:labelVisible="true"
				:class="{shake: invalidPassword}"
				:value.sync="password"
				:spellchecking="false"
				autocapitalize="none"
				:autocomplete="autoCompleteAllowed ? 'current-password' : 'off'"
				:label="t('core', 'Password')"
				:helperText="errorLabel"
				:error="isError"
				required />

			<LoginButton :loading="loading" />

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
import jstz from 'jstimezonedetect'
import { generateUrl, imagePath } from '@nextcloud/router'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import Eye from 'vue-material-design-icons/Eye'
import EyeOff from 'vue-material-design-icons/EyeOff'

import LoginButton from './LoginButton'

export default {
	name: 'LoginForm',

	components: {
		NcButton,
		Eye,
		EyeOff,
		LoginButton,
		NcPasswordField,
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
			timezone: jstz.determine().name(),
			timezoneOffset: (-new Date().getTimezoneOffset() / 60),
			user: this.username,
			password: '',
		}
	},

	computed: {
		isError() {
			return this.invalidPassword || this.userDisabled
				|| (this.throttleDelay && this.throttleDelay > 5000)
		},
		errorLabel() {
			if (this.invalidPassword) {
				return t('core', 'Wrong username or password.')
			}
			if (this.userDisabled) {
				return t('core', 'User disabled')
			}
			if (this.throttleDelay && this.throttleDelay > 5000) {
				return t('core', 'We have detected multiple invalid login attempts from your IP. Therefore your next login is throttled up to 30 seconds.')
			}
			return undefined;
		},
		apacheAuthFailed() {
			return this.errors.indexOf('apacheAuthFailed') !== -1
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
			this.$refs.user.focus()
		} else {
			this.$refs.password.focus()
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
		gap: 1rem;
	}
}
</style>
