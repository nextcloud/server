<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<fieldset class="ldap-wizard__login">
		<legend>
			{{ t('user_ldap', 'When logging in, {instanceName} will find the user based on the following attributes:', { instanceName }) }}
		</legend>

		<NcCheckboxRadioSwitch
			:model-value="ldapConfigProxy.ldapLoginFilterUsername === '1'"
			:description="t('user_ldap', 'Allows login against the LDAP/AD username, which is either \'uid\' or \'sAMAccountName\' and will be detected.')"
			@update:model-value="ldapConfigProxy.ldapLoginFilterUsername = $event ? '1' : '0'">
			{{ t('user_ldap', 'LDAP/AD Username:') }}
		</NcCheckboxRadioSwitch>

		<NcCheckboxRadioSwitch
			:model-value="ldapConfigProxy.ldapLoginFilterEmail === '1'"
			:description="t('user_ldap', 'Allows login against an email attribute. \'mail\' and \'mailPrimaryAddress\' allowed.')"
			@update:model-value="ldapConfigProxy.ldapLoginFilterEmail = $event ? '1' : '0'">
			{{ t('user_ldap', 'LDAP/AD Email Address:') }}
		</NcCheckboxRadioSwitch>

		<div class="ldap-wizard__login__line ldap-wizard__login__login-attributes">
			<NcSelect
				v-model="ldapLoginFilterAttributes"
				keep-open
				:disabled="ldapLoginFilterMode"
				:options="filteredLoginFilterOptions"
				:input-label="t('user_ldap', 'Other Attributes:')"
				:multiple="true" />
		</div>

		<div class="ldap-wizard__login__line ldap-wizard__login__user-login-filter">
			<NcCheckboxRadioSwitch
				:model-value="ldapLoginFilterMode"
				@update:model-value="toggleFilterMode">
				{{ t('user_ldap', 'Edit LDAP Query') }}
			</NcCheckboxRadioSwitch>

			<NcTextArea
				v-if="ldapLoginFilterMode"
				:model-value="ldapConfigProxy.ldapLoginFilter"
				:placeholder="t('user_ldap', 'Edit LDAP Query')"
				:helper-text="t('user_ldap', 'Defines the filter to apply, when login is attempted. `%%uid` replaces the username in the login action. Example: `uid=%%uid`')"
				@change="(event) => ldapConfigProxy.ldapLoginFilter = event.target.value" />
			<div v-else>
				<span>{{ t('user_ldap', 'LDAP Filter:') }}</span>
				<code>{{ ldapConfigProxy.ldapLoginFilter }}</code>
			</div>
		</div>

		<div class="ldap-wizard__login__line">
			<NcTextField
				v-model="testUsername"
				:helper-text="t('user_ldap', 'Attempts to receive a DN for the given login name and the current login filter')"
				:label="t('user_ldap', 'Test Login name')"
				autocomplete="off" />

			<NcButton
				:disabled="testUsername.length === 0"
				@click="verifyLoginName">
				{{ t('user_ldap', 'Verify settings') }}
			</NcButton>
		</div>
	</fieldset>
</template>

<script lang="ts" setup>
import { getCapabilities } from '@nextcloud/capabilities'
import { showError, showSuccess, showWarning } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { NcButton, NcCheckboxRadioSwitch, NcSelect, NcTextArea, NcTextField } from '@nextcloud/vue'
import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import { callWizard, showEnableAutomaticFilterInfo } from '../../services/ldapConfigService.ts'
import { useLDAPConfigsStore } from '../../store/configs.ts'

const props = defineProps<{ configId: string }>()

const ldapConfigsStore = useLDAPConfigsStore()
const { ldapConfigs } = storeToRefs(ldapConfigsStore)
const ldapConfigProxy = computed(() => ldapConfigsStore.getConfigProxy(props.configId, {
	ldapLoginFilterAttributes: getUserLoginFilter,
	ldapLoginFilterUsername: getUserLoginFilter,
	ldapLoginFilterEmail: getUserLoginFilter,
}))

const instanceName = (getCapabilities() as { theming: { name: string } }).theming.name
const testUsername = ref('')
const loginFilterOptions = ref<string[]>([])

const ldapLoginFilterAttributes = computed({
	get() { return ldapConfigProxy.value.ldapLoginFilterAttributes.split(';').filter((item) => item !== '') },
	set(value) { ldapConfigProxy.value.ldapLoginFilterAttributes = value.join(';') },
})

const ldapLoginFilterMode = computed(() => ldapConfigProxy.value.ldapLoginFilterMode === '1')
const filteredLoginFilterOptions = computed(() => loginFilterOptions.value.filter((option) => !ldapLoginFilterAttributes.value.includes(option)))

/**
 *
 */
async function init() {
	const response = await callWizard('determineAttributes', props.configId)
	loginFilterOptions.value = response.options?.ldap_loginfilter_attributes ?? []
}

init()

/**
 *
 */
async function getUserLoginFilter() {
	if (ldapConfigProxy.value.ldapLoginFilterMode === '0') {
		const response = await callWizard('getUserLoginFilter', props.configId)
		// Not using ldapConfig to avoid triggering the save logic.
		ldapConfigs.value[props.configId]!.ldapLoginFilter = (response.changes?.ldap_login_filter as string | undefined) ?? ''
	}
}

/**
 *
 */
async function verifyLoginName() {
	try {
		const response = await callWizard('testLoginName', props.configId, { loginName: testUsername.value })

		const testLoginName = response.changes!.ldap_test_loginname as number
		const testEffectiveFilter = response.changes!.ldap_test_effective_filter as string

		if (testLoginName < 1) {
			showError(t('user_ldap', 'User not found. Please check your login attributes and username. Effective filter (to copy-and-paste for command-line validation): {filter}', { filter: testEffectiveFilter }))
		} else if (testLoginName === 1) {
			showSuccess(t('user_ldap', 'User found and settings verified.'))
		} else if (testLoginName > 1) {
			showWarning(t('user_ldap', 'Consider narrowing your search, as it encompassed many users, only the first one of whom will be able to log in.'))
		}
	} catch (error) {
		const message = error ?? t('user_ldap', 'An unspecified error occurred. Please check log and settings.')

		switch (message) {
			case 'Bad search filter':
				showError(t('user_ldap', 'The search filter is invalid, probably due to syntax issues like uneven number of opened and closed brackets. Please revise.'))
				break
			case 'connection error':
				showError(t('user_ldap', 'A connection error to LDAP/AD occurred. Please check host, port and credentials.'))
				break
			case 'missing placeholder':
				showError(t('user_ldap', 'The "%uid" placeholder is missing. It will be replaced with the login name when querying LDAP/AD.'))
				break
		}
	}
}

/**
 *
 * @param value
 */
async function toggleFilterMode(value: boolean) {
	if (value) {
		ldapConfigProxy.value.ldapLoginFilterMode = '1'
	} else {
		ldapConfigProxy.value.ldapLoginFilterMode = await showEnableAutomaticFilterInfo() ? '0' : '1'
	}
}
</script>

<style lang="scss" scoped>
.ldap-wizard__login {
	display: flex;
	flex-direction: column;
	gap: 16px;

	button {
		flex-shrink: 0;
	}

	&__line {
		display: flex;
		align-items: start;
		gap: 8px;
	}

	&__login-attributes {
		display: flex;
		flex-direction: column;
	}

	&__user-login-filter {
		display: flex;
		flex-direction: column;

		code {
			background-color: var(--color-background-dark);
			padding: 4px;
			border-radius: 4px;
		}
	}
}
</style>
