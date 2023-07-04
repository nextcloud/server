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
			<NcColorPicker :value.sync="localValue"
				:advanced-fields="true"
				@update:value="debounceSave">
				<NcButton :id="id"
					class="field__button"
					type="primary"
					:aria-label="t('theming', 'Select a custom color')"
					data-admin-theming-setting-primary-color-picker>
					{{ value }}
				</NcButton>
			</NcColorPicker>
			<NcButton v-if="value !== defaultValue"
				type="tertiary"
				:aria-label="t('theming', 'Reset to default')"
				data-admin-theming-setting-primary-color-reset
				@click="undo">
				<template #icon>
					<Undo :size="20" />
				</template>
			</NcButton>
		</div>

		<NcNoteCard v-if="errorMessage"
			type="error"
			:show-alert="true">
			<p>{{ errorMessage }}</p>
		</NcNoteCard>
	</div>
</template>

<script>
import { debounce } from 'debounce'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcColorPicker from '@nextcloud/vue/dist/Components/NcColorPicker.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import Undo from 'vue-material-design-icons/UndoVariant.vue'

import TextValueMixin from '../../mixins/admin/TextValueMixin.js'

export default {
	name: 'ColorPickerField',

	components: {
		NcButton,
		NcColorPicker,
		NcNoteCard,
		Undo,
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
		displayName: {
			type: String,
			required: true,
		},
	},

	methods: {
		debounceSave: debounce(async function() {
			await this.save()
		}, 200),
	},
}
</script>

<style lang="scss" scoped>
@import './shared/field.scss';

.field {
	// Override default NcButton styles
	&__button {
		width: 230px !important;
		border-radius: var(--border-radius-large) !important;
		background-color: var(--color-primary-default) !important;

		// emulated hover state because it would not make sense
		// to create a dedicated global variable for the color-primary-default
		&:hover::after {
			background-color: white;
			content: "";
			position: absolute;
			width: 100%;
			height: 100%;
			opacity: .2;
			filter: var(--primary-invert-if-bright);
		}

		// Above the ::after
		&::v-deep * {
			z-index: 1;
		}
	}
}
</style>
