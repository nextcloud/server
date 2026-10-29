<!--
 - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { AuthenticationResponseJSON } from '@simplewebauthn/browser'

import { mdiInformationOutline, mdiLockOpen } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { getBaseUrl } from '@nextcloud/router'
import { browserSupportsWebAuthn } from '@simplewebauthn/browser'
import { ref, useTemplateRef } from 'vue'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import LoginButton from './LoginButton.vue'
import {
	finishAuthentication,
	NoValidCredentials,
	startAuthentication,
} from '../../services/WebAuthnAuthenticationService.ts'
import { logger } from '../../utils/logger.ts'

const username = defineModel<string>('username', { default: '' })

const props = withDefaults(defineProps<{
	redirectUrl?: string | false
	autoCompleteAllowed?: boolean
	isHttps?: boolean
	isLocalhost?: boolean
}>(), {
	redirectUrl: false,
	autoCompleteAllowed: true,
	isHttps: false,
	isLocalhost: false,
})

const supportsWebauthn = browserSupportsWebAuthn()
const loginForm = useTemplateRef('loginForm')
const loading = ref(false)
const validCredentials = ref(true)

/**
 * Log in with a WebAuthn device of the entered account.
 */
async function authenticate() {
	if (!loginForm.value?.checkValidity()) {
		return
	}

	logger.debug('passwordless login initiated')

	try {
		const params = await startAuthentication(username.value)
		await completeAuthentication(params)
	} catch (error) {
		if (error instanceof NoValidCredentials) {
			validCredentials.value = false
			return
		}
		logger.debug('passwordless login failed', { error })
	}
}

/**
 * Verify the device response and continue to the requested page.
 *
 * @param challenge - The response of the device
 */
async function completeAuthentication(challenge: AuthenticationResponseJSON) {
	try {
		const { defaultRedirectUrl } = await finishAuthentication(challenge)
		logger.debug('Logged in redirecting')
		if (props.redirectUrl) {
			const redirectUrl = props.redirectUrl.startsWith('/') ? props.redirectUrl : '/' + props.redirectUrl
			window.location.href = getBaseUrl() + redirectUrl
		} else {
			window.location.href = defaultRedirectUrl
		}
	} catch (error) {
		// e.g. timeout or the interaction was refused
		logger.debug('Submitting the passwordless challenge failed', { error })
	}
}
</script>

<template>
	<form
		v-if="(isHttps || isLocalhost) && supportsWebauthn"
		ref="loginForm"
		aria-labelledby="password-less-login-form-title"
		:class="$style.passwordLessLoginForm"
		method="post"
		name="login"
		@submit.prevent>
		<h2 id="password-less-login-form-title">
			{{ t('core', 'Log in with a device') }}
		</h2>

		<NcTextField
			v-model="username"
			required
			:autocomplete="autoCompleteAllowed ? 'on' : 'off'"
			:error="!validCredentials"
			:label="t('core', 'Login or email')"
			:placeholder="t('core', 'Login or email')"
			:helperText="!validCredentials ? t('core', 'Your account is not setup for passwordless login.') : ''" />

		<LoginButton
			v-if="validCredentials"
			:loading="loading"
			@click="authenticate" />
	</form>

	<NcEmptyContent
		v-else-if="!isHttps && !isLocalhost"
		:name="t('core', 'Your connection is not secure')"
		:description="t('core', 'Passwordless authentication is only available over a secure connection.')">
		<template #icon>
			<NcIconSvgWrapper :path="mdiLockOpen" />
		</template>
	</NcEmptyContent>

	<NcEmptyContent
		v-else
		:name="t('core', 'Browser not supported')"
		:description="t('core', 'Passwordless authentication is not supported in your browser.')">
		<template #icon>
			<NcIconSvgWrapper :path="mdiInformationOutline" />
		</template>
	</NcEmptyContent>
</template>

<style module>
.passwordLessLoginForm {
	display: flex;
	flex-direction: column;
	gap: 0.5rem;
	margin: 0;
}
</style>
