<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div :class="'theming__preview--' + theme.id" class="theming__preview">
		<div class="theming__preview-image" :style="{ backgroundImage: 'url(' + img + ')' }" @click="onToggle" />
		<div class="theming__preview-description">
			<h3>{{ theme.title }}</h3>
			<p class="theming__preview-explanation">
				{{ theme.description }}
			</p>
			<span v-if="enforced" class="theming__preview-warning" role="note">
				{{ t('theming', 'Theme selection is enforced') }}
			</span>

			<!-- Only show checkbox if we can change themes -->
			<NcCheckboxRadioSwitch v-show="!enforced"
				class="theming__preview-toggle"
				:checked.sync="checked"
				:disabled="enforced"
				:name="name"
				:type="switchType">
				{{ theme.enableLabel }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>

<script>
import { generateFilePath } from '@nextcloud/router'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

export default {
	name: 'ItemPreview',
	components: {
		NcCheckboxRadioSwitch,
	},
	props: {
		enforced: {
			type: Boolean,
			default: false,
		},
		selected: {
			type: Boolean,
			default: false,
		},
		theme: {
			type: Object,
			required: true,
		},
		type: {
			type: String,
			default: '',
		},
		unique: {
			type: Boolean,
			default: false,
		},
	},
	computed: {
		switchType() {
			return this.unique ? 'switch' : 'radio'
		},

		name() {
			return !this.unique ? this.type : null
		},

		img() {
			return generateFilePath('theming', 'img', this.theme.id + '.jpg')
		},

		checked: {
			get() {
				return this.selected
			},
			set(checked) {
				if (this.enforced) {
					return
				}

				console.debug('Changed theme', this.theme.id, checked)

				// If this is a radio, we can only enable
				if (!this.unique) {
					this.$emit('change', { enabled: true, id: this.theme.id })
					return
				}

				// If this is a switch, we can disable the theme
				this.$emit('change', { enabled: checked === true, id: this.theme.id })
			},
		},
	},

	methods: {
		onToggle() {
			if (this.enforced) {
				return
			}

			if (this.switchType === 'radio') {
				this.checked = true
				return
			}

			// Invert state
			this.checked = !this.checked
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
		color: var(--color-warning);
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
