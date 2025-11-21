<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<fieldset class="ldap-wizard__users">
		{{ t('user_ldap', 'Listing and searching for users is constrained by these criteria:') }}

		<div class="ldap-wizard__users__line ldap-wizard__users__user-filter-object-class">
			<NcSelect
				v-model="ldapUserFilterObjectclass"
				:disabled="ldapConfigProxy.ldapUserFilterMode === '1'"
				class="ldap-wizard__users__user-filter-object-class__select"
				:options="userObjectClasses"
				:input-label="t('user_ldap', 'Only these object classes:')"
				:multiple="true" />
			{{ t('user_ldap', 'The most common object classes for users are organizationalPerson, person, user, and inetOrgPerson. If you are not sure which object class to select, please consult your directory admin.') }}
		</div>

		<div class="ldap-wizard__users__line ldap-wizard__users__user-filter-groups">
			<NcSelect
				v-model="ldapUserFilterGroups"
				class="ldap-wizard__users__user-filter-groups__select"
				:disabled="ldapConfigProxy.ldapUserFilterMode === '1'"
				:options="userGroups"
				:input-label="t('user_ldap', 'Only from these groups:')"
				:multiple="true" />
		</div>

		<div class="ldap-wizard__users__line ldap-wizard__users__user-filter">
			<NcCheckboxRadioSwitch
				:model-value="ldapConfigProxy.ldapUserFilterMode === '1'"
				@update:model-value="toggleFilterMode">
				{{ t('user_ldap', 'Edit LDAP Query') }}
			</NcCheckboxRadioSwitch>

			<div v-if="ldapConfigProxy.ldapUserFilterMode === '1'">
				<NcTextArea
					v-model="ldapConfigProxy.ldapUserFilter"
					:placeholder="t('user_ldap', 'Edit LDAP Query')"
					:helper-text="t('user_ldap', 'The filter specifies which LDAP users shall have access to the {instanceName} instance.', { instanceName })" />
			</div>
			<div v-else>
				<label>{{ t('user_ldap', 'LDAP Filter:') }}</label>
				<code>{{ ldapConfigProxy.ldapUserFilter }}</code>
			</div>
		</div>

		<div class="ldap-wizard__users__line ldap-wizard__users__user-count-check">
			<NcButton :disabled="loadingUserCount" @click="countUsers">
				{{ t('user_ldap', 'Verify settings and count users') }}
			</NcButton>

			<NcLoadingIcon v-if="loadingUserCount" :size="16" />
			<span v-if="usersCount !== undefined && !loadingUserCount">{{ t('user_ldap', 'User count: {usersCount}', { usersCount }, { escape: false }) }}</span>
		</div>
	</fieldset>
</template>

<script lang="ts" setup>
import { getCapabilities } from '@nextcloud/capabilities'
import { t } from '@nextcloud/l10n'
import { NcButton, NcCheckboxRadioSwitch, NcLoadingIcon, NcSelect, NcTextArea } from '@nextcloud/vue'
import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import { callWizard, showEnableAutomaticFilterInfo } from '../../services/ldapConfigService.ts'
import { useLDAPConfigsStore } from '../../store/configs.ts'

const props = defineProps<{ configId: string }>()

const ldapConfigsStore = useLDAPConfigsStore()
const { ldapConfigs } = storeToRefs(ldapConfigsStore)
const ldapConfigProxy = computed(() => ldapConfigsStore.getConfigProxy(props.configId, {
	ldapUserFilterObjectclass: reloadFilters,
	ldapUserFilterGroups: reloadFilters,
}))

const usersCount = ref<number | undefined>(undefined)
const loadingUserCount = ref(false)

const instanceName = (getCapabilities() as { theming: { name: string } }).theming.name

const userObjectClasses = ref([] as string[])
const userGroups = ref([] as string[])

const ldapUserFilterObjectclass = computed({
	get() { return ldapConfigProxy.value.ldapUserFilterObjectclass?.split(';').filter((item) => item !== '') ?? [] },
	set(value) { ldapConfigProxy.value.ldapUserFilterObjectclass = value.join(';') },
})
const ldapUserFilterGroups = computed({
	get() { return ldapConfigProxy.value.ldapUserFilterGroups.split(';').filter((item) => item !== '') },
	set(value) { ldapConfigProxy.value.ldapUserFilterGroups = value.join(';') },
})

/**
 *
 */
async function init() {
	const response1 = await callWizard('determineUserObjectClasses', props.configId)
	userObjectClasses.value = response1.options?.ldap_userfilter_objectclass ?? []
	// Not using ldapConfig to avoid triggering the save logic.
	ldapConfigs.value[props.configId]!.ldapUserFilterObjectclass = (response1.changes?.ldap_userfilter_objectclass as string[] | undefined)?.join(';') ?? ''

	const response2 = await callWizard('determineGroupsForUsers', props.configId)
	userGroups.value = response2.options?.ldap_userfilter_groups ?? []
	// Not using ldapConfig to avoid triggering the save logic.
	ldapConfigs.value[props.configId]!.ldapUserFilterGroups = (response2.changes?.ldap_userfilter_groups as string[] | undefined)?.join(';') ?? ''
}

init()

/**
 *
 */
async function reloadFilters() {
	if (ldapConfigProxy.value.ldapUserFilterMode === '0') {
		const response1 = await callWizard('getUserListFilter', props.configId)
		// Not using ldapConfig to avoid triggering the save logic.
		ldapConfigs.value[props.configId]!.ldapUserFilter = (response1.changes?.ldap_userlist_filter as string | undefined) ?? ''

		const response2 = await callWizard('getUserLoginFilter', props.configId)
		// Not using ldapConfig to avoid triggering the save logic.
		ldapConfigs.value[props.configId]!.ldapLoginFilter = (response2.changes?.ldap_login_filter as string | undefined) ?? ''
	}
}

/**
 *
 */
async function countUsers() {
	try {
		loadingUserCount.value = true
		const response = await callWizard('countUsers', props.configId)
		usersCount.value = response.changes!.ldap_user_count as number
	} finally {
		loadingUserCount.value = false
	}
}

/**
 *
 * @param value
 */
async function toggleFilterMode(value: boolean) {
	if (value) {
		ldapConfigProxy.value.ldapUserFilterMode = '1'
	} else {
		ldapConfigProxy.value.ldapUserFilterMode = await showEnableAutomaticFilterInfo() ? '0' : '1'
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
