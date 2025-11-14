<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxSwitch from '@nextcloud/vue/components/NcFormBoxSwitch'
import { useUserConfigStore } from '../../store/userconfig.ts'

const store = useUserConfigStore()
</script>

<template>
	<NcAppSettingsSection id="appearance" :name="t('files', 'Appearance')">
		<NcFormBox>
			<NcFormBoxSwitch
				v-model="store.userConfig.show_hidden"
				:label="t('files', 'Show hidden files')"
				@update:modelValue="store.update('show_hidden', $event)" />
			<NcFormBoxSwitch
				v-model="store.userConfig.show_mime_column"
				:label="t('files', 'Show file type column')"
				@update:modelValue="store.update('show_mime_column', $event)" />
			<NcFormBoxSwitch
				v-model="store.userConfig.show_files_extensions"
				:label="t('files', 'Show file extensions')"
				@update:modelValue="store.update('show_files_extensions', $event)" />
			<NcFormBoxSwitch
				v-model="store.userConfig.crop_image_previews"
				:label="t('files', 'Crop image previews')"
				@update:modelValue="store.update('crop_image_previews', $event)" />
		</NcFormBox>
	</NcAppSettingsSection>
</template>
