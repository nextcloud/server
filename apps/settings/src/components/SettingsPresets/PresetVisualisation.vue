<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { PresetAppConfig, PresetAppConfigs, PresetAppsStates, PresetIds } from './models.ts'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

const props = defineProps({
	presets: {
		type: Object as () => PresetAppConfigs,
		required: true,
	},
	selectedPreset: {
		type: String as () => PresetIds,
		default: 'NONE',
	},
})

const applicationsStates = loadState('settings', 'settings-presets-apps', {}) as PresetAppsStates

const appsConfigPresets = Object.entries(props.presets)
	.map(([appId, presets]) => [appId, presets.filter((configPreset) => configPreset.config === 'app')])
	.filter(([, presets]) => presets.length > 0) as [string, PresetAppConfig[]][]
const userConfigPresets = Object.entries(props.presets)
	.map(([appId, presets]) => [appId, presets.filter((configPreset) => configPreset.config === 'user')])
	.filter(([, presets]) => presets.length > 0) as [string, PresetAppConfig[]][]

const hasApplicationsPreset = computed(() => applicationsStates[props.selectedPreset].enabled.length > 0 || applicationsStates[props.selectedPreset].disabled.length > 0)
</script>

<template>
	<div class="presets">
		<h3 class="presets__title">
			{{ t('settings', 'Default config values') }}
		</h3>

		<div v-if="appsConfigPresets.length > 0" class="presets__config-list">
			<h4 class="presets__config-list__subtitle">
				{{ t('settings', 'Applications config') }}
			</h4>
			<template v-for="[appId, appConfigPresets] in appsConfigPresets">
				<div
					v-for="configPreset in appConfigPresets"
					:key="appId + '-' + configPreset.entry.key"
					class="presets__config-list__item">
					<span>
						<div>{{ configPreset.entry.definition }}</div>
						<code class="presets__config-list__item__key">{{ configPreset.entry.key }}</code>
					</span>
					<span>
						<NcCheckboxRadioSwitch
							v-if="configPreset.entry.type === 'BOOL'"
							:model-value="configPreset.defaults[selectedPreset] === '1'"
							:disabled="true" />
						<code v-else>{{ configPreset.defaults[selectedPreset] }}</code>
					</span>
				</div>
			</template>
		</div>

		<div v-if="userConfigPresets.length > 0" class="presets__config-list">
			<h4 class="presets__config-list__subtitle">
				{{ t('settings', 'User config') }}
			</h4>
			<template v-for="[appId, userPresets] in userConfigPresets">
				<div
					v-for="configPreset in userPresets"
					:key="appId + '-' + configPreset.entry.key"
					class="presets__config-list__item">
					<span>
						<div>{{ configPreset.entry.definition }}</div>
						<code class="presets__config-list__item__key">{{ configPreset.entry.key }}</code>
					</span>
					<span>
						<NcCheckboxRadioSwitch
							v-if="configPreset.entry.type === 'BOOL'"
							:model-value="configPreset.defaults[selectedPreset] === '1'"
							:disabled="true" />
						<code v-else>{{ configPreset.defaults[selectedPreset] }}</code>
					</span>
				</div>
			</template>
		</div>

		<template v-if="hasApplicationsPreset">
			<h3 class="presets__title">
				{{ t('settings', 'Bundled applications') }}
			</h3>

			<div class="presets__app-list">
				<div class="presets__app-list__enabled">
					<h4 class="presets__app-list__title">
						{{ t('settings', 'Enabled applications') }}
					</h4>
					<ul>
						<li
							v-for="applicationId in applicationsStates[selectedPreset].enabled"
							:key="applicationId">
							{{ applicationId }}
						</li>
					</ul>
				</div>
				<div class="presets__app-list__disabled">
					<h4 class="presets__app-list__title">
						{{ t('settings', 'Disabled applications') }}
					</h4>
					<ul>
						<li
							v-for="applicationId in applicationsStates[selectedPreset].disabled"
							:key="applicationId">
							{{ applicationId }}
						</li>
					</ul>
				</div>
			</div>
		</template>
	</div>
</template>

<style lang="scss" scoped>
.presets {
	margin-top: 16px;

	&__title {
		font-size: 16px;
		margin-bottom: 0;
	}

	&__config-list {
		margin-top: 8px;
		width: 55%;

		&__subtitle {
			font-size: 14px;
		}

		&__item {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 2px 0;

			&__key {
				font-size: 12px;
				color: var(--color-text-maxcontrast);
			}
		}
	}

	&__app-list {
		display: flex;
		gap: 32px;

		&__title {
			font-size: 14px;
		}
	}
}
</style>
