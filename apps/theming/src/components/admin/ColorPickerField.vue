<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
					data-admin-theming-setting-color-picker>
					<template #icon>
						<NcLoadingIcon v-if="loading"
							:appearance="calculatedTextColor === '#ffffff' ? 'light' : 'dark'"
							:size="20" />
						<Palette v-else :size="20" />
					</template>
					{{ value }}
				</NcButton>
			</NcColorPicker>
			<div class="field__color-preview" data-admin-theming-setting-color />
			<NcButton v-if="value !== defaultValue"
				type="tertiary"
				:aria-label="t('theming', 'Reset to default')"
				data-admin-theming-setting-color-reset
				@click="undo">
				<template #icon>
					<Undo :size="20" />
				</template>
			</NcButton>
		</div>
		<div v-if="description" class="description">
			{{ description }}
		</div>

		<NcNoteCard v-if="errorMessage"
			type="error"
			:show-alert="true">
			<p>{{ errorMessage }}</p>
		</NcNoteCard>
	</div>
</template>

<script>
import { colord } from 'colord'
import debounce from 'debounce'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcColorPicker from '@nextcloud/vue/dist/Components/NcColorPicker.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import Undo from 'vue-material-design-icons/UndoVariant.vue'
import Palette from 'vue-material-design-icons/Palette.vue'

import TextValueMixin from '../../mixins/admin/TextValueMixin.js'

export default {
	name: 'ColorPickerField',

	components: {
		NcButton,
		NcColorPicker,
		NcLoadingIcon,
		NcNoteCard,
		Undo,
		Palette,
	},

	mixins: [
		TextValueMixin,
	],

	props: {
		name: {
			type: String,
			required: true,
		},
		description: {
			type: String,
			default: '',
		},
		value: {
			type: String,
			required: true,
		},
		textColor: {
			type: String,
			default: null,
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

	emits: ['update:theming'],

	data() {
		return {
			loading: false,
		}
	},

	computed: {
		calculatedTextColor() {
			const color = colord(this.value)
			return color.isLight() ? '#000000' : '#ffffff'
		},
		usedTextColor() {
			if (this.textColor) {
				return this.textColor
			}
			return this.calculatedTextColor
		},
	},

	methods: {
		debounceSave: debounce(async function() {
			this.loading = true
			await this.save()
			this.$emit('update:theming')
			this.loading = false
		}, 200),
	},
}
</script>

<style lang="scss" scoped>
@use './shared/field' as *;

.description {
	color: var(--color-text-maxcontrast);
}

.field {
	&__button {
		background-color: v-bind('value') !important;
		color: v-bind('usedTextColor') !important;
	}

	&__color-preview {
		width: var(--default-clickable-area);
		border-radius: var(--border-radius-large);
		background-color: v-bind('value');
	}
}
</style>
