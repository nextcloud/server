<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<fieldset class="ldap-wizard__groups">
		{{ t('user_ldap', 'Groups meeting these criteria are available in {instanceName}:', {instanceName}) }}

		<div class="ldap-wizard__groups__line ldap-wizard__groups__filter-selection">
			<NcSelect :model-value="ldapGroupFilterObjectclass"
				class="ldap-wizard__groups__group-filter-groups__select"
				:options="groupObjectClasses"
				:disabled="ldapConfig.ldapGroupFilterMode === '1'"
				:input-label="t('user_ldap', 'Only these object classes:')"
				:multiple="true"
				@update:model-value="updateGroupFilterObjectclass" />

			<!-- TODO -->
			<!-- <input type="text" class="ldapManyGroupsSupport ldapManyGroupsSearch hidden" placeholder="t('user_ldap', 'Search groups')"> -->
			<NcSelect :model-value="ldapGroupFilterGroups"
				class="ldap-wizard__groups__group-filter-groups__select"
				:options="groupGroups"
				:disabled="ldapConfig.ldapGroupFilterMode === '1'"
				:input-label="t('user_ldap', 'Only from these groups:')"
				:multiple="true"
				@update:model-value="updateGroupFilterGroups" />
		</div>

		<div class="ldap-wizard__groups__line ldap-wizard__groups__groups-filter">
			<NcCheckboxRadioSwitch :checked="ldapConfig.ldapGroupFilterMode === '1'"
				@update:checked="toggleFilterMode">
				{{ t('user_name', 'Edit LDAP Query') }}
			</NcCheckboxRadioSwitch>

			<div v-if="ldapConfig.ldapGroupFilterMode === '0'">
				<label>{{ t('user_name', 'LDAP Filter:') }}</label>
				<code>{{ ldapConfig.ldapGroupFilter }}</code>
			</div>
			<div v-else>
				<NcTextArea :value.sync="ldapConfig.ldapGroupFilter"
					:placeholder="t('user_name', 'Edit LDAP Query')"
					:helper-text="t('user_name', 'The filter specifies which LDAP groups shall have access to the {instanceName} instance.', {instanceName})" />
			</div>
		</div>

		<div class="ldap-wizard__groups__line ldap-wizard__groups__groups-count-check">
			<NcButton @click="countGroups">
				{{ t('user_ldap', 'Verify settings and count the groups') }}
			</NcButton>

			<span v-if="groupsCount !== undefined">{{ t('user_ldap', "Groups count: {groupsCount}", { groupsCount }) }}</span>
		</div>
	</fieldset>
</template>

<script lang="ts" setup>
import { ref, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'

import { t } from '@nextcloud/l10n'
import { NcButton, NcTextArea, NcCheckboxRadioSwitch, NcSelect } from '@nextcloud/vue'
import { getCapabilities } from '@nextcloud/capabilities'

import { useLDAPConfigsStore } from '../../store/configs'
import { showEnableAutomaticFilterInfo } from '../../services/ldapConfigService'
import { useWizardStore } from '../../store/wizard'

const ldapConfigsStore = useLDAPConfigsStore()
const wizardStore = useWizardStore()
const { selectedConfig: ldapConfig, updatingConfig } = storeToRefs(ldapConfigsStore)

const instanceName = (getCapabilities() as { theming: { name:string } }).theming.name

const groupsCount = ref<number|undefined>(undefined)

const groupObjectClasses = ref([] as string[])
const groupGroups = ref([] as string[])

const shouldRequestLdapGroupFilter = ref(false)

const ldapGroupFilterObjectclass = computed(() => ldapConfig.value.ldapGroupFilterObjectclass.split(';').filter((item) => item !== ''))
function updateGroupFilterObjectclass(value: string[]) {
	ldapConfig.value.ldapGroupFilterObjectclass = value.join(';')
	shouldRequestLdapGroupFilter.value = true
}

const ldapGroupFilterGroups = computed(() => ldapConfig.value.ldapGroupFilterGroups.split(';').filter((item) => item !== ''))
function updateGroupFilterGroups(value: string[]) {
	ldapConfig.value.ldapGroupFilterGroups = value.join(';')
	shouldRequestLdapGroupFilter.value = true
}

watch(updatingConfig, async () => {
	if (shouldRequestLdapGroupFilter.value === true && updatingConfig.value === 0 && ldapConfig.value.ldapGroupFilterMode === '0') {
		getGroupFilter()
	}
}, { immediate: true })

wizardStore.callWizardAction('determineGroupObjectClasses')
.then((response) => {
	groupObjectClasses.value = response.options.ldap_groupfilter_objectclass
})

wizardStore.callWizardAction('determineGroupsForGroups')
.then((response) => {
	groupGroups.value = response.options.ldap_groupfilter_groups
})


async function getGroupFilter() {
	const response = await wizardStore.callWizardAction('getGroupFilter')
	ldapConfig.value.ldapGroupFilter = response.changes.ldap_group_filter
	shouldRequestLdapGroupFilter.value = false
}

async function countGroups() {
	const { changes: { ldap_test_base: ldapTestBase } } = await wizardStore.callWizardAction('countGroups')
	groupsCount.value = ldapTestBase
}

async function toggleFilterMode(value: boolean) {
	if (value) {
		ldapConfig.value.ldapGroupFilterMode = '1'
	} else {
		ldapConfig.value.ldapGroupFilterMode = await showEnableAutomaticFilterInfo()
	}
}

getGroupFilter()
</script>
<style lang="scss" scoped>
.ldap-wizard__groups {
	display: flex;
	flex-direction: column;
	gap: 16px;

	&__line {
		display: flex;
		align-items: start;
	}

	&__filter-selection {
		flex-direction: column;
	}

	&__groups-filter {
		display: flex;
		flex-direction: column;

		code {
			background-color: var(--color-background-dark);
			padding: 4px;
			border-radius: 4px;
		}
	}

	&__groups-count-check {
		display: flex;
		align-items: center;
		gap: 16px;
	}
}
</style>
