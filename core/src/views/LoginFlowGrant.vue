<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { getRequestToken } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { confirmPassword, isPasswordConfirmationRequired, PwdConfirmationMode } from '@nextcloud/password-confirmation'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import LoginFlowContainer from '../components/LoginFlow/LoginFlowContainer.vue'

const {
	clientIdentifier,
	oauthState,
	providedRedirectUri,

	actionUrl,
	client,
	direct,
	instanceName,
	stateToken,
	userDisplayName,
	userId,
} = loadState<{
	clientIdentifier?: string
	oauthState?: string
	providedRedirectUri?: string

	actionUrl: string
	client: string
	direct: boolean
	instanceName: string
	stateToken: string
	userId: string
	userDisplayName: string
}>('core', 'loginFlowGrant')

const requestToken = getRequestToken()

/**
 * Handle submit event to confirm password if required
 *
 * @param event - The submit event
 */
async function onSubmit(event: SubmitEvent) {
	if (isPasswordConfirmationRequired(PwdConfirmationMode.Lax)) {
		event.preventDefault()
		event.stopPropagation()

		await confirmPassword()
		;(event.target as HTMLFormElement).submit()
		return false
	}
}
</script>

<template>
	<LoginFlowContainer :heading="t('core', 'Account access')">
		<NcNoteCard type="info">
			{{ t('core', 'Currently logged in as {userDisplayName} ({userId}).', { userDisplayName, userId }) }}
			<br>
			{{ t('core', 'You are about to grant "{client}" access to your {instanceName} account.', { client, instanceName }) }}
		</NcNoteCard>

		<form method="POST" :action="actionUrl" @submit="onSubmit">
			<input type="hidden" name="requesttoken" :value="requestToken">
			<input type="hidden" name="stateToken" :value="stateToken">

			<input
				v-if="direct"
				type="hidden"
				name="direct"
				value="1">
			<input
				v-if="clientIdentifier !== undefined"
				type="hidden"
				name="clientIdentifier"
				:value="clientIdentifier">
			<input
				v-if="oauthState !== undefined"
				type="hidden"
				name="oauthState"
				:value="oauthState">
			<input
				v-if="providedRedirectUri !== undefined"
				type="hidden"
				name="providedRedirectUri"
				:value="providedRedirectUri">

			<NcButton :class="$style.loginFlowGrant__button" type="submit" variant="primary">
				{{ t('core', 'Grant access') }}
			</NcButton>
		</form>
	</LoginFlowContainer>
</template>

<style module>
.loginFlowGrant__button {
	margin-top: 0.5rem;
	margin-inline: auto;
	min-width: 50% !important;
}
</style>
