<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<fieldset class="ldap-wizard__server">
		<div class="ldap-wizard__server__line">
			<NcButton :aria-label="t('user_ldap', 'Copy current configuration into new directory binding')"
				@click="() => ldapConfigsStore.copyConfig(ldapConfigId)">
				<template #icon>
					<ContentCopy :size="20" />
				</template>
			</NcButton>
			<NcButton type="error"
				:aria-label="t('user_ldap', 'Delete the current configuration')"
				@click="() => ldapConfigsStore.removeConfig(ldapConfigId)">
				<template #icon>
					<Delete :size="20" />
				</template>
			</NcButton>
		</div>

		<div class="ldap-wizard__server__line">
			<NcTextField :value.sync="ldapConfig.ldapHost"
				:helper-text="t('user_ldap', 'You can omit the protocol, unless you require SSL. If so, start with ldaps://')"
				:placeholder="t('user_ldap', 'Host')"
				autocomplete="off" />
			<div class="ldap-wizard__server__host__port">
				<NcTextField :value.sync="ldapConfig.ldapPort"
					:placeholder="t('user_ldap', 'Port')"
					type="number"
					autocomplete="off" />
				<NcButton :disabled="currentWizardActions.includes('guessPortAndTLS')" @click="guessPortAndTLS">
					{{ t('user_ldap', 'Detect Port') }}
				</NcButton>
			</div>
		</div>

		<div class="ldap-wizard__server__line">
			<NcTextField :value.sync="localLdapAgentName"
				:helper-text="t('user_ldap', 'The DN of the client user with which the bind shall be done, e.g. uid=agent,dc=example,dc=com. For anonymous access, leave DN and Password empty.')"
				:placeholder="t('user_ldap', 'User DN')"
				autocomplete="off" />
		</div>

		<div class="ldap-wizard__server__line">
			<NcTextField type="password"
				:helper-text="t('user_ldap', 'For anonymous access, leave DN and Password empty.')"
				:value.sync="localLdapAgentPassword"
				:placeholder="t('user_ldap', 'Password')"
				autocomplete="off" />

			<NcButton :disabled="!needsToSaveCredentials" @click="updateCredentials">
				{{ t('user_ldap', 'Save Credentials') }}
			</NcButton>
		</div>

		<div class="ldap-wizard__server__line">
			<NcTextArea :label="t('user_ldap', 'Base DN')"
				:value.sync="ldapConfig.ldapBase"
				:placeholder="t('user_ldap', 'One Base DN per line')"
				:helper-text="t('user_ldap', 'You can specify Base DN for users and groups in the Advanced tab')" />

			<NcButton :disabled="currentWizardActions.includes('guessBaseDN')" @click="guessBaseDN">
				{{ t('user_ldap', 'Detect Base DN') }}
			</NcButton>
			<NcButton :disabled="currentWizardActions.includes('countInBaseDN')" @click="countInBaseDN">
				{{ t('user_ldap', 'Test Base DN') }}
			</NcButton>
		</div>

		<div class="ldap-wizard__server__line">
			<NcCheckboxRadioSwitch :checked="ldapConfig.ldapExperiencedAdmin === '1'"
				:aria-label="t('user_ldap', 'Avoids automatic LDAP requests. Better for bigger setups, but requires some LDAP knowledge.')"
				@update:checked="ldapConfig.ldapExperiencedAdmin = $event ? '1' : '0'">
				{{ t('user_ldap', 'Manually enter LDAP filters (recommended for large directories)') }}
			</NcCheckboxRadioSwitch>
		</div>
	</fieldset>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'

import ContentCopy from 'vue-material-design-icons/ContentCopy.vue'
import Delete from 'vue-material-design-icons/Delete.vue'

import { t } from '@nextcloud/l10n'
import { NcButton, NcTextField, NcTextArea, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { showInfo } from '@nextcloud/dialogs'

import { useLDAPConfigsStore } from '../../store/configs'
import { useWizardStore } from '../../store/wizard'

const ldapConfigsStore = useLDAPConfigsStore()
const { selectedConfigId: ldapConfigId, selectedConfig: ldapConfig } = storeToRefs(ldapConfigsStore)
const { currentWizardActions, callWizardAction } = useWizardStore()

const localLdapAgentName = ref(ldapConfig.value.ldapAgentName)
const localLdapAgentPassword = ref(ldapConfig.value.ldapAgentPassword)
const needsToSaveCredentials = computed(() => {
	return ldapConfig.value.ldapAgentName !== localLdapAgentName.value || ldapConfig.value.ldapAgentPassword !== localLdapAgentPassword.value
})

/**
 *
 */
function updateCredentials() {
	ldapConfig.value.ldapAgentName = localLdapAgentName.value
	ldapConfig.value.ldapAgentPassword = localLdapAgentPassword.value
}

/**
 *
 */
async function guessPortAndTLS() {
	const { changes: { ldap_port: ldapPort } } = await callWizardAction('guessPortAndTLS')
	ldapConfig.value.ldapPort = String(ldapPort)
}

/**
 *
 */
async function guessBaseDN() {
	const { changes: { ldap_base: ldapBase } } = await callWizardAction('guessBaseDN')
	ldapConfig.value.ldapBase = ldapBase
}

/**
 *
 */
async function countInBaseDN() {
	const { changes: { ldap_test_base: ldapTestBase } } = await callWizardAction('countInBaseDN')
	// TODO:use the message from wizardTabElementary.js:287
	showInfo(t('user_ldap', 'Found {count} entries in the given Base DN.', { count: ldapTestBase }))}

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
