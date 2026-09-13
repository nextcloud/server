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
				:inputLabel="t('user_ldap', 'Only these object classes:')"
				:multiple="true" />
			{{ t('user_ldap', 'The most common object classes for users are organizationalPerson, person, user, and inetOrgPerson. If you are not sure which object class to select, please consult your directory admin.') }}
		</div>

		<div class="ldap-wizard__users__line ldap-wizard__users__user-filter-groups">
			<NcSelect
				v-model="ldapUserFilterGroups"
				class="ldap-wizard__users__user-filter-groups__select"
				:disabled="ldapConfigProxy.ldapUserFilterMode === '1'"
				:options="userGroups"
				:inputLabel="t('user_ldap', 'Only from these groups:')"
				:multiple="true" />
		</div>

		<div class="ldap-wizard__users__line ldap-wizard__users__user-filter">
			<NcCheckboxRadioSwitch
				:modelValue="ldapConfigProxy.ldapUserFilterMode === '1'"
				@update:modelValue="toggleFilterMode">
				{{ t('user_ldap', 'Edit LDAP Query') }}
			</NcCheckboxRadioSwitch>

			<div v-if="ldapConfigProxy.ldapUserFilterMode === '1'">
				<NcTextArea
					v-model="ldapConfigProxy.ldapUserFilter"
					:placeholder="t('user_ldap', 'Edit LDAP Query')"
					:helperText="t('user_ldap', 'The filter specifies which LDAP users shall have access to the {instanceName} instance.', { instanceName })" />
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
import { computed, onBeforeMount, ref } from 'vue'
import { callWizard, showEnableAutomaticFilterInfo } from '../../services/ldapConfigService.ts'
import { useLDAPConfigsStore } from '../../store/configs.ts'

const props = defineProps<{ configId: string }>()

const ldapConfigsStore = useLDAPConfigsStore()
const { ldapConfigs } = storeToRefs(ldapConfigsStore)
const ldapConfigProxy = computed(() => ldapConfigsStore.getConfigProxy(props.configId, {
	ldapUserFilterObjectclass: () => reloadFilters(),
	ldapUserFilterGroups: () => reloadFilters(),
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

onBeforeMount(init)

/**
 * Initialize user filter options
 */
async function init() {
	try {
		const response1 = await callWizard('determineUserObjectClasses', props.configId)
		userObjectClasses.value = response1.options?.ldap_userfilter_objectclass ?? []
		const objectClasses = normalizeSelection(response1.changes?.ldap_userfilter_objectclass)
		if (objectClasses !== undefined) {
			// Wizard discoveries are already persisted on the server.
			ldapConfigs.value[props.configId]!.ldapUserFilterObjectclass = objectClasses
		}

		await reloadFilters(true)

		const response2 = await callWizard('determineGroupsForUsers', props.configId)
		userGroups.value = response2.options?.ldap_userfilter_groups ?? []
		const groups = normalizeSelection(response2.changes?.ldap_userfilter_groups)
		if (groups !== undefined) {
			ldapConfigs.value[props.configId]!.ldapUserFilterGroups = groups
		}
	} catch {
		// callWizard displays the error; keep discoveries from completed steps.
	}
}

/**
 * Normalize wizard selections without replacing missing changes.
 *
 * @param value - Selection returned by the wizard
 */
function normalizeSelection(value: unknown): string | undefined {
	if (typeof value === 'string') {
		return value
	}
	if (Array.isArray(value) && value.every((item) => typeof item === 'string')) {
		return value.join(';')
	}
}

/**
 * Reload filters
 *
 * @param initialOnly - Preserve existing filters during initialization
 */
async function reloadFilters(initialOnly = false) {
	if (ldapConfigProxy.value.ldapUserFilterMode === '0') {
		if (!initialOnly || ldapConfigProxy.value.ldapUserFilter === '') {
			const response1 = await callWizard('getUserListFilter', props.configId)
			// Not using ldapConfig to avoid triggering the save logic.
			ldapConfigs.value[props.configId]!.ldapUserFilter = (response1.changes?.ldap_userlist_filter as string | undefined) ?? ''
		}

		if (ldapConfigProxy.value.ldapLoginFilterMode === '0' && (!initialOnly || ldapConfigProxy.value.ldapLoginFilter === '')) {
			const response2 = await callWizard('getUserLoginFilter', props.configId)
			// Not using ldapConfig to avoid triggering the save logic.
			ldapConfigs.value[props.configId]!.ldapLoginFilter = (response2.changes?.ldap_login_filter as string | undefined) ?? ''
		}
	}
}

/**
 * Count users
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
 * Toggle filter mode
 *
 * @param value - new value
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
