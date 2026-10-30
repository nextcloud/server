<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { generateFilePath } from '@nextcloud/router'
import { computed } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

export interface ITheme {
	id: string
	name: string
	title: string
	description: string
	enableLabel: string
	type: number // 1 = theme, 2 = font
	enabled: boolean
}

const isSelected = defineModel<boolean>({ required: true })

const props = defineProps<{
	/** If this theme is enforced */
	enforced?: boolean
	/**
	 * The loading state
	 */
	loading?: boolean
	/** The theme */
	theme: ITheme
	/**
	 * The type of the switch.
	 */
	type: 'checkbox' | 'radio' | 'switch'
	/**
	 * When multiple themes are allowed, this is the name of the input group.
	 */
	name?: string
}>()

const imageUrl = computed(() => generateFilePath('theming', 'img', props.theme.id + '.jpg'))
const checkboxValue = computed({
	get() {
		if (props.type === 'switch') {
			return isSelected.value
		} else if (props.type === 'radio') {
			return isSelected.value ? props.theme.id : false
		} else {
			return isSelected.value ? [props.theme.id] : []
		}
	},
	set() {
		isSelected.value = !isSelected.value
	},
})
</script>

<template>
	<li :class="'theming__preview--' + theme.id" class="theming__preview">
		<img
			alt=""
			class="theming__preview-image"
			:src="imageUrl"
			@click="isSelected = !isSelected">
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
				v-model="checkboxValue"
				class="theming__preview-toggle"
				:disabled="enforced"
				:loading
				:name="type !== 'switch' ? name : undefined"
				:type
				:value="type !== 'switch' ? theme.id : undefined">
				{{ theme.enableLabel }}
			</NcCheckboxRadioSwitch>
		</div>
	</li>
</template>

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
