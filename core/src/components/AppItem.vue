<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<a
		class="app-item"
		:class="{
			'app-item--active': app.active,
			'app-item--outlined': outlined,
		}"
		:href="app.href"
		:target="newTab ? '_blank' : undefined"
		:rel="newTab ? 'noopener noreferrer' : undefined"
		:aria-current="app.active ? 'page' : undefined"
		:tabindex="tabindex"
		:title="app.name"
		role="menuitem">
		<span class="app-item__circle">
			<img
				class="app-item__icon"
				:src="app.icon"
				alt=""
				aria-hidden="true">
			<span
				v-if="app.unread"
				class="app-item__unread"
				aria-hidden="true" />
		</span>
		<span class="app-item__label">
			{{ app.name }}
			<span v-if="app.unread" class="hidden-visually">, {{ unreadLabel }}</span>
		</span>
	</a>
</template>

<script setup lang="ts">
import type { INavigationEntry } from '../types/navigation.d.ts'

import { n } from '@nextcloud/l10n'
import { computed } from 'vue'

const props = withDefaults(defineProps<{
	app: INavigationEntry
	/** When true, the link opens in a new tab with rel="noopener noreferrer". Used for external destinations (e.g. the app store). */
	newTab?: boolean
	/** When true, render the circle as an outline only (used for "More apps" / utility entries). */
	outlined?: boolean
	/**
	 * Roving-tabindex value. AppMenu sets this to 0 on the focused tile and
	 * -1 on all other tiles so only one stop is in the natural Tab order.
	 * Default -1 keeps tiles out of the Tab order when used standalone.
	 */
	tabindex?: number
}>(), {
	tabindex: -1,
})

const unreadLabel = computed(() => {
	if (!props.app.unread) {
		return undefined
	}
	return n(
		'core',
		'{count} notification',
		'{count} notifications',
		props.app.unread,
		{ count: props.app.unread },
	)
})
</script>

<style scoped lang="scss">
.app-item {
	--app-item-circle-size: calc(var(--default-grid-baseline) * 10);
	--app-item-icon-size: 22px;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: var(--default-grid-baseline);
	// Inset so the hover/focus highlight floats around the circle and label
	// rather than sitting flush against the icon at the top edge.
	padding-block: var(--default-grid-baseline);
	border-radius: var(--border-radius-element);
	text-decoration: none;
	color: var(--color-main-text);
	min-width: 0;

	&:hover,
	&:focus-visible {
		background-color: var(--color-background-hover);
	}

	// Inset ring instead of outline + offset: the offset version visibly
	// clips at the popover's rounded edge for items in the first/last row
	// or column. The inset shadow stays inside the highlight rectangle.
	&:focus-visible {
		outline: none;
		box-shadow: inset 0 0 0 2px var(--color-primary-element);
	}

	&__circle {
		box-sizing: border-box;
		position: relative;
		width: var(--app-item-circle-size);
		height: var(--app-item-circle-size);
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
		display: flex;
		align-items: center;
		justify-content: center;
	}

	&__icon {
		width: var(--app-item-icon-size);
		height: var(--app-item-icon-size);
		// Force the icon to white on the colored circle, then apply the
		// same vertical alpha gradient (--header-menu-icon-mask) used in
		// the header so icons read consistently across the design.
		filter: brightness(0) invert(1);
		mask: var(--header-menu-icon-mask);
	}

	&__unread {
		position: absolute;
		top: 0;
		inset-inline-end: 0;
		width: calc(var(--default-grid-baseline) * 3);
		height: calc(var(--default-grid-baseline) * 3);
		border-radius: 50%;
		background-color: var(--color-error);
		border: 2px solid var(--color-main-background);
		box-sizing: content-box;
	}

	&__label {
		font-size: 12px;
		line-height: 1.3;
		text-align: center;
		color: var(--color-main-text);
		word-break: normal;
		overflow-wrap: break-word;
		max-width: 100%;
		letter-spacing: -0.3px;
	}

	&--active &__label {
		font-weight: bold;
	}

	// Outlined variant: no fill or gradient; icon color is unforced.
	&--outlined &__circle {
		background: transparent;
		background-image: none;
		box-shadow: inset 0 0 0 2px var(--color-border-maxcontrast);
	}

	&--outlined &__icon {
		filter: none;
		mask: none;
	}
}
</style>
