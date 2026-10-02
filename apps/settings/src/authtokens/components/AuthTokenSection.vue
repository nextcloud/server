<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import AuthTokenList from './AuthTokenList.vue'
import AuthTokenRevokeAllDialog from './AuthTokenRevokeAllDialog.vue'
import AuthTokenSetup from './AuthTokenSetup.vue'
import { useAuthTokenStore } from '../store/authtoken.ts'

const authTokenStore = useAuthTokenStore()

const canCreateToken = loadState('settings', 'can_create_app_token')
const revokeAllDialogOpen = ref(false)

/**
 * Revoke every token except the current session
 */
function revokeAllOthers() {
	authTokenStore.deleteAllOtherTokens()
}
</script>

<template>
	<NcSettingsSection
		:name="t('settings', 'Devices & sessions', {}, undefined, { sanitize: false })"
		:description="t('settings', 'Web, desktop and mobile clients currently logged in to your account.')">
		<AuthTokenList />
		<AuthTokenSetup v-if="canCreateToken" />
		<div v-if="authTokenStore.revocableCount > 0" class="auth-token-section__revoke-all">
			<NcButton variant="error" @click="revokeAllDialogOpen = true">
				{{ t('settings', 'Revoke all other sessions') }}
			</NcButton>
			<p class="auth-token-section__revoke-all-hint">
				{{ t('settings', 'Signs out every device and app except this one.') }}
			</p>
		</div>
		<AuthTokenRevokeAllDialog
			v-if="revokeAllDialogOpen"
			v-model:open="revokeAllDialogOpen"
			:count="authTokenStore.revocableCount"
			:wipePendingCount="authTokenStore.wipePendingCount"
			@confirm="revokeAllOthers" />
	</NcSettingsSection>
</template>

<style lang="scss" scoped>
.auth-token-section__revoke-all {
	margin-block-start: calc(var(--default-grid-baseline) * 6);

	&-hint {
		color: var(--color-text-maxcontrast);
		margin-block-start: calc(var(--default-grid-baseline) * 2);
	}
}
</style>
