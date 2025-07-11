<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="guest-box login-box">
		<template v-if="!hideLoginForm || directLogin">
			<transition name="fade" mode="out-in">
				<div v-if="!passwordlessLogin && !resetPassword && resetPasswordTarget === ''" class="login-box__wrapper">
					<LoginForm :username.sync="user"
						:redirect-url="redirectUrl"
						:direct-login="directLogin"
						:messages="messages"
						:errors="errors"
						:throttle-delay="throttleDelay"
						:auto-complete-allowed="autoCompleteAllowed"
						:email-states="emailStates"
						@submit="loading = true" />
					<NcButton v-if="hasPasswordless"
						type="tertiary"
						wide
						@click.prevent="passwordlessLogin = true">
						{{ t('core', 'Log in with a device') }}
					</NcButton>
					<NcButton v-if="canResetPassword && resetPasswordLink !== ''"
						id="lost-password"
						:href="resetPasswordLink"
						type="tertiary-no-background"
						wide>
						{{ t('core', 'Forgot password?') }}
					</NcButton>
					<NcButton v-else-if="canResetPassword && !resetPassword"
						id="lost-password"
						type="tertiary"
						wide
						@click.prevent="resetPassword = true">
						{{ t('core', 'Forgot password?') }}
					</NcButton>
				</div>
				<div v-else-if="!loading && passwordlessLogin"
					key="reset-pw-less"
					class="login-additional login-box__wrapper">
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

		<div id="alternative-logins" class="login-box__alternative-logins">
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
import { generateUrl } from '@nextcloud/router'

import queryString from 'query-string'

import LoginForm from '../components/login/LoginForm.vue'
import PasswordLessLoginForm from '../components/login/PasswordLessLoginForm.vue'
import ResetPassword from '../components/login/ResetPassword.vue'
import UpdatePassword from '../components/login/UpdatePassword.vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { wipeBrowserStorages } from '../utils/xhr-request.js'

const query = queryString.parse(location.search)
if (query.clear === '1') {
	wipeBrowserStorages()
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
			window.location.href = generateUrl('login')
		},
	},
}
</script>

<style scoped lang="scss">
.login-box {
	// Same size as dashboard panels
	width: 320px;
	box-sizing: border-box;

	&__wrapper {
		display: flex;
		flex-direction: column;
		gap: calc(2 * var(--default-grid-baseline));
	}

	&__alternative-logins {
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}
}

.fade-enter-active, .fade-leave-active {
	transition: opacity .3s;
}

.fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */ {
	opacity: 0;
}
</style>
