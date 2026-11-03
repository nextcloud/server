<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxSwitch from '@nextcloud/vue/components/NcFormBoxSwitch'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import PreviewsDependencies from './PreviewsDependencies.vue'
import PreviewsLimits from './PreviewsLimits.vue'
import PreviewsProviders from './PreviewsProviders.vue'
import { usePreviewSettingsStore } from '../store/previews.ts'

const store = usePreviewSettingsStore()
</script>

<template>
	<div>
		<NcSettingsSection
			:name="t('settings', 'Previews')"
			:description="t('settings', 'Thumbnails and previews shown for files.')">
			<NcNoteCard v-if="store.readOnly" type="info">
				{{ t('settings', 'The configuration file is read-only, these settings can only be changed in it.') }}
			</NcNoteCard>
			<NcFormBox>
				<NcFormBoxSwitch
					:modelValue="store.settings.enabled"
					:label="t('settings', 'Enable previews')"
					:description="t('settings', 'When disabled, no previews are generated or served.')"
					:disabled="store.readOnly || store.saving"
					@update:modelValue="store.updateSettings({ enabled: $event })" />
			</NcFormBox>
		</NcSettingsSection>

		<template v-if="store.settings.enabled">
			<PreviewsProviders />
			<PreviewsLimits />
			<PreviewsDependencies />
		</template>
	</div>
</template>
