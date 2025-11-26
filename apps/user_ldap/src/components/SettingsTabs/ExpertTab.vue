<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<fieldset class="ldap-wizard__expert">
		<div class="ldap-wizard__expert__line">
			<strong>{{ t('user_ldap', 'Internal Username') }}</strong>
			<p id="ldap_expert_username_attr">
				{{ t('user_ldap', 'By default the internal username will be created from the UUID attribute. It makes sure that the username is unique and characters do not need to be converted. The internal username has the restriction that only these characters are allowed: [a-zA-Z0-9_.@-]. Other characters are replaced with their ASCII correspondence or simply omitted. On collisions a number will be added/increased. The internal username is used to identify a user internally. It is also the default name for the user home folder. It is also a part of remote URLs, for instance for all DAV services. With this setting, the default behavior can be overridden. Changes will have effect only on newly mapped (added) LDAP users. Leave it empty for default behavior.') }}
			</p>
			<NcTextField
				aria-describedby="ldap_expert_username_attr"
				autocomplete="off"
				:label="t('user_ldap', 'Internal Username Attribute:')"
				:model-value="ldapConfigProxy.ldapExpertUsernameAttr"
				@change="(event) => ldapConfigProxy.ldapExpertUsernameAttr = event.target.value" />
		</div>

		<div class="ldap-wizard__expert__line">
			<strong>{{ t('user_ldap', 'Override UUID detection') }}</strong>
			<p id="ldap_expert_uuid_user_attr">
				{{ t('user_ldap', 'By default, the UUID attribute is automatically detected. The UUID attribute is used to doubtlessly identify LDAP users and groups. Also, the internal username will be created based on the UUID, if not specified otherwise above. You can override the setting and pass an attribute of your choice. You must make sure that the attribute of your choice can be fetched for both users and groups and it is unique. Leave it empty for default behavior. Changes will have effect only on newly mapped (added) LDAP users and groups.') }}
			</p>
			<NcTextField
				aria-describedby="ldap_expert_uuid_user_attr"
				autocomplete="off"
				:label="t('user_ldap', 'UUID Attribute for Users')"
				:model-value="ldapConfigProxy.ldapExpertUUIDUserAttr"
				@change="(event) => ldapConfigProxy.ldapExpertUUIDUserAttr = event.target.value" />
			<NcTextField
				autocomplete="off"
				:label="t('user_ldap', 'UUID Attribute for Groups')"
				:model-value="ldapConfigProxy.ldapExpertUUIDGroupAttr"
				@change="(event) => ldapConfigProxy.ldapExpertUUIDGroupAttr = event.target.value" />
		</div>
	</fieldset>
</template>

<script lang="ts" setup>
import { t } from '@nextcloud/l10n'
import { NcTextField } from '@nextcloud/vue'
import { computed } from 'vue'
import { useLDAPConfigsStore } from '../../store/configs.ts'

const props = defineProps<{ configId: string }>()

const ldapConfigsStore = useLDAPConfigsStore()
const ldapConfigProxy = computed(() => ldapConfigsStore.getConfigProxy(props.configId))
</script>

<style lang="scss" scoped>
.ldap-wizard__expert {
	display: flex;
	flex-direction: column;
	gap: 16px;

	&__line {
		display: flex;
		flex-direction: column;
		padding-inline-start: 32px;
		gap: 4px;
	}
}
</style>
