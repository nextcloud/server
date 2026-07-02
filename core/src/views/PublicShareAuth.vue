<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import type { ShareType } from '@nextcloud/sharing'

import { getRequestToken } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl, getBaseUrl } from '@nextcloud/router'
import { getSharingToken } from '@nextcloud/sharing/public'
import { NcTextField } from '@nextcloud/vue'
import { getCurrentInstance, onMounted, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcGuestContent from '@nextcloud/vue/components/NcGuestContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import IconAlert from 'vue-material-design-icons/Alert.vue'
import IconCheckMark from 'vue-material-design-icons/Check.vue'
import IconLockClock from 'vue-material-design-icons/LockClock.vue'

const publicShareAuth = loadState<{
	canResendPassword: boolean
	shareType: ShareType
	showPasswordReset?: boolean
	invalidPassword?: boolean
	otpInfo?: {
		name: string
		description: string
		recipientPattern: string
		maskedRecipient: string
	}
}>('core', 'publicShareAuth')

const requestToken = getRequestToken()
const sharingToken = getSharingToken()
const { shareType, invalidPassword, canResendPassword } = publicShareAuth

const isPasswordResetProcessed = !!publicShareAuth.showPasswordReset
const showPasswordReset = ref(publicShareAuth.showPasswordReset ?? false)
const password = ref('')
const email = ref('')
const otpInfo = ref(publicShareAuth.otpInfo)
const otpRequestStatus = ref<'initial' | 'in-progress' | 'success' | 'error'>('initial')

// TODO: Remove when using Vue 3
onMounted(() => {
	const instance = getCurrentInstance()
	if (instance) {
		// @ts-expect-error Vue internals
		(instance.proxy.$el as HTMLElement)?.classList.add('guest-box')
	}
})

/**
 * request new OTP
 */
async function requestOTP() {
	const url = generateUrl(
		'/s/{token}/requestotp',
		{ token: sharingToken },
		{ baseURL: getBaseUrl() },
	)
	otpRequestStatus.value = 'in-progress'
	try {
		const result = await axios.get(url, {
			withXSRFToken: true,
			validateStatus: (status) => status === 201,
		})
		if (result.status === 201) {
			otpRequestStatus.value = 'success'
		} else {
			otpRequestStatus.value = 'error'
		}
	} catch (e) {
		otpRequestStatus.value = 'error'
		throw e
	}
}

</script>

<template>
	<NcGuestContent :class="$style.publicShareAuth">
		<h2 v-if="otpInfo">{{ t('core', 'A one-time password is required to access this share') }}</h2>
		<h2 v-else>{{ t('core', 'This share is password-protected') }}</h2>
		<form
			v-show="!showPasswordReset"
			:class="$style.publicShareAuth__form"
			method="POST">
			<NcNoteCard v-if="invalidPassword" type="error">
				{{ t('core', 'The password is wrong or expired. Please try again or request a new one.') }}
			</NcNoteCard>
			<NcNoteCard v-if="otpRequestStatus === 'error'" type="error">
				{{ t('core', 'Requesting a one-time password failed. Please try again or contact your administrator.') }}
			</NcNoteCard>

			<NcPasswordField
				v-model="password"
				:label="t('core', 'Password')"
				autofocus
				autocomplete="new-password"
				autocapitalize="off"
				spellcheck="false"
				name="password" />

			<input type="hidden" name="requesttoken" :value="requestToken">
			<input type="hidden" name="sharingToken" :value="sharingToken">
			<input type="hidden" name="sharingType" :value="shareType">

			<NcButton type="submit" variant="primary" wide>
				{{ t('core', 'Submit') }}
			</NcButton>
		</form>

		<form
			v-if="showPasswordReset"
			:class="$style.publicShareAuth__form"
			method="POST">
			<NcNoteCard type="info">
				{{ isPasswordResetProcessed
					? t('core', 'If the email address was correct then you will receive an email with the password.')
					: t('core', 'Please type in your email address to request a temporary password')
				}}
			</NcNoteCard>

			<NcTextField
				v-model="email"
				type="email"
				name="identityToken"
				:label="t('core', 'Email address')" />
			<input type="hidden" name="requesttoken" :value="requestToken">
			<input type="hidden" name="sharingToken" :value="sharingToken">
			<input type="hidden" name="sharingType" :value="shareType">
			<input type="hidden" name="passwordRequest" value="">

			<NcFormBox row>
				<NcButton wide @click="showPasswordReset = false">
					{{ t('core', 'Back') }}
				</NcButton>
				<NcButton type="submit" variant="primary" wide>
					{{ t('core', 'Request password') }}
				</NcButton>
			</NcFormBox>
		</form>

		<!-- request password button -->
		<NcButton
			v-if="canResendPassword && !showPasswordReset"
			:class="$style.publicShareAuth__forgotPasswordButton"
			wide
			@click="showPasswordReset = true">
			{{ t('core', 'Forgot password') }}
		</NcButton>
		<NcFormBox :class="[$style.publicShareAuth__otpRequestBox, $style['publicShareAuth__icon_' + otpRequestStatus] ?? '']">
			<NcFormBoxButton
				:label="t('core', 'Request One-Time Password')"
				:description="t('core', 'Send a {providerName} with a one-time password to \'{recipient}\'', {providerName: otpInfo?.name, recipient: otpInfo?.maskedRecipient})"
				@click="requestOTP()">
				<template #icon>
					<IconLockClock v-if="otpRequestStatus === 'initial'" />
					<NcLoadingIcon v-else-if="otpRequestStatus === 'in-progress'" />
					<div
						v-else-if="otpRequestStatus === 'success'"
						:class="$style.publicShareAuth__icon_success">
						<IconCheckMark />
					</div>
					<div
						v-else-if="otpRequestStatus === 'error'"
						:class="$style.publicShareAuth__icon_error">
						<IconAlert />
					</div>
				</template>
			</NcFormBoxButton>
		</NcFormBox>
	</NcGuestContent>
</template>

<style module>
.publicShareAuth {
	max-width: 400px !important;
}

.publicShareAuth__form {
	display: flex;
	flex-direction: column;
	gap: calc(2 * var(--default-grid-baseline));
}

.publicShareAuth__forgotPasswordButton, .publicShareAuth__otpRequestBox {
	margin-top: calc(3 * var(--default-grid-baseline));
}

.publicShareAuth__icon_error {
	color: var(--color-element-error);
}

.publicShareAuth__icon_success {
	color: var(--color-element-success);
}
</style>
