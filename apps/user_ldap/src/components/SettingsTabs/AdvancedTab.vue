<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<fieldset class="ldap-wizard__advanced">
		<details open name="ldap-wizard__advanced__section" class="ldap-wizard__advanced__section">
			<summary><h3>{{ t('user_ldap', 'Connection Settings') }}</h3></summary>

			<NcTextField
				autocomplete="off"
				:label="t('user_ldap', 'Backup (Replica) Host')"
				:model-value="ldapConfigProxy.ldapBackupHost"
				:helper-text="t('user_ldap', 'Give an optional backup host. It must be a replica of the main LDAP/AD server.')"
				@change="(event) => ldapConfigProxy.ldapBackupHost = event.target.value" />

			<NcTextField
				type="number"
				:model-value="ldapConfigProxy.ldapBackupPort"
				:label="t('user_ldap', 'Backup (Replica) Port')"
				@change="(event) => ldapConfigProxy.ldapBackupPort = event.target.value" />

			<NcCheckboxRadioSwitch
				:model-value="ldapConfigProxy.ldapOverrideMainServer === '1'"
				type="switch"
				:aria-label="t('user_ldap', 'Only connect to the replica server.')"
				@update:model-value="ldapConfigProxy.ldapOverrideMainServer = $event ? '1' : '0'">
				{{ t('user_ldap', 'Disable Main Server') }}
			</NcCheckboxRadioSwitch>

			<NcCheckboxRadioSwitch
				:model-value="ldapConfigProxy.turnOffCertCheck === '1'"
				:aria-label="t('user_ldap', 'Not recommended, use it for testing only! If connection only works with this option, import the LDAP server\'s SSL certificate in your {instanceName} server.', { instanceName })"
				@update:model-value="ldapConfigProxy.turnOffCertCheck = $event ? '1' : '0'">
				{{ t('user_ldap', 'Turn off SSL certificate validation.') }}
			</NcCheckboxRadioSwitch>

			<NcTextField
				type="number"
				:label="t('user_ldap', 'Cache Time-To-Live')"
				:model-value="ldapConfigProxy.ldapCacheTTL"
				:helper-text="t('user_ldap', 'in seconds. A change empties the cache.')"
				@change="(event) => ldapConfigProxy.ldapCacheTTL = event.target.value" />
		</details>

		<details name="ldap-wizard__advanced__section" class="ldap-wizard__advanced__section">
			<summary><h3>{{ t('user_ldap', 'Directory Settings') }}</h3></summary>

			<NcTextField
				autocomplete="off"
				:model-value="ldapConfigProxy.ldapUserDisplayName"
				:label="t('user_ldap', 'User Display Name Field')"
				:helper-text="t('user_ldap', 'The LDAP attribute to use to generate the user\'s display name.')"
				@change="(event) => ldapConfigProxy.ldapUserDisplayName = event.target.value" />

			<NcTextField
				autocomplete="off"
				:model-value="ldapConfigProxy.ldapUserDisplayName2"
				:label="t('user_ldap', '2nd User Display Name Field')"
				:helper-text="t('user_ldap', 'Optional. An LDAP attribute to be added to the display name in brackets. Results in e.g. »John Doe (john.doe@example.org)«.')"
				@change="(event) => ldapConfigProxy.ldapUserDisplayName2 = event.target.value" />

			<NcTextArea
				:model-value="ldapConfigProxy.ldapBaseUsers"
				:placeholder="t('user_ldap', 'One User Base DN per line')"
				:label="t('user_ldap', 'Base User Tree')"
				@change="(event) => ldapConfigProxy.ldapBaseUsers = event.target.value" />

			<NcTextArea
				:model-value="ldapConfigProxy.ldapAttributesForUserSearch"
				:placeholder="t('user_ldap', 'Optional; one attribute per line')"
				:label="t('user_ldap', 'User Search Attributes')"
				@change="(event) => ldapConfigProxy.ldapAttributesForUserSearch = event.target.value" />

			<NcCheckboxRadioSwitch
				:model-value="ldapConfigProxy.markRemnantsAsDisabled === '1'"
				:aria-label="t('user_ldap', 'When switched on, users imported from LDAP which are then missing will be disabled')"
				@update:model-value="ldapConfigProxy.markRemnantsAsDisabled = $event ? '1' : '0'">
				{{ t('user_ldap', 'Disable users missing from LDAP') }}
			</NcCheckboxRadioSwitch>

			<NcTextField
				autocomplete="off"
				:model-value="ldapConfigProxy.ldapGroupDisplayName"
				:label="t('user_ldap', 'Group Display Name Field')"
				:title="t('user_ldap', 'The LDAP attribute to use to generate the groups\'s display name.')"
				@change="(event) => ldapConfigProxy.ldapGroupDisplayName = event.target.value" />

			<NcTextArea
				:model-value="ldapConfigProxy.ldapBaseGroups"
				:placeholder="t('user_ldap', 'One Group Base DN per line')"
				:label="t('user_ldap', 'Base Group Tree')"
				@change="(event) => ldapConfigProxy.ldapBaseGroups = event.target.value" />

			<NcTextArea
				:model-value="ldapConfigProxy.ldapAttributesForGroupSearch"
				:placeholder="t('user_ldap', 'Optional; one attribute per line')"
				:label="t('user_ldap', 'Group Search Attributes')"
				@change="(event) => ldapConfigProxy.ldapAttributesForGroupSearch = event.target.value" />

			<NcSelect
				v-model="ldapConfigProxy.ldapGroupMemberAssocAttr"
				:options="Object.keys(groupMemberAssociation)"
				:input-label="t('user_ldap', 'Group-Member association')">
				<template #option="{ label: configId }">
					{{ groupMemberAssociation[configId] }}
				</template>
				<template #selected-option="{ label: configId }">
					{{ groupMemberAssociation[configId] }}
				</template>
			</NcSelect>

			<NcTextField
				autocomplete="off"
				:label="t('user_ldap', 'Dynamic Group Member URL')"
				:model-value="ldapConfigProxy.ldapDynamicGroupMemberURL"
				:helper-text="t('user_ldap', 'The LDAP attribute that on group objects contains an LDAP search URL that determines what objects belong to the group. (An empty setting disables dynamic group membership functionality.)')"
				@change="(event) => ldapConfigProxy.ldapDynamicGroupMemberURL = event.target.value" />

			<NcCheckboxRadioSwitch
				:model-value="ldapConfigProxy.ldapNestedGroups === '1'"
				:aria-label="t('user_ldap', 'When switched on, groups that contain groups are supported. (Only works if the group member attribute contains DNs.)')"
				@update:model-value="ldapConfigProxy.ldapNestedGroups = $event ? '1' : '0'">
				{{ t('user_ldap', 'Nested Groups') }}
			</NcCheckboxRadioSwitch>

			<NcTextField
				type="number"
				:label="t('user_ldap', 'Paging chunksize')"
				:model-value="ldapConfigProxy.ldapPagingSize"
				:helper-text="t('user_ldap', 'Chunksize used for paged LDAP searches that may return bulky results like user or group enumeration. (Setting it 0 disables paged LDAP searches in those situations.)')"
				@change="(event) => ldapConfigProxy.ldapPagingSize = event.target.value" />

			<NcCheckboxRadioSwitch
				:model-value="ldapConfigProxy.turnOnPasswordChange === '1'"
				:aria-label="t('user_ldap', 'Allow LDAP users to change their password and allow Super Administrators and Group Administrators to change the password of their LDAP users. Only works when access control policies are configured accordingly on the LDAP server. As passwords are sent in plaintext to the LDAP server, transport encryption must be used and password hashing should be configured on the LDAP server.')"
				@update:model-value="ldapConfigProxy.turnOnPasswordChange = $event ? '1' : '0'">
				{{ t('user_ldap', 'Enable LDAP password changes per user') }}
			</NcCheckboxRadioSwitch>
			<span class="tablecell">
				{{ t('user_ldap', '(New password is sent as plain text to LDAP)') }}
			</span>

			<NcTextField
				autocomplete="off"
				:label="t('user_ldap', 'Default password policy DN')"
				:model-value="ldapConfigProxy.ldapDefaultPPolicyDN"
				:helper-text="t('user_ldap', 'The DN of a default password policy that will be used for password expiry handling. Works only when LDAP password changes per user are enabled and is only supported by OpenLDAP. Leave empty to disable password expiry handling.')"
				@change="(event) => ldapConfigProxy.ldapDefaultPPolicyDN = event.target.value" />
		</details>

		<details name="ldap-wizard__advanced__section" class="ldap-wizard__advanced__section">
			<summary><h3>{{ t('user_ldap', 'Special Attributes') }}</h3></summary>

			<NcTextField
				autocomplete="off"
				:model-value="ldapConfigProxy.ldapQuotaAttribute"
				:label="t('user_ldap', 'Quota Field')"
				:helper-text="t('user_ldap', 'Leave empty for user\'s default quota. Otherwise, specify an LDAP/AD attribute.')"
				@change="(event) => ldapConfigProxy.ldapQuotaAttribute = event.target.value" />

			<NcTextField
				autocomplete="off"
				:model-value="ldapConfigProxy.ldapQuotaDefault"
				:label="t('user_ldap', 'Quota Default')"
				:helper-text="t('user_ldap', 'Override default quota for LDAP users who do not have a quota set in the Quota Field.')"
				@change="(event) => ldapConfigProxy.ldapQuotaDefault = event.target.value" />

			<NcTextField
				autocomplete="off"
				:model-value="ldapConfigProxy.ldapEmailAttribute"
				:label="t('user_ldap', 'Email Field')"
				:helper-text="t('user_ldap', 'Set the user\'s email from their LDAP attribute. Leave it empty for default behaviour.')"
				@change="(event) => ldapConfigProxy.ldapEmailAttribute = event.target.value" />

			<NcTextField
				autocomplete="off"
				:label="t('user_ldap', 'User Home Folder Naming Rule')"
				:model-value="ldapConfigProxy.homeFolderNamingRule"
				:helper-text="t('user_ldap', 'Leave empty for username (default). Otherwise, specify an LDAP/AD attribute.')"
				@change="(event) => ldapConfigProxy.homeFolderNamingRule = event.target.value" />

			<NcTextField
				autocomplete="off"
				:label="t('user_ldap', '`$home` Placeholder Field')"
				:model-value="ldapConfigProxy.ldapExtStorageHomeAttribute"
				:helper-text="t('user_ldap', '$home in an external storage configuration will be replaced with the value of the specified attribute')"
				@change="(event) => ldapConfigProxy.ldapExtStorageHomeAttribute = event.target.value" />
		</details>

		<details name="ldap-wizard__advanced__section" class="ldap-wizard__advanced__section">
			<summary><h3>{{ t('user_ldap', 'User Profile Attributes') }}</h3></summary>

			<NcTextField
				autocomplete="off"
				:label="t('user_ldap', 'Phone Field')"
				:model-value="ldapConfigProxy.ldapAttributePhone"
				:helper-text="t('user_ldap', 'User profile Phone will be set from the specified attribute')"
				@change="(event) => ldapConfigProxy.ldapAttributePhone = event.target.value" />

			<NcTextField
				autocomplete="off"
				:label="t('user_ldap', 'Website Field')"
				:model-value="ldapConfigProxy.ldapAttributeWebsite"
				:helper-text="t('user_ldap', 'User profile Website will be set from the specified attribute')"
				@change="(event) => ldapConfigProxy.ldapAttributeWebsite = event.target.value" />

			<NcTextField
				autocomplete="off"
				:label="t('user_ldap', 'Address Field')"
				:model-value="ldapConfigProxy.ldapAttributeAddress"
				:helper-text="t('user_ldap', 'User profile Address will be set from the specified attribute')"
				@change="(event) => ldapConfigProxy.ldapAttributeAddress = event.target.value" />

			<NcTextField
				autocomplete="off"
				:label="t('user_ldap', 'Twitter Field')"
				:model-value="ldapConfigProxy.ldapAttributeTwitter"
				:helper-text="t('user_ldap', 'User profile Twitter will be set from the specified attribute')"
				@change="(event) => ldapConfigProxy.ldapAttributeTwitter = event.target.value" />

			<NcTextField
				autocomplete="off"
				:label="t('user_ldap', 'Fediverse Field')"
				:model-value="ldapConfigProxy.ldapAttributeFediverse"
				:helper-text="t('user_ldap', 'User profile Fediverse will be set from the specified attribute')"
				@change="(event) => ldapConfigProxy.ldapAttributeFediverse = event.target.value" />

			<NcTextField
				autocomplete="off"
				:label="t('user_ldap', 'Organisation Field')"
				:model-value="ldapConfigProxy.ldapAttributeOrganisation"
				:helper-text="t('user_ldap', 'User profile Organisation will be set from the specified attribute')"
				@change="(event) => ldapConfigProxy.ldapAttributeOrganisation = event.target.value" />

			<NcTextField
				autocomplete="off"
				:label="t('user_ldap', 'Role Field')"
				:model-value="ldapConfigProxy.ldapAttributeRole"
				:helper-text="t('user_ldap', 'User profile Role will be set from the specified attribute')"
				@change="(event) => ldapConfigProxy.ldapAttributeRole = event.target.value" />

			<NcTextField
				autocomplete="off"
				:label="t('user_ldap', 'Headline Field')"
				:model-value="ldapConfigProxy.ldapAttributeHeadline"
				:helper-text="t('user_ldap', 'User profile Headline will be set from the specified attribute')"
				@change="(event) => ldapConfigProxy.ldapAttributeHeadline = event.target.value" />

			<NcTextField
				autocomplete="off"
				:label="t('user_ldap', 'Biography Field')"
				:model-value="ldapConfigProxy.ldapAttributeBiography"
				:helper-text="t('user_ldap', 'User profile Biography will be set from the specified attribute')"
				@change="(event) => ldapConfigProxy.ldapAttributeBiography = event.target.value" />

			<NcTextField
				autocomplete="off"
				:label="t('user_ldap', 'Birthdate Field')"
				:model-value="ldapConfigProxy.ldapAttributeBirthDate"
				:helper-text="t('user_ldap', 'User profile Date of birth will be set from the specified attribute')"
				@change="(event) => ldapConfigProxy.ldapAttributeBirthDate = event.target.value" />
		</details>
	</fieldset>
</template>

<script lang="ts" setup>
import { getCapabilities } from '@nextcloud/capabilities'
import { t } from '@nextcloud/l10n'
import { NcCheckboxRadioSwitch, NcSelect, NcTextArea, NcTextField } from '@nextcloud/vue'
import { computed } from 'vue'
import { useLDAPConfigsStore } from '../../store/configs.ts'

const props = defineProps<{ configId: string }>()

const ldapConfigsStore = useLDAPConfigsStore()
const ldapConfigProxy = computed(() => ldapConfigsStore.getConfigProxy(props.configId))

const instanceName = (getCapabilities() as { theming: { name: string } }).theming.name

const groupMemberAssociation = {
	uniqueMember: 'uniqueMember',
	memberUid: 'memberUid',
	member: 'member (AD)',
	gidNumber: 'gidNumber',
	zimbraMailForwardingAddress: 'zimbraMailForwardingAddress',
}
</script>

<style lang="scss" scoped>
.ldap-wizard__advanced {
	display: flex;
	flex-direction: column;
	gap: 16px;

	&__section {
		display: flex;
		flex-direction: column;
		border: 1px solid var(--color-text-lighter);
		border-radius: var(--border-radius);
		padding: 8px;

		& > * {
			margin-top: 12px !important;
		}

		summary {
			margin-top: 0 !important;

			h3 {
				margin: 0;
				display: inline;
				cursor: pointer;
				color: var(--color-text-lighter);
				font-size: 16px;

			}
		}

		&:hover, &[open] {
			h3 {
				color: var(--color-text-light);
			}
		}
	}
}
</style>
