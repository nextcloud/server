<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<a
		class="app-item"
		:class="{
			'app-item--active': app.active,
		}"
		:href="app.href"
		:target="newTab ? '_blank' : undefined"
		:rel="newTab ? 'noopener noreferrer' : undefined"
		:aria-current="app.active ? 'page' : undefined"
		:tabindex="tabindex"
		:title="app.name"
		role="menuitem">
		<AppIcon :icon="app.icon" :outlined="outlined">
			<span
				v-if="app.unread"
				class="app-item__unread"
				aria-hidden="true" />
		</AppIcon>
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
import AppIcon from './AppIcon.vue'

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
		--app-icon-scale: 1.08;
	}

	&:active {
		--app-icon-scale: 0.96;
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
}
</style>
