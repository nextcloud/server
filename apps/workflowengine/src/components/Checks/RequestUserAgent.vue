<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<SelectWithCustomValue
		:comboboxLabel="t('workflowengine', 'User agent')"
		:customLabel="t('workflowengine', 'Custom user agent')"
		:modelValue="modelValue"
		:placeholder="t('workflowengine', 'Select a user agent')"
		:predefinedValues="predefinedTypes"
		@update:modelValue="$emit('update:modelValue', $event)" />
</template>

<script setup lang="ts">
import type { PredefinedValue } from '../../types.ts'

import { mdiCellphone, mdiEmail, mdiMonitor } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import SelectWithCustomValue from './SelectWithCustomValue.vue'

withDefaults(defineProps<{ modelValue?: string }>(), { modelValue: '' })

defineEmits<{ 'update:modelValue': [value: string] }>()

const predefinedTypes: PredefinedValue[] = [
	{ id: 'android', label: t('workflowengine', 'Android client'), iconPath: mdiCellphone },
	{ id: 'ios', label: t('workflowengine', 'iOS client'), iconPath: mdiCellphone },
	{ id: 'desktop', label: t('workflowengine', 'Desktop client'), iconPath: mdiMonitor },
	{ id: 'mail', label: t('workflowengine', 'Thunderbird & Outlook addons'), iconPath: mdiEmail },
]
</script>
