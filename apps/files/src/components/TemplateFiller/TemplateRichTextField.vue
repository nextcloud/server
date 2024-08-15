<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="template-field__text">
		<label :for="fieldId">
			{{ fieldLabel }}
		</label>

		<NcTextField :id="fieldId"
			type="text"
			:value.sync="value"
			:label="fieldLabel"
			:label-outside="true"
			:placeholder="field.content"
			@input="input" />
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { NcTextField } from '@nextcloud/vue'

export default defineComponent({
	name: 'TemplateRichTextField',

	components: {
		NcTextField,
	},

	props: {
		field: {
			type: Object,
			default: () => {},
		},
	},

	data() {
		return {
			value: '',
		}
	},

	computed: {
		fieldLabel() {
			const label = this.field.name ?? this.field.alias ?? 'Unknown field'

			return (label.charAt(0).toUpperCase() + label.slice(1))
		},
		fieldId() {
			return 'text-field' + this.field.index
		},
	},

	methods: {
		input() {
			this.$emit('input', {
				index: this.field.index,
				property: 'content',
				value: this.value,
			})
		},
	},
})
</script>

<style lang="scss" scoped>
.template-field__text {
	margin: 20px 0;

	label {
		font-weight: bold;
	}
}
</style>
