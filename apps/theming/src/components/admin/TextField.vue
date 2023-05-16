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
		<NcTextField :value.sync="localValue"
			:label="displayName"
			:label-visible="true"
			:placeholder="placeholder"
			:type="type"
			:maxlength="maxlength"
			:spellcheck="false"
			:success="showSuccess"
			:error="Boolean(errorMessage)"
			:helper-text="errorMessage"
			:show-trailing-button="value !== defaultValue"
			trailing-button-icon="undo"
			@trailing-button-click="undo"
			@keydown.enter="save"
			@blur="save" />
	</div>
</template>

<script>
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import TextValueMixin from '../../mixins/admin/TextValueMixin.js'

export default {
	name: 'TextField',

	components: {
		NcTextField,
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
			type: String,
			required: true,
		},
		defaultValue: {
			type: String,
			required: true,
		},
		type: {
			type: String,
			required: true,
		},
		displayName: {
			type: String,
			required: true,
		},
		placeholder: {
			type: String,
			required: true,
		},
		maxlength: {
			type: Number,
			required: true,
		},
	},
}
</script>

<style lang="scss" scoped>
.field {
	max-width: 400px;
}
</style>
