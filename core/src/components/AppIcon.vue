<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<span
		class="app-icon"
		:class="{ 'app-icon--outlined': outlined }">
		<span
			v-if="icon"
			class="app-icon__img"
			:style="iconStyle"
			aria-hidden="true" />
		<!-- @slot Overlay positioned over the circle, e.g. an unread badge. -->
		<slot />
	</span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
	/** URL of the app icon, used as a CSS mask. */
	icon: string
	/** Render the circle as an outline only (no fill or gradient). */
	outlined?: boolean
}>(), {
	outlined: false,
})

// Escaped so a crafted path cannot break out of the url() token.
const iconStyle = computed(() => ({
	'--app-icon-url': `url("${props.icon.replace(/["\\]/g, '\\$&')}")`,
}))
</script>

<style scoped lang="scss">
$bevel:
	inset 0 -1px 0 0 color-mix(in srgb, var(--color-primary-element-light), 10% var(--color-primary-element)),
	inset 0 -4px 6px -4px color-mix(in srgb, var(--color-primary-element-light), 16% var(--color-primary-element));

.app-icon {
	--app-icon-circle-size: calc(var(--default-grid-baseline) * 12);
	// 28px on a 48px circle, so it follows when consumers resize the circle.
	--app-icon-icon-size: calc(var(--app-icon-circle-size) * 7 / 12);
	--app-icon-bevel: #{$bevel};
	box-sizing: border-box;
	position: relative;
	display: flex;
	align-items: center;
	justify-content: center;
	width: var(--app-icon-circle-size);
	height: var(--app-icon-circle-size);
	border-radius: 50%;
	transform: scale(var(--app-icon-scale, 1));
	transition: transform var(--animation-quick) ease-out;
	background-color: var(--color-primary-element-light);
	background-image: linear-gradient(
		to bottom,
		color-mix(in srgb, var(--color-primary-element-light), 15% var(--color-main-background)) 0%,
		var(--color-primary-element-light) 100%
	);
	box-shadow: var(--app-icon-bevel);

	@media (prefers-color-scheme: dark) {
		--app-icon-bevel: none;
	}

	@media (prefers-reduced-motion: reduce) {
		transition: none;
	}

	&__img {
		width: var(--app-icon-icon-size);
		height: var(--app-icon-icon-size);
		// Masked rather than shown: app icons ship a hardcoded fill, so
		// currentColor never applies and a filter could only flip black and white.
		background-color: var(--color-primary-element);
		background-image: linear-gradient(
			to bottom,
			color-mix(in srgb, var(--color-primary-element), 28% var(--color-primary-element-light)) 0%,
			var(--color-primary-element) 100%
		);
		mask: var(--app-icon-url) center / contain no-repeat;
	}

	// Masked backgrounds are not force-adjusted the way <img> is.
	@media (forced-colors: active) {
		&__img {
			background-color: CanvasText;
			background-image: none;
		}
	}

	&--outlined {
		background: transparent;
		background-image: none;
		box-shadow: inset 0 0 0 2px var(--color-border-maxcontrast);
	}

	&--outlined &__img {
		background-color: var(--color-main-text);
		background-image: none;
	}
}

// An explicit theme choice must beat the media query above, which only sees the OS.
:global([data-themes*=dark] .app-icon) {
	--app-icon-bevel: none;
}

:global([data-themes*=light] .app-icon) {
	--app-icon-bevel: #{$bevel};
}
</style>
