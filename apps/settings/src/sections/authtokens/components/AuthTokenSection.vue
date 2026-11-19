<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import SettingsColumn from '../../../components/SettingsColumn.vue'
import AuthToken from './AuthToken.vue'
import AuthTokenRevokeAllDialog from './AuthTokenRevokeAllDialog.vue'
import AuthTokenSetup from './AuthTokenSetup.vue'
import { TokenType, useAuthTokenStore } from '../store/authtoken.ts'

const authTokenStore = useAuthTokenStore()

const canCreateToken = loadState<boolean>('settings', 'can_create_app_token')
const revokeAllDialogOpen = ref(false)

const sortedTokens = computed(() => [...authTokenStore.tokens]
	.filter((t) => t.type !== TokenType.ONETIME_TOKEN)
	.sort((t1, t2) => t2.lastActivity - t1.lastActivity))

/**
 * Revoke every token except the current session
 */
function revokeAllOthers() {
	authTokenStore.deleteAllOtherTokens()
}
</script>

<template>
	<SettingsColumn>
		<NcSettingsSection
			:name="t('settings', 'Apps and devices')"
			:description="t('settings', 'Web, desktop and mobile apps currently logged in to your account.')">
			<NcFormBox id="app-tokens-table">
				<AuthTokenSetup v-if="canCreateToken" />
				<AuthToken
					v-for="token in sortedTokens"
					:key="token.id"
					:token="token" />
			</NcFormBox>
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
	</SettingsColumn>
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
