<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import type { ShareType } from '@nextcloud/sharing'

import { getRequestToken } from '@nextcloud/auth'
import axios, { isAxiosError } from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl, getBaseUrl } from '@nextcloud/router'
import { getSharingToken } from '@nextcloud/sharing/public'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcTextField from '@nextcloud/vue/components/NcTextField'
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
const otpInfo = publicShareAuth.otpInfo
const otpRequestStatus = ref<'initial' | 'in-progress' | 'success' | 'throttled' | 'error'>('initial')

/**
 * Request a new one-time password for the share
 */
async function requestOTP() {
	const url = generateUrl(
		'/s/{token}/requestotp',
		{ token: sharingToken },
		{ baseURL: getBaseUrl() },
	)
	if (otpRequestStatus.value === 'in-progress') {
		return
	}
	otpRequestStatus.value = 'in-progress'
	try {
		await axios.post(url)
		otpRequestStatus.value = 'success'
	} catch (error) {
		otpRequestStatus.value = isAxiosError(error) && error.response?.status === 429 ? 'throttled' : 'error'
	}
}
</script>

<template>
	<div :class="$style.publicShareAuth">
		<h2 v-if="otpInfo">
			{{ t('core', 'A one-time password is required to access this share') }}
		</h2>
		<h2 v-else>
			{{ t('core', 'This share is password-protected') }}
		</h2>
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
				:label="otpInfo ? t('core', 'One-time password') : t('core', 'Password')"
				autofocus
				:autocomplete="otpInfo ? 'one-time-code' : 'new-password'"
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
		<NcFormBox
			v-if="otpInfo"
			:class="[$style.publicShareAuth__otpRequestBox, $style['publicShareAuth__icon_' + otpRequestStatus] ?? '']">
			<NcFormBoxButton
				:label="t('core', 'Request one-time password')"
				:description="t('core', 'Send via {providerName} to \'{recipient}\'', { providerName: otpInfo.name, recipient: otpInfo.maskedRecipient })"
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
						v-else
						:class="$style.publicShareAuth__icon_error">
						<IconAlert />
					</div>
				</template>
			</NcFormBoxButton>
		</NcFormBox>
		<div v-if="otpInfo" role="status">
			<NcNoteCard v-if="otpRequestStatus === 'success'" type="success">
				{{ t('core', 'A one-time password was sent to \'{recipient}\'.', { recipient: otpInfo.maskedRecipient }) }}
			</NcNoteCard>
			<NcNoteCard v-else-if="otpRequestStatus === 'throttled'" type="warning">
				{{ t('core', 'A one-time password was sent less than a minute ago. Please wait before requesting a new one.') }}
			</NcNoteCard>
		</div>
	</div>
</template>

<style module>
.publicShareAuth {
	max-width: 400px;
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
