<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { PreviewLimits } from '../types.ts'

import { t } from '@nextcloud/l10n'
import NcFormGroup from '@nextcloud/vue/components/NcFormGroup'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import PreviewsNumberField from './PreviewsNumberField.vue'
import { usePreviewSettingsStore } from '../store/previews.ts'

const store = usePreviewSettingsStore()

/**
 * Store one limit
 *
 * @param key The limit to change
 * @param value The new value, `null` for the default
 */
function update(key: keyof PreviewLimits, value: number | null): void {
	store.updateSettings({ [key]: value })
}
</script>

<template>
	<NcSettingsSection
		:name="t('settings', 'Limits')"
		:description="t('settings', 'These limits apply to every preview. Leave a field empty to use the default.')">
		<NcFormGroup :label="t('settings', 'Size')">
			<PreviewsNumberField
				:value="store.settings.maxX"
				:label="t('settings', 'Maximum width (pixels)')"
				:helperText="t('settings', 'Width of the largest preview generated.')"
				:min="1"
				placeholder="4096"
				:disabled="store.readOnly || store.saving"
				@change="update('maxX', $event)" />
			<PreviewsNumberField
				:value="store.settings.maxY"
				:label="t('settings', 'Maximum height (pixels)')"
				:helperText="t('settings', 'Height of the largest preview generated.')"
				:min="1"
				placeholder="4096"
				:disabled="store.readOnly || store.saving"
				@change="update('maxY', $event)" />
			<PreviewsNumberField
				:value="store.settings.maxMemory"
				:label="t('settings', 'Maximum memory (MB)')"
				:helperText="t('settings', 'Images that need more memory to resize are skipped. -1 for no limit.')"
				:min="-1"
				placeholder="256"
				:disabled="store.readOnly || store.saving"
				@change="update('maxMemory', $event)" />
			<PreviewsNumberField
				:value="store.settings.maxFilesizeImage"
				:label="t('settings', 'Maximum image file size (MB)')"
				:helperText="t('settings', 'Larger images get no preview. -1 for no limit.')"
				:min="-1"
				placeholder="50"
				:disabled="store.readOnly || store.saving"
				@change="update('maxFilesizeImage', $event)" />
			<PreviewsNumberField
				:value="store.settings.expirationDays"
				:label="t('settings', 'Delete previews after (days)')"
				:helperText="t('settings', 'Generated previews older than this are deleted daily. 0 keeps them.')"
				:min="0"
				placeholder="0"
				:disabled="store.readOnly || store.saving"
				@change="update('expirationDays', $event)" />
		</NcFormGroup>

		<NcFormGroup :label="t('settings', 'Quality')">
			<PreviewsNumberField
				:value="store.settings.jpegQuality"
				:label="t('settings', 'JPEG quality')"
				:helperText="t('settings', 'From 1 to 100. Higher looks better and takes more space.')"
				:min="1"
				:max="100"
				placeholder="80"
				:disabled="store.readOnly || store.saving"
				@change="update('jpegQuality', $event)" />
			<PreviewsNumberField
				:value="store.settings.webpQuality"
				:label="t('settings', 'WebP quality')"
				:helperText="t('settings', 'From 1 to 100. Higher looks better and takes more space.')"
				:min="1"
				:max="100"
				placeholder="80"
				:disabled="store.readOnly || store.saving"
				@change="update('webpQuality', $event)" />
		</NcFormGroup>

		<NcFormGroup :label="t('settings', 'Concurrency')">
			<PreviewsNumberField
				:value="store.settings.concurrencyNew"
				:label="t('settings', 'Previews generated at the same time')"
				:helperText="t('settings', 'Defaults to the number of CPU cores.')"
				:min="1"
				:disabled="store.readOnly || store.saving"
				@change="update('concurrencyNew', $event)" />
			<PreviewsNumberField
				:value="store.settings.concurrencyAll"
				:label="t('settings', 'Preview requests handled at the same time')"
				:helperText="t('settings', 'Includes previews that already exist. Defaults to twice the number of CPU cores.')"
				:min="1"
				:disabled="store.readOnly || store.saving"
				@change="update('concurrencyAll', $event)" />
		</NcFormGroup>
	</NcSettingsSection>
</template>
