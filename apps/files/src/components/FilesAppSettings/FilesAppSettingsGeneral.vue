<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiAccountOutline, mdiFolderOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxSwitch from '@nextcloud/vue/components/NcFormBoxSwitch'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcRadioGroup from '@nextcloud/vue/components/NcRadioGroup'
import NcRadioGroupButton from '@nextcloud/vue/components/NcRadioGroupButton'
import { useUserConfigStore } from '../../store/userconfig.ts'

const store = useUserConfigStore()
</script>

<template>
	<NcAppSettingsSection
		id="settings"
		:name="t('files', 'General')">
		<NcFormBox>
			<NcFormBoxSwitch
				v-model="store.userConfig.sort_favorites_first"
				:label="t('files', 'Sort favorites first')"
				@update:modelValue="store.update('sort_favorites_first', $event)" />
			<NcFormBoxSwitch
				v-model="store.userConfig.sort_folders_first"
				:label="t('files', 'Sort folders before files')"
				@update:modelValue="store.update('sort_folders_first', $event)" />
			<NcFormBoxSwitch
				v-model="store.userConfig.folder_tree"
				:label="t('files', 'Enable folder tree view')"
				@update:modelValue="store.update('folder_tree', $event)" />
		</NcFormBox>
		<NcRadioGroup
			v-model="store.userConfig.default_view"
			:label="t('files', 'Default view')"
			@update:modelValue="store.update('default_view', $event)">
			<NcRadioGroupButton :label="t('files', 'All files')" value="files">
				<template #icon>
					<NcIconSvgWrapper :path="mdiFolderOutline" />
				</template>
			</NcRadioGroupButton>
			<NcRadioGroupButton :label="t('files', 'Personal files')" value="personal">
				<template #icon>
					<NcIconSvgWrapper :path="mdiAccountOutline" />
				</template>
			</NcRadioGroupButton>
		</NcRadioGroup>
	</NcAppSettingsSection>
</template>
