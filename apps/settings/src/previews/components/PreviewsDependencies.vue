<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import { usePreviewSettingsStore } from '../store/previews.ts'

const store = usePreviewSettingsStore()
</script>

<template>
	<NcSettingsSection
		:name="t('settings', 'Dependencies')"
		:description="t('settings', 'Some providers need extra software. Their paths and the Imaginary URL are set in the configuration file.')">
		<dl :class="$style.dependencies">
			<dt>ImageMagick</dt>
			<dd>{{ store.settings.dependencies.imagick ? t('settings', 'Installed') : t('settings', 'Not installed') }}</dd>
			<dt>ffmpeg</dt>
			<dd>
				<code v-if="store.settings.dependencies.ffmpeg">{{ store.settings.dependencies.ffmpeg }}</code>
				<template v-else>
					{{ t('settings', 'Not found') }}
				</template>
			</dd>
			<dt>LibreOffice</dt>
			<dd>
				<code v-if="store.settings.dependencies.office">{{ store.settings.dependencies.office }}</code>
				<template v-else>
					{{ t('settings', 'Not found') }}
				</template>
			</dd>
			<dt>Imaginary</dt>
			<dd>{{ store.settings.dependencies.imaginary ? t('settings', 'Configured') : t('settings', 'Not configured') }}</dd>
		</dl>
	</NcSettingsSection>
</template>

<style module>
.dependencies {
	display: grid;
	grid-template-columns: max-content 1fr;
	gap: var(--default-grid-baseline) calc(var(--default-grid-baseline) * 4);
}
</style>
