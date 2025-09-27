<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<fieldset class="ldap-wizard__users">
		{{ t('user_name', 'Listing and searching for users is constrained by these criteria:') }}

		<div class="ldap-wizard__users__line ldap-wizard__users__user-filter-object-class">
			<NcSelect :model-value="ldapUserFilterObjectclass"
				:disabled="ldapConfig.ldapUserFilterMode === '1'"
				class="ldap-wizard__users__user-filter-object-class__select"
				:options="userObjectClasses"
				:input-label="t('user_name', 'Only these object classes:')"
				:multiple="true"
				@update:model-value="updateUserFilterObjectclass" />
			{{ t('user_name', 'The most common object classes for users are organizationalPerson, person, user, and inetOrgPerson. If you are not sure which object class to select, please consult your directory admin.') }}
		</div>

		<div class="ldap-wizard__users__line ldap-wizard__users__user-filter-groups">
			<div>
				{{ t('user_name', 'Only from these groups:') }}
			</div>

			<!-- TODO -->
			<!-- <input type="text" class="ldapManyGroupsSupport ldapManyGroupsSearch hidden" > -->
			<!-- <NcTextField :disabled="ldapConfig.ldapUserFilterMode === '1'"
				:value.sync="ldapConfig.ldapUserFilterGroups"
				:placeholder="t('user_name', 'Search groups')"
				autocomplete="off" /> -->

			<NcSelect :model-value="ldapUserFilterGroups"
				class="ldap-wizard__users__user-filter-groups__select"
				:disabled="ldapConfig.ldapUserFilterMode === '1'"
				:options="userGroups"
				:input-label="t('user_name', 'Only these groups:')"
				:multiple="true"
				@update:model-value="updateUserFilterGroups" />
		</div>

		<div class="ldap-wizard__users__line ldap-wizard__users__user-filter">
			<NcCheckboxRadioSwitch :checked="ldapConfig.ldapUserFilterMode === '1'"
				@update:checked="toggleFilterMode">
				{{ t('user_name', 'Edit LDAP Query') }}
			</NcCheckboxRadioSwitch>

			<div v-if="ldapConfig.ldapUserFilterMode === '0'">
				<label>{{ t('user_name', 'LDAP Filter:') }}</label>
				<code>{{ ldapConfig.ldapUserFilter }}</code>
			</div>
			<div v-else>
				<NcTextArea :value.sync="ldapConfig.ldapUserFilter"
					:placeholder="t('user_name', 'Edit LDAP Query')"
					:helper-text="t('user_name', 'The filter specifies which LDAP users shall have access to the {instanceName} instance.', { instanceName })" />
			</div>
		</div>

		<div class="ldap-wizard__users__line ldap-wizard__users__user-count-check">
			<NcButton @click="countUsers">
				{{ t('user_name', 'Verify settings and count users') }}
			</NcButton>

			<span v-if="usersCount !== undefined">{{ t('user_ldap', "User count: {userCount}", { usersCount }) }}</span>
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
import { useWizardStore } from '../../store/wizard'
import { showEnableAutomaticFilterInfo } from '../../services/ldapConfigService'

const wizardStore = useWizardStore()
const ldapConfigsStore = useLDAPConfigsStore()
const { selectedConfig: ldapConfig, updatingConfig } = storeToRefs(ldapConfigsStore)

const usersCount = ref<number|undefined>(undefined)

const instanceName = (getCapabilities() as { theming: { name:string } }).theming.name

const userObjectClasses = ref([] as string[])
const userGroups = ref([] as string[])

const shouldRequestLdapUserFilter = ref(false)

const ldapUserFilterObjectclass = computed(() => ldapConfig.value.ldapUserFilterObjectclass.split(';').filter((item) => item !== ''))
function updateUserFilterObjectclass(value: string[]) {
	ldapConfig.value.ldapUserFilterObjectclass = value.join(';')
	shouldRequestLdapUserFilter.value = true
}

const ldapUserFilterGroups = computed(() => ldapConfig.value.ldapUserFilterGroups.split(';').filter((item) => item !== ''))
function updateUserFilterGroups(value: string[]) {
	ldapConfig.value.ldapUserFilterGroups = value.join(';')
	shouldRequestLdapUserFilter.value = true
}

watch(updatingConfig, async () => {
	if (shouldRequestLdapUserFilter.value === true && updatingConfig.value === 0 && ldapConfig.value.ldapUserFilterMode === '0') {
		getUserListFilter()
	}
})

async function init() {
	const response1 = await wizardStore.callWizardAction('determineUserObjectClasses')
	userObjectClasses.value = response1.options.ldap_userfilter_objectclass
	ldapConfig.value.ldapUserFilterObjectclass = response1.changes.ldap_userfilter_objectclass

	const response2 = await wizardStore.callWizardAction('determineGroupsForUsers')
	userGroups.value = response2.options.ldap_userfilter_groups
	ldapConfig.value.ldapUserFilterGroups = response2.changes.ldap_userfilter_groups

	await getUserListFilter()
}

init()

async function getUserListFilter() {
	const response = await wizardStore.callWizardAction('getUserListFilter')
	ldapConfig.value.ldapUserFilter = response.changes.ldap_userlist_filter
	shouldRequestLdapUserFilter.value = false
}

async function countUsers() {
	const { changes: { ldap_test_base: ldapTestBase } } = await wizardStore.callWizardAction('countUsers')
	usersCount.value = ldapTestBase
}

async function toggleFilterMode(value: boolean) {
	if (value) {
		ldapConfig.value.ldapUserFilterMode = '1'
	} else {
		ldapConfig.value.ldapUserFilterMode = await showEnableAutomaticFilterInfo()
	}
}
</script>
<style lang="scss" scoped>
.ldap-wizard__users {
	display: flex;
	flex-direction: column;
	gap: 16px;

	&__line {
		display: flex;
		align-items: start;
	}

	&__user-filter-object-class {
		display: flex;
		gap: 16px;

		&__select {
			min-width: 50%;
			flex-grow: 1;
		}
	}

	&__user-filter-groups {
		display: flex;
		gap: 16px;

		&__select {
			min-width: 50%;
			flex-grow: 1;
		}
	}

	&__user-filter {
		display: flex;
		flex-direction: column;

		code {
			background-color: var(--color-background-dark);
			padding: 4px;
			border-radius: 4px;
		}
	}

	&__user-count-check {
		display: flex;
		align-items: center;
		gap: 16px;
	}
}
</style>
