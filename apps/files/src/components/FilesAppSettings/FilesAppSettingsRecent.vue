<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script lang="ts" setup>
import { t } from '@nextcloud/l10n'
import { NcInputField } from '@nextcloud/vue'
import debounce from 'debounce'
import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import { useUserConfigStore } from '../../store/userconfig.ts'

const store = useUserConfigStore()
const debouncedUpdate = debounce((value: number) => {
	store.update('recent_files_limit', value)
}, 500)
</script>

<template>
	<NcAppSettingsSection id="recent" :name="t('files', 'Recent view')">
		<NcFormBox>
			<NcInputField
				v-model="store.userConfig.recent_files_limit"
				type="number"
				:min="1"
				:max="100"
				:label="t('files', 'Maximum number of files shown in the Recent view')"
				@update:model-value="debouncedUpdate(Number($event))" />
		</NcFormBox>
	</NcAppSettingsSection>
</template>
