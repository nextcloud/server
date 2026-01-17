<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import type { IConfigurationOption } from '../../types.ts'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { ConfigurationFlag, ConfigurationType } from '../../types.ts'

const value = defineModel<string | boolean>('modelValue', { default: '' })

defineProps<{
	configKey: string
	configOption: IConfigurationOption
}>()
</script>

<template>
	<component
		:is="configOption.type === ConfigurationType.Password ? NcPasswordField : NcTextField"
		v-if="configOption.type !== ConfigurationType.Boolean"
		v-model="value"
		:name="configKey"
		:required="!(configOption.flags & ConfigurationFlag.Optional)"
		:label="configOption.value"
		:title="configOption.tooltip" />
	<NcCheckboxRadioSwitch
		v-else
		v-model="value"
		type="switch"
		:title="configOption.tooltip">
		{{ configOption.value }}
	</NcCheckboxRadioSwitch>
</template>
