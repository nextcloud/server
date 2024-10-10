<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<div class="ldap-wizard__controls">
		<!-- TODO -->
		<span class="ldap_config_state_indicator" /> <span class="ldap_config_state_indicator_sign" />

		<NcButton type="tertiary"
			href="https://docs.nextcloud.com/server/stable/go.php?to=admin-ldap"
			target="_blank"
			rel="noreferrer noopener">
			<template #icon>
				<Information :size="20" />
			</template>
			<span>{{ t('user_ldap', 'Help') }}</span>
		</NcButton>

		<NcButton type="primary" :disabled="loading" @click="testSelectedConfig">
			{{ t('user_ldap', 'Test Configuration') }}
		</NcButton>
	</div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { storeToRefs } from 'pinia'

import Information from 'vue-material-design-icons/ContentCopy.vue'

import { t } from '@nextcloud/l10n'
import { NcButton } from '@nextcloud/vue'

import { testConfiguration } from '../services/ldapConfigService'
import { useLDAPConfigsStore } from '../store/configs'

const ldapConfigsStore = useLDAPConfigsStore()
const { selectedConfigId } = storeToRefs(ldapConfigsStore)

const loading = ref(false)

/**
 *
 */
function testSelectedConfig() {
	try {
		loading.value = true
		testConfiguration(selectedConfigId.value)
	} finally {
		loading.value = false
	}
}
</script>
<style lang="scss" scoped>
.ldap-wizard__controls {
	display: flex;
	gap: 16px;
	align-items: center;
	justify-content: end;
}
</style>
