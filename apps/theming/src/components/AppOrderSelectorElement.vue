<template>
	<li :data-cy-app-order-element="app.id"
		:class="{
			'order-selector-element': true,
			'order-selector-element--disabled': app.default
		}">
		<svg width="20"
			height="20"
			viewBox="0 0 20 20"
			role="presentation">
			<image preserveAspectRatio="xMinYMin meet"
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
			<NcButton v-show="!isFirst && !app.default"
				ref="buttonUp"
				:aria-label="t('settings', 'Move up')"
				data-cy-app-order-button="up"
				type="tertiary-no-background"
				@click="moveUp">
				<template #icon>
					<IconArrowUp :size="20" />
				</template>
			</NcButton>
			<div v-show="isFirst || !!app.default" aria-hidden="true" class="order-selector-element__placeholder" />
			<NcButton v-show="!isLast && !app.default"
				ref="buttonDown"
				:aria-label="t('settings', 'Move down')"
				data-cy-app-order-button="down"
				type="tertiary-no-background"
				@click="moveDown">
				<template #icon>
					<IconArrowDown :size="20" />
				</template>
			</NcButton>
			<div v-show="isLast || !!app.default" aria-hidden="true" class="order-selector-element__placeholder" />
		</div>
	</li>
</template>

<script lang="ts">
import type { PropType } from 'vue'

import { translate as t } from '@nextcloud/l10n'
import { defineComponent, nextTick, onUpdated, ref } from 'vue'

import IconArrowDown from 'vue-material-design-icons/ArrowDown.vue'
import IconArrowUp from 'vue-material-design-icons/ArrowUp.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'

interface IApp {
	id: string // app id
	icon: string // path to the icon svg
	label?: string // display name
	default?: boolean // for app as default app
}

export default defineComponent({
	name: 'AppOrderSelectorElement',
	components: {
		IconArrowDown,
		IconArrowUp,
		NcButton,
	},
	props: {
		app: {
			type: Object as PropType<IApp>,
			required: true,
		},
		isFirst: {
			type: Boolean,
			default: false,
		},
		isLast: {
			type: Boolean,
			default: false,
		},
	},
	emits: {
		'move:up': () => true,
		'move:down': () => true,
	},
	setup(props, { emit }) {
		const buttonUp = ref()
		const buttonDown = ref()

		/**
		 * Used to decide if we need to trigger focus() an a button on update
		 */
		let needsFocus = 0

		/**
		 * Handle move up, ensure focus is kept on the button
		 */
		const moveUp = () => {
			emit('move:up')
			needsFocus = 1 // request focus on buttonUp
		}

		/**
		 * Handle move down, ensure focus is kept on the button
		 */
		 const moveDown = () => {
			emit('move:down')
			needsFocus = -1 // request focus on buttonDown
		}

		/**
		 * onUpdated hook is used to reset the focus on the last used button (if requested)
		 * If the button is now visible anymore (because this element is the first/last) then the opposite button is focussed
		 */
		onUpdated(() => {
			if (needsFocus !== 0) {
				// focus requested
				if ((needsFocus === 1 || props.isLast) && !props.isFirst) {
					// either requested to btn up and it is not the first, or it was requested to btn down but it is the last
					nextTick(() => buttonUp.value.$el.focus())
				} else {
					nextTick(() => buttonDown.value.$el.focus())
				}
			}
			needsFocus = 0
		})

		return {
			buttonUp,
			buttonDown,

			moveUp,
			moveDown,

			t,
		}
	},
})
</script>

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
