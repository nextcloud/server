<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import AdminSettingsSharingForm from '../components/AdminSettingsSharingForm.vue'

const documentationLink = loadState<string>('settings', 'sharingDocumentation', '')
const sharingAppEnabled = loadState<boolean>('settings', 'sharingAppEnabled', false)
</script>

<template>
	<NcSettingsSection
		:docUrl="documentationLink"
		:name="t('settings', 'Sharing')"
		:description="t('settings', 'As admin you can fine-tune the sharing behavior. Please see the documentation for more information.')">
		<NcNoteCard v-if="!sharingAppEnabled" type="warning">
			{{ t('settings', 'You need to enable the File sharing App.') }}
		</NcNoteCard>
		<AdminSettingsSharingForm v-else />
	</NcSettingsSection>
</template>
