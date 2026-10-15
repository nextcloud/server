<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSelectTags
		v-model="newValue"
		:aria-label-combobox="t('workflowengine', 'Tag')"
		:limit="null"
		:multiple="false"
		@update:modelValue="emitValue()" />
</template>

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import NcSelectTags from '@nextcloud/vue/components/NcSelectTags'
import { useCheckValue } from '../../composables/useCheckValue.ts'

const props = withDefaults(defineProps<{ modelValue?: string }>(), { modelValue: '' })

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const { newValue, emitValue } = useCheckValue<number | null>(
	() => props.modelValue,
	(value) => emit('update:modelValue', value),
	{
		// NcSelectTags works on numeric tag ids, a rule stores the id as a string
		parse: (modelValue) => (modelValue === '' ? null : Number.parseInt(modelValue)),
		format: (value) => String(value || ''),
	},
)
</script>
