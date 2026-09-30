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
		@input="update" />
</template>

<script>
import NcSelectTags from '@nextcloud/vue/components/NcSelectTags'
import { useCheckValue } from '../../composables/useCheckValue.ts'

export default {
	name: 'FileSystemTag',
	components: {
		NcSelectTags,
	},

	props: {
		modelValue: {
			type: String,
			default: '',
		},
	},

	emits: ['update:model-value'],

	setup(props, { emit }) {
		return useCheckValue(
			() => props.modelValue,
			(value) => emit('update:model-value', value),
			{
				// NcSelectTags works on numeric tag ids, the rule stores a string
				parse: (modelValue) => (modelValue === '' ? null : parseInt(modelValue)),
				format: (value) => String(value || ''),
			},
		)
	},

	methods: {
		update() {
			this.emitValue()
		},
	},
}
</script>
