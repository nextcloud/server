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
	</NcSettingsSection>
</template>

<script lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import AuthTokenList from './AuthTokenList.vue'
import AuthTokenSetup from './AuthTokenSetup.vue'

export default defineComponent({
	name: 'AuthTokenSection',
	components: {
		AuthTokenList,
		AuthTokenSetup,
		NcSettingsSection,
	},

	data() {
		return {
			canCreateToken: loadState('settings', 'can_create_app_token'),
		}
	},

	methods: {
		t,
	},
})
</script>
