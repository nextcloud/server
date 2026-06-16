<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { getRequestToken } from '@nextcloud/auth'
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcTextField from '@nextcloud/vue/components/NcTextField'

defineProps<{
	appTokenUrl: string
	direct: boolean
	stateToken: string
}>()

const requestToken = getRequestToken()
</script>

<template>
	<form :action="appTokenUrl" :class="$style.loginFlowAuthAppToken" method="post">
		<NcFormBox>
			<NcTextField name="user" :label="t('core', 'Login')" />
			<NcPasswordField name="password" :label="t('core', 'App password')" />
		</NcFormBox>
		<input type="hidden" name="stateToken" :value="stateToken">
		<input type="hidden" name="requesttoken" :value="requestToken">
		<input
			v-if="direct"
			type="hidden"
			name="direct"
			value="1">

		<NcButton type="submit" variant="primary" wide>
			{{ t('core', 'Grant access') }}
		</NcButton>
	</form>
</template>

<style module>
.loginFlowAuthAppToken {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
}
</style>
