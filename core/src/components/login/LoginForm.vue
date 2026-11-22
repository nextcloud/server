<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { getRequestToken } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl, imagePath } from '@nextcloud/router'
import debounce from 'debounce'
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import LoginButton from './LoginButton.vue'
import LoginNameInput from './LoginNameInput.vue'

const username = defineModel<string>('username', { default: '' })

const props = withDefaults(defineProps<{
	redirectUrl?: string | false
	errors?: string[]
	messages?: string[]
	throttleDelay?: number
	autoCompleteAllowed?: boolean
	remembermeAllowed?: boolean
	directLogin?: boolean
	emailStates?: string[]
}>(), {
	redirectUrl: false,
	errors: () => [],
	messages: () => [],
	throttleDelay: 0,
	autoCompleteAllowed: true,
	remembermeAllowed: true,
	directLogin: false,
	emailStates: () => [],
})

const emit = defineEmits<{
	submit: []
}>()

// Escaping is left to Vue, otherwise "J's cloud" would be shown as "J&#39;s cloud"
const headlineText = t('core', 'Log in to {productName}', { productName: window.OC.theme.name }, undefined, { sanitize: false, escape: false })
const loginTimeout = loadState('core', 'loginTimeout', 300)
const requestToken = getRequestToken()
const timezone = new Intl.DateTimeFormat().resolvedOptions().timeZone
const timezoneOffset = -new Date().getTimezoneOffset() / 60
const loadingIcon = imagePath('core', 'loading-dark.gif')
const loginActionUrl = generateUrl('login')

const userInput = useTemplateRef('userInput')
const passwordInput = useTemplateRef('passwordInput')

const loading = ref(false)
const password = ref('')
const rememberme = ref(['1'])
const visible = ref(false)

const apacheAuthFailed = computed(() => props.errors.includes('apacheAuthFailed'))
const csrfCheckFailed = computed(() => props.errors.includes('csrfCheckFailed'))
const internalException = computed(() => props.errors.includes('internalexception'))
const invalidPassword = computed(() => props.errors.includes('invalidpassword'))
const userDisabled = computed(() => props.errors.includes('userdisabled'))
const isError = computed(() => invalidPassword.value || userDisabled.value || props.throttleDelay > 5000)
const emailEnabled = computed(() => props.emailStates.every((state) => state === '1'))

const errorLabel = computed(() => {
	if (invalidPassword.value) {
		return t('core', 'Wrong login or password.')
	}
	if (userDisabled.value) {
		return t('core', 'This account is disabled')
	}
	if (props.throttleDelay > 5000) {
		return t('core', 'Too many failed login attempts from your location. Try again in 30 seconds.')
	}
	return undefined
})

// Clearing the password after a long idle time prevents leaking it on public devices
if (loginTimeout > 0) {
	watch(password, debounce(() => {
		password.value = ''
	}, loginTimeout * 1000))
}

onMounted(() => {
	if (username.value === '') {
		userInput.value?.focus()
	} else {
		passwordInput.value?.focus()
	}
})

/**
 * Submit the form natively, but only once.
 *
 * @param event - The submit event
 */
function submit(event: SubmitEvent) {
	visible.value = false

	if (loading.value) {
		event.preventDefault()
		return
	}

	loading.value = true
	emit('submit')
}
</script>

<template>
	<form
		:class="$style.loginForm"
		method="post"
		name="login"
		:action="loginActionUrl"
		@submit="submit">
		<fieldset :class="$style.loginForm__fieldset" data-login-form>
			<NcNoteCard
				v-if="apacheAuthFailed"
				:heading="t('core', 'Server side authentication failed!')"
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
				:heading="t('core', 'An internal error occurred.')"
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
			<h2 :class="$style.loginForm__headline" data-login-form-headline>
				{{ headlineText }}
			</h2>
			<LoginNameInput
				id="user"
				ref="userInput"
				v-model:user="username"
				:class="{ shake: invalidPassword }"
				:autoCompleteAllowed="autoCompleteAllowed"
				:allowEmail="emailEnabled"
				name="user"
				required
				:error="isError"
				data-login-form-input-user />

			<NcPasswordField
				id="password"
				ref="passwordInput"
				v-model="password"
				name="password"
				:class="{ shake: invalidPassword }"
				spellcheck="false"
				autocapitalize="none"
				:autocomplete="autoCompleteAllowed ? 'current-password' : 'off'"
				:label="t('core', 'Password')"
				:helperText="errorLabel"
				:error="isError"
				:visible="visible"
				data-login-form-input-password
				required />

			<NcCheckboxRadioSwitch
				v-if="remembermeAllowed"
				id="rememberme"
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

<style module lang="scss">
.loginForm {
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
	:global(input:invalid:not(:user-invalid)) {
		border-color: var(--color-border-maxcontrast) !important;
	}
}
</style>
