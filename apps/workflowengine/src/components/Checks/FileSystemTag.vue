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
import NcSelectTags from '@nextcloud/vue/dist/Components/NcSelectTags.js'

export default {
	name: 'FileSystemTag',
	components: {
		NcSelectTags,
	},
	props: {
		value: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			newValue: [],
		}
	},
	watch: {
		value() {
			this.updateValue()
		},
	},
	beforeMount() {
		this.updateValue()
	},
	methods: {
		updateValue() {
			if (this.value !== '') {
				this.newValue = parseInt(this.value)
			} else {
				this.newValue = null
			}
		},
		update() {
			this.$emit('input', this.newValue || '')
		},
	},
}
</script>
