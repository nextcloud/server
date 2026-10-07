<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import type { ShareType } from '@nextcloud/sharing'

import { getRequestToken } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { getSharingToken } from '@nextcloud/sharing/public'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcTextField from '@nextcloud/vue/components/NcTextField'

const publicShareAuth = loadState<{
	canResendPassword: boolean
	shareType: ShareType
	showPasswordReset?: boolean
	invalidPassword?: boolean
}>('core', 'publicShareAuth')

const requestToken = getRequestToken()
const sharingToken = getSharingToken()
const { shareType, invalidPassword, canResendPassword } = publicShareAuth

const isPasswordResetProcessed = !!publicShareAuth.showPasswordReset
const showPasswordReset = ref(publicShareAuth.showPasswordReset ?? false)
const password = ref('')
const email = ref('')

/** Strip pasted whitespace/newlines before the native form POST. */
function onPasswordSubmit(event: Event) {
	const form = event.target as HTMLFormElement
	const input = form.elements.namedItem('password') as HTMLInputElement | null
	if (input) {
		input.value = input.value.trim()
		password.value = input.value
	}
}
</script>

<template>
	<div :class="$style.publicShareAuth">
		<h2>{{ t('core', 'This share is password-protected') }}</h2>
		<form
			v-show="!showPasswordReset"
			:class="$style.publicShareAuth__form"
			method="POST"
			@submit="onPasswordSubmit">
			<NcNoteCard v-if="invalidPassword" type="error">
				{{ t('core', 'The password is wrong or expired. Please try again or request a new one.') }}
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

.publicShareAuth__forgotPasswordButton {
	margin-top: calc(3 * var(--default-grid-baseline));
}
</style>
