<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<span
		class="app-icon"
		:class="{ 'app-icon--outlined': outlined }">
		<img
			class="app-icon__img"
			:src="icon"
			alt=""
			aria-hidden="true">
		<!-- @slot Overlay positioned over the circle, e.g. an unread badge. -->
		<slot />
	</span>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
	/** URL of the app icon (painted bright on the coloured circle). */
	icon: string
	/** Render the circle as an outline only (no fill or gradient). */
	outlined?: boolean
}>(), {
	outlined: false,
})
</script>

<style scoped lang="scss">
.app-icon {
	--app-icon-circle-size: calc(var(--default-grid-baseline) * 10);
	--app-icon-icon-size: 22px;
	box-sizing: border-box;
	position: relative;
	display: flex;
	align-items: center;
	justify-content: center;
	width: var(--app-icon-circle-size);
	height: var(--app-icon-circle-size);
	border-radius: 50%;
	background-color: var(--color-primary-element);
	background-image: linear-gradient(
		to bottom,
		rgba(255, 255, 255, 0.18) 0%,
		rgba(255, 255, 255, 0) 45%,
		rgba(0, 0, 0, 0.15) 100%
	);
	box-shadow:
		inset 0 1px 0 0 rgba(255, 255, 255, 0.25),
		inset 0 -1px 0 0 rgba(0, 0, 0, 0.2),
		0 2px 4px rgba(0, 0, 0, 0.15);

	&__img {
		width: var(--app-icon-icon-size);
		height: var(--app-icon-icon-size);
		// App icons are bright; flip to dark when the circle background is bright (e.g. white in dark mode).
		filter: var(--primary-invert-if-bright);
		mask: var(--header-menu-icon-mask);
	}

	&--outlined {
		background: transparent;
		background-image: none;
		box-shadow: inset 0 0 0 2px var(--color-border-maxcontrast);
	}

	&--outlined &__img {
		filter: var(--background-invert-if-dark);
		mask: none;
	}
}
</style>
