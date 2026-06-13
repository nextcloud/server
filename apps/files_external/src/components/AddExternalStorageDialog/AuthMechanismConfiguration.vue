<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import type { IAuthMechanism } from '../../types.ts'

import { t } from '@nextcloud/l10n'
import { NcLoadingIcon } from '@nextcloud/vue'
import { computed, ref, watch, watchEffect } from 'vue'
import ConfigurationEntry from './ConfigurationEntry.vue'
import { ConfigurationFlag, ConfigurationType } from '../../types.ts'

const modelValue = defineModel<Record<string, string | boolean>>({ required: true })

const props = defineProps<{
	authMechanism: IAuthMechanism
}>()

const configuration = computed(() => {
	if (!props.authMechanism.configuration) {
		return undefined
	}

	const entries = Object.entries(props.authMechanism.configuration)
		.filter(([, option]) => !(option.flags & ConfigurationFlag.UserProvided))
	return Object.fromEntries(entries) as typeof props.authMechanism.configuration
})

const customComponent = computed(() => window.OCA.FilesExternal.AuthMechanism!.getHandler(props.authMechanism))
const hasConfiguration = computed(() => {
	if (!configuration.value) {
		return false
	}
	for (const option of Object.values(configuration.value)) {
		if ((option.flags & ConfigurationFlag.Hidden) || (option.flags & ConfigurationFlag.UserProvided)) {
			continue
		}
		// a real config option
		return true
	}
	return false
})

const isLoadingCustomComponent = ref(false)
watchEffect(async () => {
	if (customComponent.value) {
		isLoadingCustomComponent.value = true
		await window.customElements.whenDefined(customComponent.value.tagName)
		isLoadingCustomComponent.value = false
	}
})

watch(configuration, () => {
	for (const key in configuration.value) {
		if (!(key in modelValue.value)) {
			modelValue.value[key] = configuration.value[key]?.type === ConfigurationType.Boolean
				? false
				: ''
		}
	}
})

/**
 * Update the model value when the custom component emits an update event.
 *
 * @param event - The custom event
 */
function onUpdateModelValue(event: CustomEvent) {
	const config = [event.detail].flat()[0]
	modelValue.value = { ...modelValue.value, ...config }
}
</script>

<template>
	<fieldset v-if="hasConfiguration" :class="$style.authMechanismConfiguration">
		<legend>
			{{ t('files_external', 'Authentication') }}
		</legend>

		<template v-if="customComponent">
			<NcLoadingIcon v-if="isLoadingCustomComponent" />
			<!-- eslint-disable vue/attribute-hyphenation,vue/v-on-event-hyphenation -- for custom elements the casing is fixed! -->
			<component
				:is="customComponent.tagName"
				v-else
				:modelValue.prop="modelValue"
				:authMechanism.prop="authMechanism"
				@update:modelValue="onUpdateModelValue" />
		</template>

		<template v-else>
			<ConfigurationEntry
				v-for="(configOption, configKey) in configuration"
				v-show="!(configOption.flags & ConfigurationFlag.Hidden)"
				:key="configOption.value"
				v-model="modelValue[configKey]!"
				:config-key
				:config-option />
		</template>
	</fieldset>
</template>

<style module>
.authMechanismConfiguration {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
}
</style>
