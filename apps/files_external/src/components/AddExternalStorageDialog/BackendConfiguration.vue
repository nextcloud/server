<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import type { IConfigurationOption } from '../../types.ts'

import { t } from '@nextcloud/l10n'
import { watch } from 'vue'
import ConfigurationEntry from './ConfigurationEntry.vue'
import { ConfigurationFlag, ConfigurationType } from '../../types.ts'

const modelValue = defineModel<Record<string, string | boolean>>({ required: true })

const props = defineProps<{
	configuration: Record<string, IConfigurationOption>
}>()

watch(() => props.configuration, () => {
	for (const key in props.configuration) {
		if (!(key in modelValue.value)) {
			modelValue.value[key] = props.configuration[key]?.type === ConfigurationType.Boolean
				? false
				: ''
		}
	}
})
</script>

<template>
	<fieldset :class="$style.backendConfiguration">
		<legend>
			{{ t('files_external', 'Storage configuration') }}
		</legend>

		<ConfigurationEntry
			v-for="configOption, configKey in configuration"
			v-show="!(configOption.flags & ConfigurationFlag.Hidden)"
			:key="configOption.value"
			v-model="modelValue[configKey]!"
			:config-key="configKey"
			:config-option="configOption" />
	</fieldset>
</template>

<style module>
.backendConfiguration {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
}
</style>
