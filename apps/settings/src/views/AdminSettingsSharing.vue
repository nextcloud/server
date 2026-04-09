<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSettingsSection
		data-cy-settings-sharing-section
		:doc-url="documentationLink"
		:name="t('settings', 'Sharing')"
		:description="t('settings', 'As admin you can fine-tune the sharing behavior. Please see the documentation for more information.')">
		<NcNoteCard v-if="!sharingAppEnabled" type="warning">
			{{ t('settings', 'You need to enable the File sharing App.') }}
		</NcNoteCard>
		<AdminSettingsSharingForm v-else />
	</NcSettingsSection>
</template>

<script lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import AdminSettingsSharingForm from '../components/AdminSettingsSharingForm.vue'

export default defineComponent({
	name: 'AdminSettingsSharing',
	components: {
		AdminSettingsSharingForm,
		NcNoteCard,
		NcSettingsSection,
	},

	data() {
		return {
			documentationLink: loadState<string>('settings', 'sharingDocumentation', ''),
			sharingAppEnabled: loadState<boolean>('settings', 'sharingAppEnabled', false),
		}
	},

	methods: {
		t,
	},
})
</script>
