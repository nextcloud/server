<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<SelectWithCustomValue
		:aria-label="t('workflowengine', 'Request URL')"
		:customLabel="t('workflowengine', 'Custom URL')"
		:customPlaceholder="customPlaceholder"
		:modelValue="modelValue"
		:placeholder="t('workflowengine', 'Select a request URL')"
		:predefinedValues="predefinedTypes"
		@update:modelValue="$emit('update:modelValue', $event)" />
</template>

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import SelectWithCustomValue from './SelectWithCustomValue.vue'

const props = withDefaults(defineProps<{
	modelValue?: string
	operator?: string
}>(), {
	modelValue: '',
	operator: '',
})

defineEmits<{ 'update:modelValue': [value: string] }>()

const predefinedTypes = [
	{
		icon: 'icon-files-dark',
		id: 'webdav',
		label: t('workflowengine', 'Files WebDAV'),
	},
]

const customPlaceholder = computed(() => {
	if (props.operator === 'matches' || props.operator === '!matches') {
		return '/^https\\:\\/\\/localhost\\/index\\.php$/i'
	}
	return 'https://localhost/index.php'
})
</script>
