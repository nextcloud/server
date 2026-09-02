<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import IconPlus from 'vue-material-design-icons/Plus.vue'

const props = defineProps<{
	/** URL of the icon, painted as-is so the colors of the action are kept. */
	icon?: string
	/** Color of the indicator; without one no indicator is rendered. */
	color?: string
}>()

// Escaped so a crafted path cannot break out of the url() token.
const iconStyle = computed(() => ({
	'--app-action-icon-url': `url("${(props.icon ?? '').replace(/["\\]/g, '\\$&')}")`,
}))

const indicatorStyle = computed(() => ({
	'--app-action-icon-indicator-color': props.color,
}))
</script>

<template>
	<span class="app-action-icon">
		<!-- @slot Icon to render instead of the `icon` URL, e.g. an inline icon component. -->
		<slot>
			<span
				v-if="icon"
				class="app-action-icon__img"
				:style="iconStyle"
				aria-hidden="true" />
		</slot>
		<span
			v-if="color"
			class="app-action-icon__indicator"
			:style="indicatorStyle"
			aria-hidden="true">
			<IconPlus />
		</span>
	</span>
</template>

<style scoped lang="scss">
.app-action-icon {
	// Consumers size the icon through --app-action-icon-size, see AppMenuItem.
	--app-action-icon-glyph-size: calc(var(--app-action-icon-size, calc(var(--default-grid-baseline) * 12)) * 0.8);
	position: relative;
	box-sizing: border-box;
	display: flex;
	align-items: center;
	justify-content: center;
	width: var(--app-action-icon-size, calc(var(--default-grid-baseline) * 12));
	height: var(--app-action-icon-size, calc(var(--default-grid-baseline) * 12));
	transform: scale(var(--app-icon-scale, 1));
	transition: transform var(--animation-quick) ease-out;

	@media (prefers-reduced-motion: reduce) {
		transition: none;
	}

	&__img {
		width: var(--app-action-icon-glyph-size);
		height: var(--app-action-icon-glyph-size);
		background: var(--app-action-icon-url) center / contain no-repeat;
	}

	// Slotted icon components ship their own SVG dimensions.
	:deep(.material-design-icon) {
		width: var(--app-action-icon-glyph-size);
		height: var(--app-action-icon-glyph-size);

		svg {
			width: 100%;
			height: 100%;
		}
	}

	&__indicator {
		--app-action-icon-indicator-size: max(12px, calc(var(--app-action-icon-size, calc(var(--default-grid-baseline) * 12)) * 0.36));
		position: absolute;
		inset-block-end: 0;
		inset-inline-end: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		width: var(--app-action-icon-indicator-size);
		height: var(--app-action-icon-indicator-size);
		border-radius: 50%;
		background-color: var(--app-action-icon-indicator-color);
		// Separates the indicator from the icon underneath.
		border: 2px solid var(--color-main-background);
		box-sizing: content-box;
		// The indicator color is app-provided and saturated, so the glyph on top
		// of it cannot follow the theme text color.
		color: #fff;

		:deep(svg) {
			width: 100%;
			height: 100%;
		}
	}
}
</style>
