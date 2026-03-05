<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import LoginFlowAuthAppToken from '../components/LoginFlow/LoginFlowAuthAppToken.vue'
import LoginFlowContainer from '../components/LoginFlow/LoginFlowContainer.vue'

const {
	client,
	direct,
	instanceName,
	loginRedirectUrl,
	appTokenUrl,
	stateToken,
} = loadState<{
	client: string
	direct?: boolean
	instanceName: string
	loginRedirectUrl: string
	appTokenUrl: string
	stateToken: string
}>('core', 'loginFlowAuth')

const useAppTokenLogin = ref(false)
</script>

<template>
	<LoginFlowContainer :heading="t('core', 'Connect to your account')">
		<NcNoteCard type="info">
			{{ t('core', 'Please log in before granting "{client}" access to your {instanceName} account.', { client, instanceName }) }}
		</NcNoteCard>

		<NcNoteCard type="warning" :heading="t('core', 'Security warning')">
			{{ t('core', 'If you are not trying to set up a new device or app, someone is trying to trick you into granting them access to your data. In this case do not proceed and instead contact your system administrator.') }}
		</NcNoteCard>

		<NcButton
			v-if="!useAppTokenLogin"
			:class="$style.loginFlowAuth__button"
			:href="loginRedirectUrl"
			variant="primary">
			{{ t('core', 'Log in') }}
		</NcButton>

		<LoginFlowAuthAppToken
			v-else
			:app-token-url="appTokenUrl"
			:direct="direct ?? false"
			:state-token="stateToken" />

		<NcButton
			:class="$style.loginFlowAuth__button"
			variant="tertiary"
			@click="useAppTokenLogin = !useAppTokenLogin">
			{{ useAppTokenLogin ? t('core', 'Log in using password') : t('core', 'Alternative log in using app password') }}
		</NcButton>
	</LoginFlowContainer>
</template>

<style module>
.loginFlowAuth__button {
	margin-top: 0.5rem;
	margin-inline: auto;
	min-width: 50% !important;
}
</style>
