<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<SelectWithCustomValue
		:aria-label="t('workflowengine', 'User agent')"
		:customLabel="t('workflowengine', 'Custom user agent')"
		:modelValue="modelValue"
		:placeholder="t('workflowengine', 'Select a user agent')"
		:predefinedValues="predefinedTypes"
		@update:modelValue="$emit('update:modelValue', $event)" />
</template>

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import SelectWithCustomValue from './SelectWithCustomValue.vue'

withDefaults(defineProps<{ modelValue?: string }>(), { modelValue: '' })

defineEmits<{ 'update:modelValue': [value: string] }>()

const predefinedTypes = [
	{ id: 'android', label: t('workflowengine', 'Android client'), icon: 'icon-phone' },
	{ id: 'ios', label: t('workflowengine', 'iOS client'), icon: 'icon-phone' },
	{ id: 'desktop', label: t('workflowengine', 'Desktop client'), icon: 'icon-desktop' },
	{ id: 'mail', label: t('workflowengine', 'Thunderbird & Outlook addons'), icon: 'icon-mail' },
]
</script>
