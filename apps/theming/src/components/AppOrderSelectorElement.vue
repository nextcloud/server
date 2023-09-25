<template>
	<li class="order-selector-element">
		<svg width="20" height="20" viewBox="0 0 20 20">
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
			<NcButton v-show="!isFirst"
				:aria-label="t('settings', 'Move up')"
				type="tertiary-no-background"
				@click="$emit('move:up')">
				<template #icon>
					<IconChevronUp :size="20" />
				</template>
			</NcButton>
			<div v-show="isFirst" aria-hidden="true" class="order-selector-element__placeholder" />
			<NcButton v-show="!isLast"
				:aria-label="t('settings', 'Move down')"
				type="tertiary-no-background"
				@click="$emit('move:down')">
				<template #icon>
					<IconChevronDown :size="20" />
				</template>
			</NcButton>
			<div v-show="isLast" aria-hidden="true" class="order-selector-element__placeholder" />
		</div>
	</li>
</template>

<script lang="ts">
import { translate as t } from '@nextcloud/l10n'
import { PropType, defineComponent } from 'vue'

import IconChevronDown from 'vue-material-design-icons/ChevronDown.vue'
import IconChevronUp from 'vue-material-design-icons/ChevronUp.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'

interface IApp {
	id: string // app id
	icon: string // path to the icon svg
	label?: string // display name
}

export default defineComponent({
	name: 'AppOrderSelectorElement',
	components: {
		IconChevronDown,
		IconChevronUp,
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
	setup() {
		return {
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
