<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<li :class="'theming__preview--' + theme.id" class="theming__preview">
		<img
			alt=""
			class="theming__preview-image"
			:src="imageUrl"
			@click="onToggle">
		<div class="theming__preview-description">
			<h3>{{ theme.title }}</h3>
			<p class="theming__preview-explanation">
				{{ theme.description }}
			</p>
			<span v-if="enforced" class="theming__preview-warning" role="note">
				{{ t('theming', 'Theme selection is enforced') }}
			</span>

			<!-- Only show checkbox if we can change themes -->
			<NcCheckboxRadioSwitch
				v-show="!enforced"
				class="theming__preview-toggle"
				:model-value="checkboxModelValue"
				:disabled="enforced"
				:name="isSwitch ? undefined : name"
				:type="isSwitch ? 'switch' : 'radio'"
				:value="isSwitch ? undefined : theme.id"
				@update:model-value="onToggle">
				{{ theme.enableLabel }}
			</NcCheckboxRadioSwitch>
		</div>
	</li>
</template>

<script>
import { generateFilePath } from '@nextcloud/router'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

export default {
	name: 'ThemeListItem',
	components: {
		NcCheckboxRadioSwitch,
	},

	props: {
		/**
		 * If the theme is enforced by the admin
		 */
		enforced: {
			type: Boolean,
			default: false,
		},

		/**
		 * The theme object
		 */
		theme: {
			type: Object,
			required: true,
		},

		/**
		 * The name for the radio input to group them.
		 */
		name: {
			type: String,
			default: '',
		},

		/**
		 * Whether to use a switch instead of a radio
		 */
		isSwitch: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['selected'],

	computed: {
		checkboxModelValue() {
			if (this.isSwitch) {
				return this.enforced || this.theme.enabled
			}
			if (this.enforced || this.theme.enabled) {
				return this.theme.id
			}
			return ''
		},

		imageUrl() {
			return generateFilePath('theming', 'img', this.theme.id + '.jpg')
		},
	},

	methods: {
		onToggle(value) {
			if (this.enforced) {
				return
			}
			if (this.isSwitch || (value === this.theme.id) !== this.theme.enabled) {
				this.$emit('selected')
			}
		},
	},
}
</script>

<style lang="scss" scoped>
@use 'sass:math';

.theming__preview {
	// We make previews on 16/10 screens
	--ratio: 16;
	position: relative;
	display: flex;
	justify-content: flex-start;

	&,
	* {
		user-select: none;
	}

	&-image {
		flex-basis: calc(16px * var(--ratio));
		flex-shrink: 0;
		height: calc(10px * var(--ratio));
		margin-inline-end: var(--gap);
		cursor: pointer;
		border-radius: var(--border-radius);
		background-repeat: no-repeat;
		background-position: top left;
		background-size: cover;
	}

	&-explanation {
		margin-bottom: 10px;
	}

	&-description {
		display: flex;
		flex-direction: column;

		h3 {
			font-weight: bold;
			margin-bottom: 0;
		}

		label {
			padding: 12px 0;
		}
	}

	&-warning {
		background-color: var(--color-warning);
		color: var(--color-warning-text);
	}
}

@media (max-width: math.div(1024px, 1.5)) {
	.theming__preview {
		flex-direction: column;

		&-image {
			margin: 0;
		}
	}
}

</style>
