<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<fieldset class="ldap-wizard__groups">
		<legend>
			{{ t('user_ldap', 'Groups meeting these criteria are available in {instanceName}:', { instanceName }) }}
		</legend>

		<div class="ldap-wizard__groups__line ldap-wizard__groups__filter-selection">
			<NcSelect
				v-model="ldapGroupFilterObjectclass"
				class="ldap-wizard__groups__group-filter-groups__select"
				:options="groupObjectClasses"
				:disabled="ldapConfigProxy.ldapGroupFilterMode === '1'"
				:input-label="t('user_ldap', 'Only these object classes:')"
				:multiple="true" />

			<NcSelect
				v-model="ldapGroupFilterGroups"
				class="ldap-wizard__groups__group-filter-groups__select"
				:options="groupGroups"
				:disabled="ldapConfigProxy.ldapGroupFilterMode === '1'"
				:input-label="t('user_ldap', 'Only from these groups:')"
				:multiple="true" />
		</div>

		<div class="ldap-wizard__groups__line ldap-wizard__groups__groups-filter">
			<NcCheckboxRadioSwitch
				:model-value="ldapConfigProxy.ldapGroupFilterMode === '1'"
				@update:model-value="toggleFilterMode">
				{{ t('user_ldap', 'Edit LDAP Query') }}
			</NcCheckboxRadioSwitch>

			<div v-if="ldapConfigProxy.ldapGroupFilterMode === '1'">
				<NcTextArea
					v-model="ldapConfigProxy.ldapGroupFilter"
					:placeholder="t('user_ldap', 'Edit LDAP Query')"
					:helper-text="t('user_ldap', 'The filter specifies which LDAP groups shall have access to the {instanceName} instance.', { instanceName })" />
			</div>
			<div v-else>
				<span>{{ t('user_ldap', 'LDAP Filter:') }}</span>
				<code>{{ ldapConfigProxy.ldapGroupFilter }}</code>
			</div>
		</div>

		<div class="ldap-wizard__groups__line ldap-wizard__groups__groups-count-check">
			<NcButton :disabled="loadingGroupCount" @click="countGroups">
				{{ t('user_ldap', 'Verify settings and count the groups') }}
			</NcButton>

			<NcLoadingIcon v-if="loadingGroupCount" :size="20" />
			<span v-if="groupsCountLabel !== undefined && !loadingGroupCount">{{ groupsCountLabel }}</span>
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
	ldapGroupFilterObjectclass: getGroupFilter,
	ldapGroupFilterGroups: getGroupFilter,
}))

const instanceName = (getCapabilities() as { theming: { name: string } }).theming.name

const groupsCountLabel = ref<number | undefined>(undefined)

const groupObjectClasses = ref([] as string[])
const groupGroups = ref([] as string[])
const loadingGroupCount = ref(false)

const ldapGroupFilterObjectclass = computed({
	get() { return ldapConfigProxy.value.ldapGroupFilterObjectclass.split(';').filter((item) => item !== '') },
	set(value) { ldapConfigProxy.value.ldapGroupFilterObjectclass = value.join(';') },
})
const ldapGroupFilterGroups = computed({
	get() { return ldapConfigProxy.value.ldapGroupFilterGroups.split(';').filter((item) => item !== '') },
	set(value) { ldapConfigProxy.value.ldapGroupFilterGroups = value.join(';') },
})

/**
 *
 */
async function init() {
	const response1 = await callWizard('determineGroupObjectClasses', props.configId)
	groupObjectClasses.value = response1.options?.ldap_groupfilter_objectclass ?? []

	const response2 = await callWizard('determineGroupsForGroups', props.configId)
	groupGroups.value = response2.options?.ldap_groupfilter_groups ?? []
}

init()

/**
 *
 */
async function getGroupFilter() {
	const response = await callWizard('getGroupFilter', props.configId)
	// Not using ldapConfig to avoid triggering the save logic.
	ldapConfigs.value[props.configId]!.ldapGroupFilter = (response.changes?.ldap_group_filter as string | undefined) ?? ''
}

/**
 *
 */
async function countGroups() {
	try {
		loadingGroupCount.value = true
		const response = await callWizard('countGroups', props.configId)
		groupsCountLabel.value = response.changes!.ldap_group_count as number
	} finally {
		loadingGroupCount.value = false
	}
}

/**
 *
 * @param value
 */
async function toggleFilterMode(value: boolean) {
	if (value) {
		ldapConfigProxy.value.ldapGroupFilterMode = '1'
	} else {
		ldapConfigProxy.value.ldapGroupFilterMode = await showEnableAutomaticFilterInfo() ? '0' : '1'
	}
}
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
