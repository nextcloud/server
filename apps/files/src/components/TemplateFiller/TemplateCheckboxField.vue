<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="template-field__checkbox">
		<NcCheckboxRadioSwitch :id="fieldId"
			:checked.sync="value"
			type="switch"
			@update:checked="input">
			{{ fieldLabel }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'

export default defineComponent({
	name: 'TemplateCheckboxField',

	components: {
		NcCheckboxRadioSwitch,
	},

	props: {
		field: {
			type: Object,
			default: () => {},
		},
	},

	data() {
		return {
			value: this.field.checked ?? false,
		}
	},

	computed: {
		fieldLabel() {
			const label = this.field.name ?? this.field.alias ?? 'Unknown field'

			return label.charAt(0).toUpperCase() + label.slice(1)
		},
		fieldId() {
			return 'checkbox-field' + this.field.index
		},
	},

	methods: {
		input() {
			this.$emit('input', {
				index: this.field.index,
				property: 'checked',
				value: this.value,
			})
		},
	},
})
</script>

<style lang="scss" scoped>
.template-field__checkbox {
  margin: 20px 0;
}
</style>
