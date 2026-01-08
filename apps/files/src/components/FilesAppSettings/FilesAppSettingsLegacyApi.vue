<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type Setting from '../../models/Setting.ts'

import { t } from '@nextcloud/l10n'
import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'
import FilesAppSettingsLegacyApiEntry from './FilesAppSettingsLegacyApiEntry.vue'

const apiSettings = ((window.OCA?.Files?.Settings?.settings || []) as Setting[])
	.sort((a, b) => {
		if (a.order && b.order) {
			return a.order - b.order
		}
		return a.name.localeCompare(b.name)
	})
</script>

<template>
	<NcAppSettingsSection
		v-if="apiSettings.length !== 0"
		id="api-settings"
		:name="t('files', 'Additional settings')">
		<FilesAppSettingsLegacyApiEntry v-for="setting in apiSettings" :key="setting.name" :setting="setting" />
	</NcAppSettingsSection>
</template>
