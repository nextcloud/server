<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { nextTick, useTemplateRef } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import IconArrowDown from 'vue-material-design-icons/ArrowDown.vue'
import IconArrowUp from 'vue-material-design-icons/ArrowUp.vue'

export interface IApp {
	id: string // app id
	icon: string // path to the icon svg
	label?: string // display name
	default?: boolean // for app as default app
}

const props = defineProps<{
	/**
	 * Needs to be forwarded to the buttons (as interactive elements)
	 */
	ariaDescribedby?: string
	/**
	 * Needs to be forwarded to the buttons (as interactive elements)
	 */
	ariaDetails?: string

	/**
	 * The app data to display
	 */
	app: IApp

	/**
	 * Is this the first element in the list
	 */
	isFirst?: boolean
	/**
	 * Is this the last element in the list
	 */
	isLast?: boolean
}>()

const emit = defineEmits<{
	'move:up': []
	'move:down': []
	/**
	 * We need this as Sortable.js removes all native focus event listeners
	 */
	'update:focus': []
}>()

defineExpose({ keepFocus })

const buttonUpElement = useTemplateRef('buttonUp')
const buttonDownElement = useTemplateRef('buttonDown')

/**
 * Used to decide if we need to trigger focus() an a button on update
 */
let needsFocus = 0

/**
 * Handle move up, ensure focus is kept on the button
 */
function moveUp() {
	emit('move:up')
	needsFocus = 1 // request focus on buttonUp
}

/**
 * Handle move down, ensure focus is kept on the button
 */
function moveDown() {
	emit('move:down')
	needsFocus = -1 // request focus on buttonDown
}

/**
 * Reset the focus on the last used button.
 * If the button is now visible anymore (because this element is the first/last) then the opposite button is focussed
 *
 * This function is exposed to the "AppOrderSelector" component which triggers this when the list was successfully rerendered
 */
function keepFocus() {
	if (needsFocus !== 0) {
		// focus requested
		if ((needsFocus === 1 || props.isLast) && !props.isFirst) {
			// either requested to btn up and it is not the first, or it was requested to btn down but it is the last
			nextTick(() => buttonUpElement.value!.$el.focus())
		} else {
			nextTick(() => buttonDownElement.value!.$el.focus())
		}
	}
	needsFocus = 0
}
</script>

<template>
	<li
		class="order-selector-element"
		:class="{
			'order-selector-element--disabled': app.default,
		}"
		@focusin="$emit('update:focus')">
		<svg
			width="20"
			height="20"
			viewBox="0 0 20 20"
			role="presentation">
			<image
				preserveAspectRatio="xMinYMin meet"
				x="0"
				y="0"
				width="20"
				height="20"
				:xlink:href="app.icon"
				class="order-selector-element__icon" />
		</svg>

		<div class="order-selector-element__label">
			{{ app.label ?? app.id }}
		</div>

		<div class="order-selector-element__actions">
			<NcButton
				v-show="!isFirst && !app.default"
				ref="buttonUp"
				:aria-label="t('settings', 'Move up')"
				:aria-describedby="ariaDescribedby"
				:aria-details="ariaDetails"
				variant="tertiary-no-background"
				@click="moveUp">
				<template #icon>
					<IconArrowUp :size="20" />
				</template>
			</NcButton>
			<div
				v-show="isFirst || !!app.default"
				aria-hidden="true"
				class="order-selector-element__placeholder" />
			<NcButton
				v-show="!isLast && !app.default"
				ref="buttonDown"
				:aria-label="t('settings', 'Move down')"
				:aria-describedby="ariaDescribedby"
				:aria-details="ariaDetails"
				variant="tertiary-no-background"
				@click="moveDown">
				<template #icon>
					<IconArrowDown :size="20" />
				</template>
			</NcButton>
			<div
				v-show="isLast || !!app.default"
				aria-hidden="true"
				class="order-selector-element__placeholder" />
		</div>
	</li>
</template>

<style lang="scss" scoped>
.order-selector-element {
	// hide default styling
	list-style: none;
	// Align children
	display: flex;
	flex-direction: row;
	align-items: center;
	// Spacing
	gap: 12px;
	padding-inline: 12px;

	&:hover {
		background-color: var(--color-background-hover);
		border-radius: var(--border-radius-large);
	}

	&--disabled {
		border-color: var(--color-text-maxcontrast);
		color: var(--color-text-maxcontrast);

		.order-selector-element__icon {
			opacity: 75%;
		}
	}

	&__actions {
		flex: 0 0;
		display: flex;
		flex-direction: row;
		gap: 6px;
	}

	&__label {
		flex: 1 1;
		text-overflow: ellipsis;
		overflow: hidden;
	}

	&__placeholder {
		height: 44px;
		width: 44px;
	}

	&__icon {
		filter: var(--background-invert-if-bright);
	}
}
</style>
