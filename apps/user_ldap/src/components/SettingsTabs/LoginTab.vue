<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<fieldset class="ldap-wizard__login">
		{{ t('user_ldap', 'When logging in, {instanceName} will find the user based on the following attributes:', { instanceName }) }}

		<div class="ldap-wizard__login__line ldap-wizard__login__login-attributes">
			<NcCheckboxRadioSwitch :disabled="ldapConfig.ldapLoginFilterMode === '1'"
				:checked="ldapConfig.ldapLoginFilterUsername === '1'"
				:aria-label="t('user_ldap', 'Allows login against the LDAP/AD username, which is either `uid` or `sAMAccountName` and will be detected.')"
				@update:checked="ldapConfig.ldapLoginFilterUsername = $event ? '1' : '0'">
				{{ t('user_ldap', 'LDAP/AD Username') }}
			</NcCheckboxRadioSwitch>

			<NcCheckboxRadioSwitch :disabled="ldapConfig.ldapLoginFilterMode === '1'"
				:checked="ldapConfig.ldapLoginFilterEmail === '1'"
				:aria-label="t('user_ldap', 'Allows login against an email attribute. `mail` and `mailPrimaryAddress` allowed.')"
				@update:checked="ldapConfig.ldapLoginFilterEmail = $event ? '1' : '0'">
				{{ t('user_ldap', 'LDAP/AD Email Address') }}
			</NcCheckboxRadioSwitch>

			<NcSelect :value="selectedLoginFilterAttributes"
				:close-on-select="false"
				:disabled="ldapConfig.ldapLoginFilterMode === '1'"
				:options="filteredLoginFilterOptions"
				:input-label="t('user_ldap', 'Other Attributes:')"
				:multiple="true"
				@input="updateLoginFilterAttributes" />
		</div>

		<div class="ldap-wizard__login__line ldap-wizard__login__user-login-filter">
			<NcCheckboxRadioSwitch :checked="ldapConfig.ldapLoginFilterMode === '1'"
				@update:checked="toggleFilterMode">
				{{ t('user_name', 'Edit LDAP Query') }}
			</NcCheckboxRadioSwitch>

			<div v-if="ldapConfig.ldapLoginFilterMode === '0'">
				<label>{{ t('user_name', 'LDAP Filter:') }}</label>
				<code>{{ ldapConfig.ldapLoginFilter }}</code>
			</div>
			<div v-else>
				<NcTextArea :value.sync="ldapConfig.ldapLoginFilter"
					:placeholder="t('user_name', 'Edit LDAP Query')"
					:helper-text="t('user_name', 'Defines the filter to apply, when login is attempted. `%%uid` replaces the username in the login action. Example: `uid=%%uid`')" />
			</div>
		</div>

		<div class="ldap-wizard__login__line">
			<NcTextField :value.sync="testUsername"
				:helper-text="t('user_ldap', 'Attempts to receive a DN for the given login name and the current login filter')"
				:placeholder="t('user_ldap', 'Test Login name')"
				autocomplete="off" />

			<NcButton :disabled="enableVerifyButton"
				@click="verifyLoginName">
				{{ t('user_ldap', 'Verify settings') }}
			</NcButton>
		</div>
	</fieldset>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'

import { t } from '@nextcloud/l10n'
import { NcButton, NcTextField, NcTextArea, NcCheckboxRadioSwitch, NcSelect } from '@nextcloud/vue'
import { getCapabilities } from '@nextcloud/capabilities'
import { showError, showSuccess } from '@nextcloud/dialogs'

import { useLDAPConfigsStore } from '../../store/configs'
import { useWizardStore } from '../../store/wizard'
import { showEnableAutomaticFilterInfo } from '../../services/ldapConfigService'

const ldapConfigsStore = useLDAPConfigsStore()
const wizardStore = useWizardStore()

const { selectedConfig: ldapConfig } = storeToRefs(ldapConfigsStore)

const instanceName = (getCapabilities() as { theming: { name:string } }).theming.name
const testUsername = ref('')
const enableVerifyButton = ref(false)

const loginFilterOptions = ref<string[]>([])
const selectedLoginFilterAttributes = computed(() => ldapConfig.value.ldapLoginFilterAttributes.split(';'))
const filteredLoginFilterOptions = computed(() => loginFilterOptions.value.filter((option) => !selectedLoginFilterAttributes.value.includes(option)))
wizardStore.callWizardAction('determineAttributes')
	.then(({ options }) => { loginFilterOptions.value = options.ldap_loginfilter_attributes })

/**
 *
 * @param value
 */
function updateLoginFilterAttributes(value) {
	ldapConfig.value.ldapLoginFilterAttributes = value.join(';')
}

/**
 *
 */
async function verifyLoginName() {
	try {
		const { changes: { ldap_test_loginname: testLoginName, ldap_test_effective_filter: testEffectiveFilter } } = await wizardStore.callWizardAction('testLoginName', { ldap_test_loginname: testUsername.value })

		if (testLoginName < 1) {
			showSuccess(t('user_ldap', 'User not found. Please check your login attributes and username. Effective filter (to copy-and-paste for command-line validation): {filter}', { filter: testEffectiveFilter }))
		} else if (testLoginName === 1) {
			showSuccess(t('user_ldap', 'User found and settings verified.'))
		} else if (testLoginName > 1) {
			showSuccess(t('user_ldap', 'Consider narrowing your search, as it encompassed many users, only the first one of whom will be able to log in.'))
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
		ldapConfig.value.ldapLoginFilterMode = '1'
	} else {
		ldapConfig.value.ldapLoginFilterMode = await showEnableAutomaticFilterInfo()
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
