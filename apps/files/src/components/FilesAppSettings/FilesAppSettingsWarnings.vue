<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script lang="ts" setup>
import { t } from '@nextcloud/l10n'
import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxSwitch from '@nextcloud/vue/components/NcFormBoxSwitch'
import { useUserConfigStore } from '../../store/userconfig.ts'

const store = useUserConfigStore()
</script>

<template>
	<NcAppSettingsSection id="warning" :name="t('files', 'Warnings')">
		<NcFormBox>
			<NcFormBoxSwitch
				v-model="store.userConfig.show_dialog_file_extension"
				:label="t('files', 'Warn before changing a file extension')"
				@update:modelValue="store.update('show_dialog_file_extension', $event)" />
			<NcFormBoxSwitch
				v-model="store.userConfig.show_dialog_deletion"
				:label="t('files', 'Warn before deleting a file')"
				@update:modelValue="store.update('show_dialog_deletion', $event)" />
		</NcFormBox>
	</NcAppSettingsSection>
</template>
