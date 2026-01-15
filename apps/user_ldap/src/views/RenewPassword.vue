<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import { getRequestToken } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcGuestContent from '@nextcloud/vue/components/NcGuestContent'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'

const renewPasswordParameters = loadState<{
	user: string
	errors: string[]
	messages: string[]
	cancelRenewUrl: string
	tryRenewPasswordUrl: string
}>('user_ldap', 'renewPasswordParameters')

const hasInvalidPassword = renewPasswordParameters.errors.includes('invalidpassword')

const requestToken = getRequestToken()
const isRenewing = ref(false)

/**
 * Handle the form submission.
 */
function onSubmit() {
	isRenewing.value = true
}
</script>

<template>
	<NcGuestContent>
		<h2>{{ t('user_ldap', 'Please renew your password') }}</h2>
		<NcNoteCard v-if="renewPasswordParameters.messages.length" type="warning">
			<p v-for="(message, index) in renewPasswordParameters.messages" :key="index">
				{{ message }}
			</p>
		</NcNoteCard>
		<NcNoteCard
			v-if="renewPasswordParameters.errors.includes('internalexception')"
			:heading="t('user_ldap', 'An internal error occurred.')"
			:text="t('user_ldap', 'Please try again or contact your administrator.')"
			type="warning" />

		<form
			method="post"
			name="renewpassword"
			:action="renewPasswordParameters.tryRenewPasswordUrl"
			@submit="onSubmit">
			<NcPasswordField
				autofocus
				autocomplete="off"
				autocapitalize="off"
				:error="hasInvalidPassword"
				:helper-text="hasInvalidPassword ? t('user_ldap', 'Wrong password.') : ''"
				:label="t('user_ldap', 'Current password')"
				required
				spellcheck="false"
				name="oldPassword" />
			<NcPasswordField
				autofocus
				autocomplete="off"
				autocapitalize="off"
				:label="t('user_ldap', 'New password')"
				required
				spellcheck="false"
				name="newPassword" />

			<div :class="$style.renewPassword__actions">
				<NcButton :href="renewPasswordParameters.cancelRenewUrl" variant="error">
					{{ t('user_ldap', 'Cancel') }}
				</NcButton>
				<NcButton :disabled="isRenewing" type="submit" variant="primary">
					{{ isRenewing ? t('user_ldap', 'Renewing…') : t('user_ldap', 'Renew password') }}
				</NcButton>
			</div>

			<input type="hidden" name="user" :value="renewPasswordParameters.user">
			<input type="hidden" name="requesttoken" :value="requestToken">
		</form>
	</NcGuestContent>
</template>

<style module>
.renewPassword__actions {
	display: flex;
	justify-content: end;
	gap: var(--default-grid-baseline);
	margin-top: 1rem;
}
</style>
