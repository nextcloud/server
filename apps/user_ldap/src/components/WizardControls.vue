<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<div class="ldap-wizard__controls">
		<NcButton variant="primary" :disabled="loading" @click="testSelectedConfig">
			{{ t('user_ldap', 'Test Configuration') }}
		</NcButton>

		<NcButton
			variant="tertiary"
			href="https://docs.nextcloud.com/server/stable/go.php?to=admin-ldap"
			target="_blank"
			rel="noreferrer noopener">
			<template #icon>
				<Information :size="20" />
			</template>
			<span>{{ t('user_ldap', 'Help') }}</span>
		</NcButton>

		<template v-if="result !== null && !loading">
			<span
				class="ldap-wizard__controls__state_indicator"
				:class="{ 'ldap-wizard__controls__state_indicator--valid': isValide }" />

			<span class="ldap-wizard__controls__state_message">
				{{ result.message }}
			</span>
		</template>

		<NcLoadingIcon v-if="loading" :size="16" />
	</div>
</template>

<script lang="ts" setup>
import { t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'
import { storeToRefs } from 'pinia'
import { computed, ref, watch } from 'vue'
import Information from 'vue-material-design-icons/ContentCopy.vue'
import { testConfiguration } from '../services/ldapConfigService.ts'
import { useLDAPConfigsStore } from '../store/configs.ts'

const props = defineProps<{ configId: string }>()

const ldapConfigsStore = useLDAPConfigsStore()
const { updatingConfig } = storeToRefs(ldapConfigsStore)

const loading = ref(false)
const result = ref<{ success: boolean, message: string } | null>(null)
const isValide = computed(() => result.value?.success)

watch(updatingConfig, () => {
	result.value = null
})

/**
 *
 */
async function testSelectedConfig() {
	try {
		loading.value = true
		result.value = await testConfiguration(props.configId)
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
	min-height: 45px; // Prevents jumping when the message length need two lines.

	& > * {
		flex-shrink: 0;
	}

	&__state_message {
		flex-shrink: 1;
	}

	&__state_indicator {
		width: 16px;
		height: 16px;
		border-radius: 100%;
		background-color: var(--color-element-error);

		&--valid {
			background-color: var(--color-element-success);
		}
	}
}
</style>
