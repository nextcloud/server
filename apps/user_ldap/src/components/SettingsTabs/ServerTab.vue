<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<fieldset class="ldap-wizard__server">
		<div class="ldap-wizard__server__line">
			<NcCheckboxRadioSwitch
				:model-value="ldapConfigProxy.ldapConfigurationActive === '1'"
				type="switch"
				:aria-label="t('user_ldap', 'When unchecked, this configuration will be skipped.')"
				@update:model-value="ldapConfigProxy.ldapConfigurationActive = $event ? '1' : '0'">
				{{ t('user_ldap', 'Configuration active') }}
			</NcCheckboxRadioSwitch>

			<NcButton
				:title="t('user_ldap', 'Copy current configuration into new directory binding')"
				@click="ldapConfigsStore.copyConfig(configId)">
				<template #icon>
					<ContentCopy :size="20" />
				</template>
				{{ t('user_ldap', 'Copy configuration') }}
			</NcButton>
			<NcButton
				variant="error"
				@click="ldapConfigsStore.removeConfig(configId)">
				<template #icon>
					<Delete :size="20" />
				</template>
				{{ t('user_ldap', 'Delete configuration') }}
			</NcButton>
		</div>

		<div class="ldap-wizard__server__line">
			<NcTextField
				:model-value="ldapConfigProxy.ldapHost"
				:helper-text="t('user_ldap', 'You can omit the protocol, unless you require SSL. If so, start with ldaps://')"
				:label="t('user_ldap', 'Host')"
				placeholder="ldaps://localhost"
				autocomplete="off"
				@change="(event) => ldapConfigProxy.ldapHost = event.target.value" />
			<div class="ldap-wizard__server__host__port">
				<NcTextField
					:model-value="ldapConfigProxy.ldapPort"
					:label="t('user_ldap', 'Port')"
					placeholder="389"
					type="number"
					autocomplete="off"
					@change="(event) => ldapConfigProxy.ldapPort = event.target.value" />
				<NcButton :disabled="loadingGuessPortAndTLS" @click="guessPortAndTLS">
					{{ t('user_ldap', 'Detect port') }}
				</NcButton>
			</div>
		</div>

		<div class="ldap-wizard__server__line">
			<NcTextField
				v-model="localLdapAgentName"
				:helper-text="t('user_ldap', 'The DN of the client user with which the bind shall be done. For anonymous access, leave DN and Password empty.')"
				:label="t('user_ldap', 'User DN')"
				placeholder="uid=agent,dc=example,dc=com"
				autocomplete="off" />
		</div>

		<div class="ldap-wizard__server__line">
			<NcTextField
				v-model="localLdapAgentPassword"
				type="password"
				:helper-text="t('user_ldap', 'For anonymous access, leave DN and Password empty.')"
				:label="t('user_ldap', 'Password')"
				autocomplete="off" />

			<NcButton :disabled="!needsToSaveCredentials" @click="updateCredentials">
				{{ t('user_ldap', 'Save credentials') }}
			</NcButton>
		</div>

		<div class="ldap-wizard__server__line">
			<NcTextArea
				:label="t('user_ldap', 'Base DN')"
				:model-value="ldapConfigProxy.ldapBase"
				:placeholder="t('user_ldap', 'One Base DN per line')"
				:helper-text="t('user_ldap', 'You can specify Base DN for users and groups in the Advanced tab')"
				@change="(event) => ldapConfigProxy.ldapBase = event.target.value" />

			<NcButton :disabled="loadingGuessBaseDN" @click="guessBaseDN">
				{{ t('user_ldap', 'Detect Base DN') }}
			</NcButton>
			<NcButton :disabled="loadingCountInBaseDN || ldapConfigProxy.ldapBase === ''" @click="countInBaseDN">
				{{ t('user_ldap', 'Test Base DN') }}
			</NcButton>
		</div>
	</fieldset>
</template>

<script lang="ts" setup>
import { showInfo } from '@nextcloud/dialogs'
import { n, t } from '@nextcloud/l10n'
import { NcButton, NcCheckboxRadioSwitch, NcTextArea, NcTextField } from '@nextcloud/vue'
import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import ContentCopy from 'vue-material-design-icons/ContentCopy.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import { callWizard } from '../../services/ldapConfigService.ts'
import { useLDAPConfigsStore } from '../../store/configs.ts'

const props = defineProps<{ configId: string }>()

const ldapConfigsStore = useLDAPConfigsStore()
const { ldapConfigs } = storeToRefs(ldapConfigsStore)
const ldapConfigProxy = computed(() => ldapConfigsStore.getConfigProxy(props.configId))

const loadingGuessPortAndTLS = ref(false)
const loadingCountInBaseDN = ref(false)
const loadingGuessBaseDN = ref(false)

const localLdapAgentName = ref(ldapConfigProxy.value.ldapAgentName)
const localLdapAgentPassword = ref(ldapConfigProxy.value.ldapAgentPassword)
const needsToSaveCredentials = computed(() => {
	return ldapConfigProxy.value.ldapAgentName !== localLdapAgentName.value || ldapConfigProxy.value.ldapAgentPassword !== localLdapAgentPassword.value
})

/**
 *
 */
function updateCredentials() {
	ldapConfigProxy.value.ldapAgentName = localLdapAgentName.value
	ldapConfigProxy.value.ldapAgentPassword = localLdapAgentPassword.value
}

/**
 *
 */
async function guessPortAndTLS() {
	try {
		loadingGuessPortAndTLS.value = true
		const { changes } = await callWizard('guessPortAndTLS', props.configId)
		// Not using ldapConfigProxy to avoid triggering the save logic.
		ldapConfigs.value[props.configId].ldapPort = (changes!.ldap_port as string) ?? ''
	} finally {
		loadingGuessPortAndTLS.value = false
	}
}

/**
 *
 */
async function guessBaseDN() {
	try {
		loadingGuessBaseDN.value = true
		const { changes } = await callWizard('guessBaseDN', props.configId)
		ldapConfigProxy.value.ldapBase = (changes!.ldap_base as string) ?? ''
	} finally {
		loadingGuessBaseDN.value = false
	}
}

/**
 *
 */
async function countInBaseDN() {
	try {
		loadingCountInBaseDN.value = true
		const { changes } = await callWizard('countInBaseDN', props.configId)
		const ldapTestBase = changes!.ldap_test_base as number

		if (ldapTestBase < 1) {
			showInfo(t('user_ldap', 'No object found in the given Base DN. Please revise.'))
		} else if (ldapTestBase > 1000) {
			showInfo(t('user_ldap', 'More than 1,000 directory entries available.'))
		} else {
			showInfo(n(
				'user_ldap',
				'{ldapTestBase} entry available within the provided Base DN',
				'{ldapTestBase} entries available within the provided Base DN',
				ldapTestBase,
				{ ldapTestBase },
			))
		}
	} finally {
		loadingCountInBaseDN.value = false
	}
}
</script>

<style lang="scss" scoped>
.ldap-wizard__server {
	display: flex;
	flex-direction: column;
	gap: 16px;

	button {
		flex-shrink: 0;
	}

	&__line {
		display: flex;
		align-items: start;
		gap: 16px;
	}

	&__host__port {
		display: flex;
		align-items: center;
		flex-shrink: 0;
		gap: 16px;
	}
}
</style>
