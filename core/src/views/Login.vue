<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="guest-box login-box">
		<template v-if="!hideLoginForm || directLogin">
			<transition name="fade" mode="out-in">
				<div v-if="!passwordlessLogin && !resetPassword && resetPasswordTarget === ''">
					<LoginForm :username.sync="user"
						:redirect-url="redirectUrl"
						:direct-login="directLogin"
						:messages="messages"
						:errors="errors"
						:throttle-delay="throttleDelay"
						:auto-complete-allowed="autoCompleteAllowed"
						:email-states="emailStates"
						@submit="loading = true" />
					<a v-if="canResetPassword && resetPasswordLink !== ''"
						id="lost-password"
						class="login-box__link"
						:href="resetPasswordLink">
						{{ t('core', 'Forgot password?') }}
					</a>
					<a v-else-if="canResetPassword && !resetPassword"
						id="lost-password"
						class="login-box__link"
						:href="resetPasswordLink"
						@click.prevent="resetPassword = true">
						{{ t('core', 'Forgot password?') }}
					</a>
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
					key="reset-pw-less"
					class="login-additional login-passwordless">
					<PasswordLessLoginForm :username.sync="user"
						:redirect-url="redirectUrl"
						:auto-complete-allowed="autoCompleteAllowed"
						:is-https="isHttps"
						:is-localhost="isLocalhost"
						@submit="loading = true" />
					<NcButton type="tertiary"
						:aria-label="t('core', 'Back to login form')"
						:wide="true"
						@click="passwordlessLogin = false">
						{{ t('core', 'Back') }}
					</NcButton>
				</div>
				<div v-else-if="!loading && canResetPassword"
					key="reset-can-reset"
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
		</template>
		<template v-else>
			<transition name="fade" mode="out-in">
				<NcNoteCard type="info" :title="t('core', 'Login form is disabled.')">
					{{ t('core', 'The Nextcloud login form is disabled. Use another login option if available or contact your administration.') }}
				</NcNoteCard>
			</transition>
		</template>

		<div id="alternative-logins" class="alternative-logins">
			<NcButton v-for="(alternativeLogin, index) in alternativeLogins"
				:key="index"
				type="secondary"
				:wide="true"
				:class="[alternativeLogin.class]"
				role="link"
				:href="alternativeLogin.href">
				{{ alternativeLogin.name }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import queryString from 'query-string'

import LoginForm from '../components/login/LoginForm.vue'
import PasswordLessLoginForm from '../components/login/PasswordLessLoginForm.vue'
import ResetPassword from '../components/login/ResetPassword.vue'
import UpdatePassword from '../components/login/UpdatePassword.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

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
		NcButton,
		NcNoteCard,
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
			alternativeLogins: loadState('core', 'alternativeLogins', []),
			isHttps: window.location.protocol === 'https:',
			isLocalhost: window.location.hostname === 'localhost',
			hideLoginForm: loadState('core', 'hideLoginForm', false),
			emailStates: loadState('core', 'emailStates', []),
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

<style lang="scss">
body {
	font-size: var(--default-font-size);
}

.login-box {
	// Same size as dashboard panels
	width: 320px;
	box-sizing: border-box;

	&__link {
		display: block;
		padding: 1rem;
		font-size: var(--default-font-size);
		text-align: center;
		font-weight: normal !important;
	}
}

.fade-enter-active, .fade-leave-active {
	transition: opacity .3s;
}

.fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */ {
	opacity: 0;
}

.alternative-logins {
	display: flex;
	flex-direction: column;
	gap: 0.75rem;

	.button-vue {
		box-sizing: border-box;
	}
}

.login-passwordless {
	.button-vue {
		margin-top: 0.5rem;
	}
}
</style>
