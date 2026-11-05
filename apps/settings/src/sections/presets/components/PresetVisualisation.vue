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

const props = withDefaults(defineProps<{
	/** App configs of every preset */
	presets: PresetAppConfigs
	/** The preset whose values are shown */
	selectedPreset?: PresetIds
}>(), {
	selectedPreset: 'NONE',
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
	<div :class="$style.presets">
		<h3 :class="$style.title">
			{{ t('settings', 'Default config values') }}
		</h3>

		<div v-if="appsConfigPresets.length > 0" :class="$style.configList">
			<h4 :class="$style.configListSubtitle">
				{{ t('settings', 'Applications config') }}
			</h4>
			<template v-for="[appId, appConfigPresets] in appsConfigPresets">
				<div
					v-for="configPreset in appConfigPresets"
					:key="appId + '-' + configPreset.entry.key"
					:class="$style.configItem">
					<span>
						<div>{{ configPreset.entry.definition }}</div>
						<code :class="$style.configKey">{{ configPreset.entry.key }}</code>
					</span>
					<span>
						<NcCheckboxRadioSwitch
							v-if="configPreset.entry.type === 'BOOL'"
							:modelValue="configPreset.defaults[selectedPreset] === '1'"
							:disabled="true" />
						<code v-else>{{ configPreset.defaults[selectedPreset] }}</code>
					</span>
				</div>
			</template>
		</div>

		<div v-if="userConfigPresets.length > 0" :class="$style.configList">
			<h4 :class="$style.configListSubtitle">
				{{ t('settings', 'User config') }}
			</h4>
			<template v-for="[appId, userPresets] in userConfigPresets">
				<div
					v-for="configPreset in userPresets"
					:key="appId + '-' + configPreset.entry.key"
					:class="$style.configItem">
					<span>
						<div>{{ configPreset.entry.definition }}</div>
						<code :class="$style.configKey">{{ configPreset.entry.key }}</code>
					</span>
					<span>
						<NcCheckboxRadioSwitch
							v-if="configPreset.entry.type === 'BOOL'"
							:modelValue="configPreset.defaults[selectedPreset] === '1'"
							:disabled="true" />
						<code v-else>{{ configPreset.defaults[selectedPreset] }}</code>
					</span>
				</div>
			</template>
		</div>

		<template v-if="hasApplicationsPreset">
			<h3 :class="$style.title">
				{{ t('settings', 'Bundled applications') }}
			</h3>

			<div :class="$style.appList">
				<div>
					<h4 :class="$style.appListTitle">
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
				<div>
					<h4 :class="$style.appListTitle">
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

<style module>
.presets {
	margin-top: 16px;
}

.title {
	font-size: 16px;
	margin-bottom: 0;
}

.configList {
	margin-top: 8px;
	width: 55%;
}

.configListSubtitle {
	font-size: 14px;
}

.configItem {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 2px 0;
}

.configKey {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}

.appList {
	display: flex;
	gap: 32px;
}

.appListTitle {
	font-size: 14px;
}
</style>
