<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="field">
		<label :for="id">{{ displayName }}</label>
		<div class="field__row">
			<NcCheckboxRadioSwitch :id="id"
				type="switch"
				:checked.sync="localValue"
				@update:checked="save">
				{{ label }}
			</NcCheckboxRadioSwitch>
		</div>

		<p class="field__description">
			{{ description }}
		</p>

		<NcNoteCard v-if="errorMessage"
			type="error"
			:show-alert="true">
			<p>{{ errorMessage }}</p>
		</NcNoteCard>
	</div>
</template>

<script>
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

import TextValueMixin from '../../mixins/admin/TextValueMixin.js'

export default {
	name: 'CheckboxField',

	components: {
		NcCheckboxRadioSwitch,
		NcNoteCard,
	},

	mixins: [
		TextValueMixin,
	],

	props: {
		name: {
			type: String,
			required: true,
		},
		value: {
			type: Boolean,
			required: true,
		},
		defaultValue: {
			type: Boolean,
			required: true,
		},
		displayName: {
			type: String,
			required: true,
		},
		label: {
			type: String,
			required: true,
		},
		description: {
			type: String,
			required: true,
		},
	},
}
</script>

<style lang="scss" scoped>
@use './shared/field' as *;

.field {
	&__description {
		color: var(--color-text-maxcontrast);
	}
}
</style>
