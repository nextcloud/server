<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSelectTags v-model="newValue"
		:multiple="false"
		@input="update" />
</template>

<script>
import NcSelectTags from '@nextcloud/vue/components/NcSelectTags'

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

	data() {
		return {
			newValue: [],
		}
	},
	watch: {
		modelValue() {
			this.updateValue()
		},
	},
	beforeMount() {
		this.updateValue()
	},
	methods: {
		updateValue() {
			if (this.modelValue !== '') {
				this.newValue = parseInt(this.modelValue)
			} else {
				this.newValue = null
			}
		},
		update() {
			this.$emit('update:model-value', this.newValue || '')
		},
	},
}
</script>
