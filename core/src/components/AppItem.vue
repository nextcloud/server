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
			<span
				v-if="app.icon"
				class="app-item__icon"
				:style="iconStyle"
				aria-hidden="true" />
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

// Escaped so a crafted path cannot break out of the url() token.
const iconStyle = computed(() => ({
	'--app-item-icon-url': `url("${props.app.icon.replace(/["\\]/g, '\\$&')}")`,
}))

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
$bevel:
	inset 0 -1px 0 0 color-mix(in srgb, var(--color-primary-element-light), 10% var(--color-primary-element)),
	inset 0 -4px 6px -4px color-mix(in srgb, var(--color-primary-element-light), 16% var(--color-primary-element));

.app-item {
	--app-item-circle-size: calc(var(--default-grid-baseline) * 12);
	// 28px on a 48px circle, so it follows when the circle is resized.
	--app-item-icon-size: calc(var(--app-item-circle-size) * 7 / 12);
	--app-item-bevel: #{$bevel};
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: var(--default-grid-baseline);
	// Keeps the grown circle and the focus ring off the tile's edge.
	padding-block: var(--default-grid-baseline);
	border-radius: var(--border-radius-element);
	text-decoration: none;
	color: var(--color-main-text);
	min-width: 0;

	// Inset ring instead of outline + offset: the offset version visibly
	// clips at the popover's rounded edge for items in the first/last row
	// or column. The inset shadow stays inside the tile's own bounds.
	&:focus-visible {
		outline: none;
		box-shadow: inset 0 0 0 2px var(--color-primary-element);
	}

	&:hover,
	&:focus-visible {
		--app-item-circle-scale: 1.08;
	}

	&:active {
		--app-item-circle-scale: 0.96;
	}

	@media (prefers-color-scheme: dark) {
		--app-item-bevel: none;
	}

	&__circle {
		box-sizing: border-box;
		position: relative;
		display: flex;
		align-items: center;
		justify-content: center;
		width: var(--app-item-circle-size);
		height: var(--app-item-circle-size);
		border-radius: 50%;
		transform: scale(var(--app-item-circle-scale, 1));
		transition: transform var(--animation-quick) ease-out;
		background-color: var(--color-primary-element-light);
		background-image: linear-gradient(
			to bottom,
			color-mix(in srgb, var(--color-primary-element-light), 15% var(--color-main-background)) 0%,
			var(--color-primary-element-light) 100%
		);
		box-shadow: var(--app-item-bevel);

		@media (prefers-reduced-motion: reduce) {
			transition: none;
		}
	}

	&__icon {
		width: var(--app-item-icon-size);
		height: var(--app-item-icon-size);
		// Masked rather than shown: app icons ship a hardcoded fill, so
		// currentColor never applies and a filter could only flip black and white.
		background-color: var(--color-primary-element);
		background-image: linear-gradient(
			to bottom,
			color-mix(in srgb, var(--color-primary-element), 28% var(--color-primary-element-light)) 0%,
			var(--color-primary-element) 100%
		);
		mask: var(--app-item-icon-url) center / contain no-repeat;
	}

	// Masked backgrounds are not force-adjusted the way <img> is.
	@media (forced-colors: active) {
		&__icon {
			background-color: CanvasText;
			background-image: none;
		}
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
		// Needs a matching <html lang> to actually break with a hyphen.
		-webkit-hyphens: auto;
		hyphens: auto;
		word-break: normal;
		overflow-wrap: break-word;
		max-width: 100%;
		letter-spacing: -0.3px;
	}

	&--active &__label {
		font-weight: bold;
	}

	// Outlined variant: no fill or gradient.
	&--outlined &__circle {
		background: transparent;
		background-image: none;
		box-shadow: inset 0 0 0 2px var(--color-border-maxcontrast);
	}

	&--outlined &__icon {
		background-color: var(--color-main-text);
		background-image: none;
	}
}

// An explicit theme choice must beat the media query above, which only sees the OS.
:global([data-themes*=dark] .app-item) {
	--app-item-bevel: none;
}

:global([data-themes*=light] .app-item) {
	--app-item-bevel: #{$bevel};
}
</style>
