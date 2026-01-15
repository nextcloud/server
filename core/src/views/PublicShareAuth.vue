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
import { NcTextField } from '@nextcloud/vue'
import { getCurrentInstance, onMounted, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcGuestContent from '@nextcloud/vue/components/NcGuestContent'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'

const publicShareAuth = loadState<{
	canResendPassword: boolean
	shareType: ShareType
	identityOk?: boolean | null
	invalidPassword?: boolean
}>('core', 'publicShareAuth')

const requestToken = getRequestToken()
const sharingToken = getSharingToken()
const { shareType, invalidPassword, canResendPassword } = publicShareAuth

const hasIdentityCheck = typeof publicShareAuth.identityOk === 'boolean'
const showIdentityCheck = ref(typeof publicShareAuth.identityOk === 'boolean')
const password = ref('')
const email = ref('')

// TODO: Remove when using Vue 3
onMounted(() => {
	const instance = getCurrentInstance()
	if (instance) {
		// @ts-expect-error Vue internals
		(instance.proxy.$el as HTMLElement)?.classList.add('guest-box')
	}
})
</script>

<template>
	<NcGuestContent :class="$style.publicShareAuth">
		<h2>{{ t('core', 'This share is password-protected') }}</h2>
		<form
			v-show="!showIdentityCheck"
			:class="$style.publicShareAuth__form"
			method="POST">
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
			v-if="showIdentityCheck"
			:class="$style.publicShareAuth__form"
			method="POST">
			<NcNoteCard v-if="!hasIdentityCheck" type="info">
				{{ t('core', 'Please type in your email address to request a temporary password') }}
			</NcNoteCard>

			<NcNoteCard v-else :type="publicShareAuth.identityOk ? 'success' : 'error'">
				{{ publicShareAuth.identityOk ? t('core', 'Password sent!') : t('core', 'You are not authorized to request a password for this share') }}
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
				<NcButton wide @click="showIdentityCheck = false">
					{{ t('core', 'Back') }}
				</NcButton>
				<NcButton type="submit" variant="primary" wide>
					{{ t('core', 'Request password') }}
				</NcButton>
			</NcFormBox>
		</form>

		<!-- request password button -->
		<NcButton
			v-if="canResendPassword && !showIdentityCheck"
			:class="$style.publicShareAuth__forgotPasswordButton"
			wide
			@click="showIdentityCheck = true">
			{{ t('core', 'Forgot password') }}
		</NcButton>
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

.publicShareAuth__forgotPasswordButton {
	margin-top: calc(3 * var(--default-grid-baseline));
}
</style>
