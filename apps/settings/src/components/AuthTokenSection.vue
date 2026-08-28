<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

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
			:count="authTokenStore.revocableCount"
			:wipe-pending-count="authTokenStore.wipePendingCount"
			:open.sync="revokeAllDialogOpen"
			@confirm="revokeAllOthers" />
	</NcSettingsSection>
</template>

<script lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import AuthTokenList from './AuthTokenList.vue'
import AuthTokenRevokeAllDialog from './AuthTokenRevokeAllDialog.vue'
import AuthTokenSetup from './AuthTokenSetup.vue'
import { useAuthTokenStore } from '../store/authtoken.ts'

export default defineComponent({
	name: 'AuthTokenSection',
	components: {
		AuthTokenList,
		AuthTokenRevokeAllDialog,
		AuthTokenSetup,
		NcButton,
		NcSettingsSection,
	},

	setup() {
		const authTokenStore = useAuthTokenStore()
		return { authTokenStore }
	},

	data() {
		return {
			canCreateToken: loadState('settings', 'can_create_app_token'),
			revokeAllDialogOpen: false,
		}
	},

	methods: {
		t,

		revokeAllOthers() {
			this.authTokenStore.deleteAllOtherTokens()
		},
	},
})
</script>

<style lang="scss" scoped>
.auth-token-section__revoke-all {
	margin-block-start: calc(var(--default-grid-baseline) * 6);

	&-hint {
		color: var(--color-text-maxcontrast);
		margin-block-start: calc(var(--default-grid-baseline) * 2);
	}
}
</style>
