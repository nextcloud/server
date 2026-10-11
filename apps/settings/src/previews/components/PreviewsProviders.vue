<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { PreviewProvider } from '../types.ts'

import { mdiArrowDown, mdiArrowUp } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import { usePreviewSettingsStore } from '../store/previews.ts'

const store = usePreviewSettingsStore()

const locked = computed(() => store.readOnly || store.saving)

/**
 * Why a provider can or cannot run on this server
 *
 * @param provider The provider
 */
function availability(provider: PreviewProvider): string {
	if (provider.available) {
		return t('settings', 'Available')
	}
	switch (provider.requirement) {
		case 'imagick':
			return t('settings', 'Not supported by ImageMagick on this server')
		case 'office':
			return t('settings', 'Requires LibreOffice')
		case 'ffmpeg':
			return t('settings', 'Requires ffmpeg')
		case 'imaginary':
			return t('settings', 'Requires Imaginary')
		default:
			return t('settings', 'Not available')
	}
}

/**
 * Position of an enabled provider in the try-order
 *
 * @param provider The provider
 */
function position(provider: PreviewProvider): number {
	return store.enabledProviders.indexOf(provider)
}
</script>

<template>
	<NcSettingsSection
		:name="t('settings', 'Providers')"
		:description="t('settings', 'Enabled providers are tried from top to bottom. When one fails, the next one supporting the file type is tried.')">
		<table :class="$style.providers">
			<caption class="hidden-visually">
				{{ t('settings', 'Preview providers') }}
			</caption>
			<thead>
				<tr>
					<th scope="col">
						{{ t('settings', 'Provider') }}
					</th>
					<th scope="col">
						{{ t('settings', 'File types') }}
					</th>
					<th scope="col">
						{{ t('settings', 'Availability') }}
					</th>
					<th scope="col">
						{{ t('settings', 'Order') }}
					</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="provider in store.settings.providers" :key="provider.class">
					<th scope="row">
						<NcCheckboxRadioSwitch
							:modelValue="provider.enabled"
							type="switch"
							:disabled="locked || (!provider.available && !provider.enabled)"
							@update:modelValue="store.toggleProvider(provider.class, $event)">
							{{ provider.name }}
						</NcCheckboxRadioSwitch>
					</th>
					<td>{{ provider.mimetypes }}</td>
					<td :class="{ [$style.unavailable]: !provider.available }">
						{{ availability(provider) }}
					</td>
					<td>
						<div v-if="provider.enabled" :class="$style.order">
							<NcButton
								variant="tertiary"
								:aria-label="t('settings', 'Try {provider} earlier', { provider: provider.name })"
								:disabled="locked || position(provider) === 0"
								@click="store.moveProvider(provider.class, -1)">
								<template #icon>
									<NcIconSvgWrapper :path="mdiArrowUp" />
								</template>
							</NcButton>
							<NcButton
								variant="tertiary"
								:aria-label="t('settings', 'Try {provider} later', { provider: provider.name })"
								:disabled="locked || position(provider) === store.enabledProviders.length - 1"
								@click="store.moveProvider(provider.class, 1)">
								<template #icon>
									<NcIconSvgWrapper :path="mdiArrowDown" />
								</template>
							</NcButton>
						</div>
					</td>
				</tr>
			</tbody>
		</table>

		<NcButton
			:class="$style.reset"
			:disabled="locked || !store.settings.providersConfigured"
			@click="store.resetProviders()">
			{{ t('settings', 'Reset to default providers') }}
		</NcButton>
	</NcSettingsSection>
</template>

<style module>
.providers {
	width: 100%;

	th, td {
		padding-block: var(--default-grid-baseline);
		padding-inline-end: calc(var(--default-grid-baseline) * 4);
		text-align: start;
	}

	thead th {
		color: var(--color-text-maxcontrast);
		font-weight: normal;
	}

	tbody th {
		font-weight: normal;
	}
}

.unavailable {
	color: var(--color-text-maxcontrast);
}

.order {
	display: flex;
}

.reset {
	margin-block-start: calc(var(--default-grid-baseline) * 4);
}
</style>
