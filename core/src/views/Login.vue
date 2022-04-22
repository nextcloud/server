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
	<div v-if="!hideLoginForm || directLogin">
		<transition name="fade" mode="out-in">
			<div v-if="!passwordlessLogin && !resetPassword && resetPasswordTarget === ''"
				key="login">
				<LoginForm :username.sync="user"
					:redirect-url="redirectUrl"
					:direct-login="directLogin"
					:messages="messages"
					:errors="errors"
					:throttle-delay="throttleDelay"
					:auto-complete-allowed="autoCompleteAllowed"
					@submit="loading = true" />
				<a v-if="canResetPassword && resetPasswordLink !== ''"
					id="lost-password"
					:href="resetPasswordLink">
					{{ t('core', 'Forgot password?') }}
				</a>
				<a v-else-if="canResetPassword && !resetPassword"
					id="lost-password"
					:href="resetPasswordLink"
					@click.prevent="resetPassword = true">
					{{ t('core', 'Forgot password?') }}
				</a>
				<br>
				<template v-if="hasPasswordless">
					<div v-if="countAlternativeLogins"
						class="alternative-logins">
						<a v-if="hasPasswordless"
							class="button"
							:class="{ 'single-alt-login-option': countAlternativeLogins }"
							href="#"
							@click.prevent="passwordlessLogin = true">
							{{ t('core', 'Log in with a device') }}
						</a>
					</div>
					<a v-else
						href="#"
						@click.prevent="passwordlessLogin = true">
						{{ t('core', 'Log in with a device') }}
					</a>
				</template>
			</div>
			<div v-else-if="!loading && passwordlessLogin"
				key="reset"
				class="login-additional">
				<PasswordLessLoginForm :username.sync="user"
					:redirect-url="redirectUrl"
					:auto-complete-allowed="autoCompleteAllowed"
					:is-https="isHttps"
					:is-localhost="isLocalhost"
					:has-public-key-credential="hasPublicKeyCredential"
					@submit="loading = true" />
				<a href="#" @click.prevent="passwordlessLogin = false">
					{{ t('core', 'Back') }}
				</a>
			</div>
			<div v-else-if="!loading && canResetPassword"
				key="reset"
				class="login-additional">
				<div class="lost-password-container">
					<ResetPassword v-if="resetPassword"
						:username.sync="user"
						:reset-password-link="resetPasswordLink"
						@abort="resetPassword = false" />
				</div>
			</div>
			<div v-else-if="resetPasswordTarget !== ''">
				<UpdatePassword :username.sync="user"
					:reset-password-target="resetPasswordTarget"
					@done="passwordResetFinished" />
			</div>
		</transition>
	</div>
	<div v-else>
		<transition name="fade" mode="out-in">
			<div class="warning">
				{{ t('core', 'Login form is disabled.') }}<br>
				<small>{{ t('core', 'Please contact your administrator.') }}
				</small>
			</div>
		</transition>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import queryString from 'query-string'

import LoginForm from '../components/login/LoginForm.vue'
import PasswordLessLoginForm from '../components/login/PasswordLessLoginForm.vue'
import ResetPassword from '../components/login/ResetPassword.vue'
import UpdatePassword from '../components/login/UpdatePassword.vue'

const query = queryString.parse(location.search)
if (query.clear === '1') {
	try {
		window.localStorage.clear()
		window.sessionStorage.clear()
		console.debug('Browser storage cleared')
	} catch (e) {
		console.error('Could not clear browser storage', e)
	}
}

export default {
	name: 'Login',

	components: {
		LoginForm,
		PasswordLessLoginForm,
		ResetPassword,
		UpdatePassword,
	},

	data() {
		return {
			loading: false,
			user: loadState('core', 'loginUsername', ''),
			passwordlessLogin: false,
			resetPassword: false,

			// Initial data
			errors: loadState('core', 'loginErrors', []),
			messages: loadState('core', 'loginMessages', []),
			redirectUrl: loadState('core', 'loginRedirectUrl', false),
			throttleDelay: loadState('core', 'loginThrottleDelay', 0),
			canResetPassword: loadState('core', 'loginCanResetPassword', false),
			resetPasswordLink: loadState('core', 'loginResetPasswordLink', ''),
			autoCompleteAllowed: loadState('core', 'loginAutocomplete', true),
			resetPasswordTarget: loadState('core', 'resetPasswordTarget', ''),
			resetPasswordUser: loadState('core', 'resetPasswordUser', ''),
			directLogin: query.direct === '1',
			hasPasswordless: loadState('core', 'webauthn-available', false),
			countAlternativeLogins: loadState('core', 'countAlternativeLogins', false),
			isHttps: window.location.protocol === 'https:',
			isLocalhost: window.location.hostname === 'localhost',
			hasPublicKeyCredential: typeof (window.PublicKeyCredential) !== 'undefined',
			hideLoginForm: loadState('core', 'hideLoginForm', false),
		}
	},

	methods: {
		passwordResetFinished() {
			this.resetPasswordTarget = ''
			this.directLogin = true
		},
	},
}
</script>

<style>
	.fade-enter-active, .fade-leave-active {
		transition: opacity .3s;
	}
	.fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */ {
		opacity: 0;
	}
</style>
