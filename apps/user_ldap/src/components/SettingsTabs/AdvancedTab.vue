<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<fieldset class="ldap-wizard__advanced">
		<details open="true" name="ldap-wizard__advanced__section" class="ldap-wizard__advanced__section">
			<summary><h3>{{ t('user_ldap', 'Connection Settings') }}</h3></summary>

			<NcCheckboxRadioSwitch :checked="ldapConfig.ldapConfigurationActive === '1'"
				:aria-label="t('user_ldap', 'When unchecked, this configuration will be skipped.')"
				@update:checked="ldapConfig.ldapConfigurationActive = $event ? '1' : '0'">
				{{ t('user_ldap', 'Configuration Active') }}
			</NcCheckboxRadioSwitch>

			<NcTextField autocomplete="off"
				:label=" t('user_ldap', 'Backup (Replica) Host')"
				:value.sync="ldapConfig.ldapBackupHost"
				:helper-text="t('user_ldap', 'Give an optional backup host. It must be a replica of the main LDAP/AD server.')" />

			<NcTextField type="number"
				:value="ldapConfig.ldapBackupPort"
				:label="t('user_ldap', 'Backup (Replica) Port') " />

			<NcCheckboxRadioSwitch :checked="ldapConfig.ldapOverrideMainServer === '1'"
				:aria-label="t('user_ldap', 'Only connect to the replica server.')"
				@update:checked="ldapConfig.ldapOverrideMainServer = $event ? '1' : '0'">
				{{ t('user_ldap', 'Disable Main Server') }}
			</NcCheckboxRadioSwitch>

			<NcCheckboxRadioSwitch :checked="ldapConfig.turnOffCertCheck === '1'"
				:aria-label="t('user_ldap', 'Not recommended, use it for testing only! If connection only works with this option, import the LDAP server\'s SSL certificate in your {instanceName} server.', { instanceName })"
				@update:checked="ldapConfig.turnOffCertCheck = $event ? '1' : '0'">
				{{ t('user_ldap', 'Turn off SSL certificate validation.') }}
			</NcCheckboxRadioSwitch>

			<NcTextField type="number"
				:label="t('user_ldap', 'Cache Time-To-Live')"
				:value="ldapConfig.ldapCacheTTL"
				:helper-text="t('user_ldap', 'in seconds. A change empties the cache.')" />
		</details>

		<details name="ldap-wizard__advanced__section" class="ldap-wizard__advanced__section">
			<summary><h3>{{ t('user_ldap', 'Directory Settings') }}</h3></summary>

			<NcTextField autocomplete="off"
				:value.sync="ldapConfig.ldapUserDisplayName"
				:label="t('user_ldap', 'User Display Name Field')"
				:helper-text="t('user_ldap', 'The LDAP attribute to use to generate the user\'s display name.')" />

			<NcTextField autocomplete="off"
				:value.sync="ldapConfig.ldapUserDisplayName2"
				:label="t('user_ldap', '2nd User Display Name Field')"
				:helper-text="t('user_ldap', 'Optional. An LDAP attribute to be added to the display name in brackets. Results in e.g. »John Doe (john.doe@example.org)«.')" />

			<NcTextArea :value.sync="ldapConfig.ldapBaseUsers"
				:placeholder="t('user_ldap', 'One User Base DN per line')"
				:label="t('user_ldap', 'Base User Tree')" />

			<NcTextArea :value.sync="ldapConfig.ldapAttributesForUserSearch"
				:placeholder="t('user_ldap', 'Optional; one attribute per line')"
				:label="t('user_ldap', 'Base User Tree')"
				:helper-text="t('user_ldap', 'User Search Attributes')" />

			<NcCheckboxRadioSwitch :checked="ldapConfig.markRemnantsAsDisabled === '1'"
				:aria-label="t('user_ldap', 'When switched on, users imported from LDAP which are then missing will be disabled')"
				@update:checked="ldapConfig.markRemnantsAsDisabled = $event ? '1' : '0'">
				{{ t('user_ldap', 'Disable users missing from LDAP') }}
			</NcCheckboxRadioSwitch>

			<NcTextField autocomplete="off"
				:value.sync="ldapConfig.ldapGroupDisplayName"
				:label="t('user_ldap', 'Group Display Name Field')"
				:title="t('user_ldap', 'The LDAP attribute to use to generate the groups\'s display name.')" />

			<NcTextArea :value.sync="ldapConfig.ldapBaseGroups"
				:placeholder="t('user_ldap', 'One Group Base DN per line')"
				:label="t('user_ldap', 'Base Group Tree')" />

			<NcTextArea :value.sync="ldapConfig.ldapAttributesForGroupSearch"
				:placeholder="t('user_ldap', 'Optional; one attribute per line')"
				:label="t('user_ldap', 'Group Search Attributes')" />

			<NcSelect v-model="ldapConfig.ldapGroupMemberAssocAttr"
				:options="Object.keys(groupMemberAssociation)"
				:input-label="t('user_ldap', 'Group-Member association')">
				<template #option="{label: configId}">
					{{ groupMemberAssociation[configId] }}
				</template>
				<template #selected-option="{label: configId}">
					{{ groupMemberAssociation[configId] }}
				</template>
			</NcSelect>

			<NcTextField autocomplete="off"
				:label="t('user_ldap', 'Dynamic Group Member URL')"
				:value.sync="ldapConfig.ldapDynamicGroupMemberURL"
				:helper-text="t('user_ldap', 'The LDAP attribute that on group objects contains an LDAP search URL that determines what objects belong to the group. (An empty setting disables dynamic group membership functionality.)')" />

			<NcCheckboxRadioSwitch :checked="ldapConfig.ldapNestedGroups === '1'"
				:aria-label="t('user_ldap', 'When switched on, groups that contain groups are supported. (Only works if the group member attribute contains DNs.)')"
				@update:checked="ldapConfig.ldapNestedGroups = $event ? '1' : '0'">
				{{ t('user_ldap', 'Nested Groups') }}
			</NcCheckboxRadioSwitch>

			<NcTextField type="number"
				:label="t('user_ldap', 'Paging chunksize')"
				:value.sync="ldapConfig.ldapPagingSize"
				:helper-text="t('user_ldap', 'Chunksize used for paged LDAP searches that may return bulky results like user or group enumeration. (Setting it 0 disables paged LDAP searches in those situations.)')" />

			<NcCheckboxRadioSwitch :checked="ldapConfig.turnOnPasswordChange === '1'"
				:aria-label="t('user_ldap', 'Allow LDAP users to change their password and allow Super Administrators and Group Administrators to change the password of their LDAP users. Only works when access control policies are configured accordingly on the LDAP server. As passwords are sent in plaintext to the LDAP server, transport encryption must be used and password hashing should be configured on the LDAP server.')"
				@update:checked="ldapConfig.turnOnPasswordChange = $event ? '1' : '0'">
				{{ t('user_ldap', 'Enable LDAP password changes per user') }}
			</NcCheckboxRadioSwitch>
			<span class="tablecell">
				{{ t('user_ldap', '(New password is sent as plain text to LDAP)') }}
			</span>

			<NcTextField autocomplete="off"
				:label="t('user_ldap', 'Default password policy DN')"
				:value.sync="ldapConfig.ldapDefaultPPolicyDN"
				:helper-text="t('user_ldap', 'The DN of a default password policy that will be used for password expiry handling. Works only when LDAP password changes per user are enabled and is only supported by OpenLDAP. Leave empty to disable password expiry handling.')" />
		</details>

		<details name="ldap-wizard__advanced__section" class="ldap-wizard__advanced__section">
			<summary><h3>{{ t('user_ldap', 'Special Attributes') }}</h3></summary>

			<NcTextField autocomplete="off"
				:value.sync="ldapConfig.ldapQuotaAttribute"
				:label="t('user_ldap', 'Quota Field')"
				:helper-text="t('user_ldap', 'Leave empty for user\'s default quota. Otherwise, specify an LDAP/AD attribute.')" />

			<NcTextField autocomplete="off"
				:value.sync="ldapConfig.ldapQuotaDefault"
				:label="t('user_ldap', 'Quota Default')"
				:helper-text="t('user_ldap', 'Override default quota for LDAP users who do not have a quota set in the Quota Field.')" />

			<NcTextField autocomplete="off"
				:value.sync="ldapConfig.ldapEmailAttribute"
				:label="t('user_ldap', 'Email Field')"
				:helper-text="t('user_ldap', 'Set the user\'s email from their LDAP attribute. Leave it empty for default behaviour.')" />

			<NcTextField autocomplete="off"
				:label="t('user_ldap', 'User Home Folder Naming Rule')"
				:value.sync="ldapConfig.homeFolderNamingRule"
				:helper-text="t('user_ldap', 'Leave empty for username (default). Otherwise, specify an LDAP/AD attribute.')" />

			<NcTextField autocomplete="off"
				:label="t('user_ldap', '`$home` Placeholder Field')"
				:value.sync="ldapConfig.ldapExtStorageHomeAttribute"
				:helper-text="t('user_ldap', '$home in an external storage configuration will be replaced with the value of the specified attribute')" />
		</details>

		<details name="ldap-wizard__advanced__section" class="ldap-wizard__advanced__section">
			<summary><h3>{{ t('user_ldap', 'User Profile Attributes') }}</h3></summary>

			<NcTextField autocomplete="off"
				:label="t('user_ldap', 'Phone Field')"
				:value.sync="ldapConfig.ldapAttributePhone"
				:helper-text="t('user_ldap', 'User profile Phone will be set from the specified attribute')" />

			<NcTextField autocomplete="off"
				:label="t('user_ldap', 'Website Field')"
				:value.sync="ldapConfig.ldapAttributeWebsite"
				:helper-text="t('user_ldap', 'User profile Website will be set from the specified attribute')" />

			<NcTextField autocomplete="off"
				:label="t('user_ldap', 'Address Field')"
				:value.sync="ldapConfig.ldapAttributeAddress"
				:helper-text="t('user_ldap', 'User profile Address will be set from the specified attribute')" />

			<NcTextField autocomplete="off"
				:label="t('user_ldap', 'Twitter Field')"
				:value.sync="ldapConfig.ldapAttributeTwitter"
				:helper-text="t('user_ldap', 'User profile Twitter will be set from the specified attribute')" />

			<NcTextField autocomplete="off"
				:label="t('user_ldap', 'Fediverse Field')"
				:value.sync="ldapConfig.ldapAttributeFediverse"
				:helper-text="t('user_ldap', 'User profile Fediverse will be set from the specified attribute')" />

			<NcTextField autocomplete="off"
				:label="t('user_ldap', 'Organisation Field')"
				:value.sync="ldapConfig.ldapAttributeOrganisation"
				:helper-text="t('user_ldap', 'User profile Organisation will be set from the specified attribute')" />

			<NcTextField autocomplete="off"
				:label="t('user_ldap', 'Role Field')"
				:value.sync="ldapConfig.ldapAttributeRole"
				:helper-text="t('user_ldap', 'User profile Role will be set from the specified attribute')" />

			<NcTextField autocomplete="off"
				:label="t('user_ldap', 'Headline Field')"
				:value.sync="ldapConfig.ldapAttributeHeadline"
				:helper-text="t('user_ldap', 'User profile Headline will be set from the specified attribute')" />

			<NcTextField autocomplete="off"
				:label="t('user_ldap', 'Biography Field')"
				:value.sync="ldapConfig.ldapAttributeBiography"
				:helper-text="t('user_ldap', 'User profile Biography will be set from the specified attribute')" />

			<NcTextField autocomplete="off"
				:label="t('user_ldap', 'Birthdate Field')"
				:value.sync="ldapConfig.ldapAttributeBirthDate"
				:helper-text="t('user_ldap', 'User profile Date of birth will be set from the specified attribute')" />
		</details>
	</fieldset>
</template>

<script lang="ts" setup>
import { storeToRefs } from 'pinia'

import { t } from '@nextcloud/l10n'
import { NcTextField, NcTextArea, NcCheckboxRadioSwitch, NcSelect } from '@nextcloud/vue'
import { getCapabilities } from '@nextcloud/capabilities'

import { useLDAPConfigsStore } from '../../store/configs'

const ldapConfigsStore = useLDAPConfigsStore()
const { selectedConfig: ldapConfig } = storeToRefs(ldapConfigsStore)

const instanceName = (getCapabilities() as { theming: { name:string } }).theming.name

const groupMemberAssociation = {
	uniqueMember: t('user_ldap', 'uniqueMember'),
	memberUid: t('user_ldap', 'memberUid'),
	member: t('user_ldap', 'member (AD)'),
	gidNumber: t('user_ldap', 'gidNumber'),
	zimbraMailForwardingAddress: t('user_ldap', 'zimbraMailForwardingAddress'),
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
