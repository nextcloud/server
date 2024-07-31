<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<template>
	<li class="app-menu-entry"
		:class="{
			'app-menu-entry--active': app.active,
		}">
		<a class="app-menu-entry__link"
			:href="app.href"
			:title="app.name"
			:aria-current="app.active ? 'page' : false"
			:target="app.target ? '_blank' : undefined"
			:rel="app.target ? 'noopener noreferrer' : undefined">
			<AppMenuIcon class="app-menu-entry__icon" :app="app" />
			<span class="app-menu-entry__label">
				{{ app.name }}
			</span>
		</a>
	</li>
</template>

<script setup lang="ts">
import type { INavigationEntry } from '../types/navigation'
import AppMenuIcon from './AppMenuIcon.vue'

defineProps<{
	app: INavigationEntry
}>()
</script>

<style scoped lang="scss">
.app-menu-entry {
	width: var(--header-height);
	height: var(--header-height);
	position: relative;

	&__link {
		position: relative;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		// Set color as this is shown directly on the background
		color: var(--color-background-plain-text);
		// Make space for focus-visible outline
		width: calc(100% - 4px);
		height: calc(100% - 4px);
		margin: 2px;
	}

	&__label {
		opacity: 0;
		position: absolute;
		font-size: 12px;
		line-height: 1.25;
		// this is shown directly on the background
		color: var(--color-background-plain-text);
		text-align: center;
		bottom: 0;
		left: 50%;
		top: 50%;
		display: block;
		min-width: 100%;
		transform: translateX(-50%);
		width: 100%;
		text-overflow: ellipsis;
		overflow: hidden;
		letter-spacing: -0.5px;
	}

	&--active {
		// When hover or focus, show the label and make it bolder than the other entries
		.app-menu-entry__label {
			font-weight: bolder;
		}

		// When active show a line below the entry as an "active" indicator
		&::before {
			content: " ";
			position: absolute;
			pointer-events: none;
			border-bottom-color: var(--color-main-background);
			transform: translateX(-50%);
			width: 10px;
			height: 5px;
			border-radius: 3px;
			background-color: var(--color-background-plain-text);
			left: 50%;
			bottom: 8px;
			display: block;
			transition: all 0.1s ease-in-out;
			opacity: 1;
		}
	}

	&__icon,
	&__label {
		transition: all 0.1s ease-in-out;
	}

	// Make the hovered entry bold to see that it is hovered
	&:hover .app-menu-entry__label,
	&:focus-within .app-menu-entry__label {
		font-weight: bold;
	}
}
</style>

<style lang="scss">
// Showing the label
.app-menu-entry:hover,
.app-menu-entry:focus-within,
.app-menu__list:hover,
.app-menu__list:focus-within {
	// Move icon up so that the name does not overflow the icon
	.app-menu-entry__icon {
		margin-block-end: calc(1.5 * 12px); // font size of label * line height
	}

	// Make the label visible
	.app-menu-entry__label {
		opacity: 1;
	}

	// Hide indicator when the text is shown
	.app-menu-entry--active::before {
		opacity: 0;
	}

	.app-menu-icon__unread {
		opacity: 0;
	}
}
</style>
