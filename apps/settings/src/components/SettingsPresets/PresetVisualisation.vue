<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

import type { PresetAppConfigs } from './models.ts'

const applications = [] // TODO: get the list of applications from the presets

defineProps({
	presets: {
		type: Object as () => PresetAppConfigs,
		required: true,
	},
	selectedPreset: {
		type: String,
		default: '',
	},
})
</script>

<template>
	<div class="preset">
		<h3 class="preset__title">
			{{ t('settings', 'Default values') }}
		</h3>
		<p class="preset__subtitle">
			{{ t('settings', 'The following values will be used as default value when settings are not manually set.') }}
		</p>

		<div class="preset__config-list">
			<template v-for="(presetConfigs, appId) in presets">
				<div v-for="config in presetConfigs"
					:key="appId + '-' + config.entry.key"
					class="preset__config-list__key">
					<span>
						{{ config.entry.definition }}
						<div class="preset__config-list__key__value">{{ config.entry.key }}</div>
					</span>
					<span>
						<NcCheckboxRadioSwitch v-if="config.entry.type === 'BOOL'"
							:model-value="config.defaults[selectedPreset] === '1'"
							:disabled="true" />
						<code v-else>{{ config.defaults[selectedPreset] }}</code>
					</span>
				</div>
			</template>
		</div>

		<h3 class="preset__title">
			{{ t('settings', 'Application bundle') }}
		</h3>
		<p class="preset__subtitle">
			{{ t('settings', 'Applying the preset will install and uninstall the following applications.') }}
		</p>
		<div class="preset__app-list">
			<div v-for="application in applications" :key="application">
				{{ application }}
			</div>
		</div>
	</div>
</template>

<style lang="scss" scoped>
.preset {
	margin-top: 16px;

	&__title {
		font-size: 16px;
		margin-bottom: 0;
	}

	&__subtitle {
		color: var(--color-text-maxcontrast);
	}

	&__config-list {
		margin-top: 8px;
		width: 75%;

		&__key {
			display: flex;
			justify-content: space-between;
			padding: 2px 0;

			&__value {
				color: var(--color-text-maxcontrast);
			}
		}
	}
}
</style>
