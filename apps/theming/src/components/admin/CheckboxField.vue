<!--
  - @copyright 2022 Christopher Ng <chrng8@gmail.com>
  -
  - @author Christopher Ng <chrng8@gmail.com>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
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
@import './shared/field.scss';

.field {
	&__description {
		color: var(--color-text-maxcontrast);
	}
}
</style>
