<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import LoginForm from '../components/login/LoginForm.vue'
import PasswordLessLoginForm from '../components/login/PasswordLessLoginForm.vue'
import ResetPassword from '../components/login/ResetPassword.vue'
import UpdatePassword from '../components/login/UpdatePassword.vue'
import { wipeBrowserStorages } from '../utils/xhr-request.js'

interface AlternativeLogin {
	name: string
	href: string
	class?: string
}

const query = new URLSearchParams(window.location.search)
if (query.get('clear') === '1') {
	wipeBrowserStorages()
}

const loading = ref(false)
const user = ref(loadState('core', 'loginUsername', ''))
const passwordlessLogin = ref(false)
const resetPassword = ref(false)

const errors = loadState<string[]>('core', 'loginErrors', [])
const messages = loadState<string[]>('core', 'loginMessages', [])
const redirectUrl = loadState<string | false>('core', 'loginRedirectUrl', false)
const throttleDelay = loadState('core', 'loginThrottleDelay', 0)
const canResetPassword = loadState('core', 'loginCanResetPassword', false)
const resetPasswordLink = loadState('core', 'loginResetPasswordLink', '')
const autoCompleteAllowed = loadState('core', 'loginAutocomplete', true)
const remembermeAllowed = loadState('core', 'loginCanRememberme', true)
const resetPasswordTarget = loadState('core', 'resetPasswordTarget', '')
const directLogin = query.get('direct') === '1'
const hasPasswordless = loadState('core', 'webauthn-available', false)
const alternativeLogins = loadState<AlternativeLogin[]>('core', 'alternativeLogins', [])
const isHttps = window.location.protocol === 'https:'
const isLocalhost = window.location.hostname === 'localhost'
const hideLoginForm = loadState('core', 'hideLoginForm', false)
const emailStates = loadState<string[]>('core', 'emailStates', [])

/**
 * Continue with the login form once the new password is set.
 */
function passwordResetFinished() {
	window.location.href = generateUrl('login') + '?direct=1'
}
</script>

<template>
	<div class="guest-box" :class="$style.loginBox">
		<template v-if="!hideLoginForm || directLogin">
			<Transition
				mode="out-in"
				:enterActiveClass="$style.fadeActive"
				:leaveActiveClass="$style.fadeActive"
				:enterFromClass="$style.fadeHidden"
				:leaveToClass="$style.fadeHidden">
				<div v-if="!passwordlessLogin && !resetPassword && resetPasswordTarget === ''" :class="$style.loginBox__wrapper">
					<LoginForm
						v-model:username="user"
						:redirectUrl="redirectUrl"
						:directLogin="directLogin"
						:messages="messages"
						:errors="errors"
						:throttleDelay="throttleDelay"
						:autoCompleteAllowed="autoCompleteAllowed"
						:remembermeAllowed="remembermeAllowed"
						:emailStates="emailStates"
						@submit="loading = true" />
					<NcButton
						v-if="hasPasswordless"
						variant="tertiary"
						wide
						@click.prevent="passwordlessLogin = true">
						{{ t('core', 'Log in with a device') }}
					</NcButton>
					<NcButton
						v-if="canResetPassword && resetPasswordLink !== ''"
						id="lost-password"
						:href="resetPasswordLink"
						variant="tertiary-no-background"
						wide>
						{{ t('core', 'Forgot password?') }}
					</NcButton>
					<NcButton
						v-else-if="canResetPassword && !resetPassword"
						id="lost-password"
						variant="tertiary"
						wide
						@click.prevent="resetPassword = true">
						{{ t('core', 'Forgot password?') }}
					</NcButton>
				</div>
				<div
					v-else-if="!loading && passwordlessLogin"
					key="reset-pw-less"
					class="login-additional"
					:class="$style.loginBox__wrapper">
					<PasswordLessLoginForm
						v-model:username="user"
						:redirectUrl="redirectUrl"
						:autoCompleteAllowed="autoCompleteAllowed"
						:isHttps="isHttps"
						:isLocalhost="isLocalhost" />
					<NcButton
						variant="tertiary"
						:aria-label="t('core', 'Back to login form')"
						wide
						@click="passwordlessLogin = false">
						{{ t('core', 'Back') }}
					</NcButton>
				</div>
				<div
					v-else-if="!loading && canResetPassword"
					key="reset-can-reset"
					class="login-additional">
					<div class="lost-password-container">
						<ResetPassword
							v-if="resetPassword"
							v-model:username="user"
							@abort="resetPassword = false" />
					</div>
				</div>
				<div v-else-if="resetPasswordTarget !== ''">
					<UpdatePassword
						:resetPasswordTarget="resetPasswordTarget"
						@done="passwordResetFinished" />
				</div>
			</Transition>
		</template>
		<NcNoteCard v-else type="info" :heading="t('core', 'Login form is disabled.')">
			{{ t('core', 'The Nextcloud login form is disabled. Use another login option if available or contact your administration.') }}
		</NcNoteCard>

		<div id="alternative-logins" :class="$style.loginBox__alternativeLogins">
			<NcButton
				v-for="(alternativeLogin, index) in alternativeLogins"
				:key="index"
				variant="secondary"
				wide
				:class="[alternativeLogin.class]"
				role="link"
				:href="alternativeLogin.href">
				{{ alternativeLogin.name }}
			</NcButton>
		</div>
	</div>
</template>

<style module lang="scss">
.loginBox {
	// Same size as dashboard panels
	width: 320px;
	box-sizing: border-box;

	&__wrapper {
		display: flex;
		flex-direction: column;
		gap: calc(2 * var(--default-grid-baseline));
	}

	&__alternativeLogins {
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}
}

.fadeActive {
	transition: opacity .3s;
}

.fadeHidden {
	opacity: 0;
}
</style>
