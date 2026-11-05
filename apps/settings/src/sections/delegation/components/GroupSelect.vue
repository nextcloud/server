<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import type { AuthorizedGroup, DelegatedSetting, Group } from '../types.ts'

import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { PwdConfirmationMode } from '@nextcloud/password-confirmation'
import { generateUrl } from '@nextcloud/router'
import { ref, watch } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import logger from '../../../logger.ts'

const props = defineProps<{
	/** Groups that can be picked */
	availableGroups: Group[]
	/** The setting to delegate */
	setting: DelegatedSetting
	/** Groups currently allowed to access any delegated setting */
	authorizedGroups: AuthorizedGroup[]
}>()

const selected = ref(props.authorizedGroups
	.filter((group) => group.class === props.setting.class)
	.map((groupToMap) => props.availableGroups.find((group) => group.gid === groupToMap.group_id))
	.filter((group) => group !== undefined))

watch(selected, saveGroups)

/**
 * Save the selected groups for this setting
 */
async function saveGroups() {
	const data = {
		newGroups: selected.value,
		class: props.setting.class,
	}
	try {
		await axios.post(generateUrl('/apps/settings/') + '/settings/authorizedgroups/saveSettings', data, { confirmPassword: PwdConfirmationMode.Strict })
	} catch (e) {
		showError(t('settings', 'Unable to modify setting'))
		logger.error('Unable to modify setting', e)
	}
}
</script>

<template>
	<NcSelect
		v-model="selected"
		:inputId="setting.id"
		:class="$style.groupSelect"
		:placeholder="t('settings', 'None')"
		label="displayName"
		:options="availableGroups"
		multiple
		keepOpen />
</template>

<style module>
.groupSelect {
	width: 100%;
}
</style>
