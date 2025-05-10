<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<form class="ldap-wizard">
		<h2>{{ t('user_ldap', 'LDAP/AD integration') }}</h2>

		<div class="ldap-wizard__config-selection">
			<NcSelect v-model="selectedConfigId"
				:options="Object.keys(ldapConfigs)"
				:input-label="t('user_ldap', 'Select LDAP Config')">
				<template #option="{label: configId}">
					{{ `${configId}: ${ldapConfigs[configId].ldapHost}` }}
				</template>
				<template #selected-option="{label: configId}">
					{{ `${configId}: ${ldapConfigs[configId].ldapHost}` }}
				</template>
			</NcSelect>
			<NcButton :label="t('user_ldap','Create New Config')" class="ldap-wizard__config-selection__create-button" @click="ldapConfigsStore.create">
				<template #icon>
					<Plus :size="20" />
				</template>
			</NcButton>
		</div>

		<NcNoteCard v-if="!ldapModuleInstalled" type="warning" :text="t('user_ldap', 'The PHP LDAP module is not installed, the backend will not work. Please ask your system administrator to install it.')" />

		<div v-if="ldapModuleInstalled && selectedConfig !== undefined" class="ldap-wizard__tab-container">
			<div class="ldap-wizard__tab-selection-container">
				<div class="ldap-wizard__tab-selection">
					<NcCheckboxRadioSwitch v-for="(tabLabel, tabId) in leftTabs"
						:key="tabId"
						:button-variant="true"
						:checked.sync="selectedTab"
						:value="tabId"
						type="radio"
						button-variant-grouped="horizontal">
						{{ tabLabel }}
					</NcCheckboxRadioSwitch>
				</div>
				<div class="ldap-wizard__tab-selection">
					<NcCheckboxRadioSwitch v-for="(tabLabel, tabId) in rightTabs"
						:key="tabId"
						:button-variant="true"
						:checked.sync="selectedTab"
						:value="tabId"
						type="radio"
						button-variant-grouped="horizontal">
						{{ tabLabel }}
					</NcCheckboxRadioSwitch>
				</div>
			</div>

			<ServerTab v-if="selectedTab === 'server'" />
			<UsersTab v-else-if="selectedTab === 'users'" />
			<LoginTab v-else-if="selectedTab === 'login'" />
			<GroupsTab v-else-if="selectedTab === 'groups'" />
			<ExpertTab v-else-if="selectedTab === 'expert'" />
			<AdvancedTab v-else-if="selectedTab === 'advanced'" />

			<WizardControls class="ldap-wizard__controls" />
		</div>

		<div class="ldap-wizard__clear-mapping">
			<strong>{{ t('user_ldap', 'Username-LDAP User Mapping') }}</strong>
			{{ t('user_ldap', 'Usernames are used to store and assign metadata. In order to precisely identify and recognize users, each LDAP user will have an internal username. This requires a mapping from username to LDAP user. The created username is mapped to the UUID of the LDAP user. Additionally the DN is cached as well to reduce LDAP interaction, but it is not used for identification. If the DN changes, the changes will be found. The internal username is used all over. Clearing the mappings will have leftovers everywhere. Clearing the mappings is not configuration sensitive, it affects all LDAP configurations! Never clear the mappings in a production environment, only in a testing or experimental stage.') }}

			<div class="ldap-wizard__clear-mapping__buttons">
				<NcButton type="error" :disabled="clearMappingLoading" @click="requestClearMapping('user')">
					{{ t('user_ldap', 'Clear Username-LDAP User Mapping') }}
				</NcButton>
				<NcButton type="error" :disabled="clearMappingLoading" @click="requestClearMapping('group')">
					{{ t('user_ldap', 'Clear Groupname-LDAP Group Mapping') }}
				</NcButton>
			</div>
		</div>
	</form>
</template>

<script lang="ts" setup>
import { ref } from 'vue'

import Plus from 'vue-material-design-icons/Plus.vue'

import { t } from '@nextcloud/l10n'
import { loadState } from '@nextcloud/initial-state'
import { NcCheckboxRadioSwitch, NcSelect, NcButton, NcNoteCard } from '@nextcloud/vue'

import ServerTab from '../components/SettingsTabs/ServerTab.vue'
import UsersTab from '../components/SettingsTabs/UsersTab.vue'
import LoginTab from '../components/SettingsTabs/LoginTab.vue'
import GroupsTab from '../components/SettingsTabs/GroupsTab.vue'
import ExpertTab from '../components/SettingsTabs/ExpertTab.vue'
import AdvancedTab from '../components/SettingsTabs/AdvancedTab.vue'
import WizardControls from '../components/WizardControls.vue'
import { useLDAPConfigsStore } from '../store/configs'
import { clearMapping, updateConfig } from '../services/ldapConfigService'
import { storeToRefs } from 'pinia'

const ldapModuleInstalled = loadState('user_ldap', 'ldapModuleInstalled')

const leftTabs = {
	server: t('user_ldap', 'Server'),
	users: t('user_ldap', 'Users'),
	login: t('user_ldap', 'Login Attributes'),
	groups: t('user_ldap', 'Groups'),
}

const rightTabs = {
	advanced: t('user_ldap', 'Advanced'),
	expert: t('user_ldap', 'Expert'),
}

const ldapConfigsStore = useLDAPConfigsStore()
const { ldapConfigs, selectedConfigId, selectedConfig } = storeToRefs(ldapConfigsStore)

const selectedTab = ref('server')
const clearMappingLoading = ref(false)

ldapConfigsStore.$subscribe(async () => {
	if (selectedConfigId === undefined) {
		throw new Error('selectedConfigId should not be undefined')
	}

	await updateConfig(selectedConfigId.value, selectedConfig.value)
})

/**
 *
 * @param subject
 */
async function requestClearMapping(subject: 'user'|'group') {
	try {
		clearMappingLoading.value = true
		await clearMapping(subject)
	} finally {
		clearMappingLoading.value = false
	}
}
</script>
<style lang="scss" scoped>
.ldap-wizard {
	padding: 16px;
	max-width: 1000px;

	&__config-selection {
		display: flex;
		align-items: end;
		margin-bottom: 8px;
		gap: 16px;

		&__create-button {
			margin-bottom: 4px;
		}
	}

	&__tab-selection-container {
		display: flex;
		justify-content: space-between;
	}

	&__tab-selection {
		display: flex;
		margin-left: -16px;
		margin-bottom: 16px;

		&:last-of-type {
			margin-right: -16px;
		}
	}

	&__tab-container {
		border: 1px solid var(--color-text-light);
		border-radius: var(--border-radius);
		padding: 0 16px 16px 16px;
	}

	&__controls {
		margin-top: 16px;
	}

	&__clear-mapping {
		padding: 16px;

		&__buttons {
			display: flex;
			margin-top: 8px;
			gap: 16px;
			justify-content: end;
		}
	}
}
</style>
