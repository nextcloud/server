<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<template>
	<li ref="containerElement"
		class="app-menu-entry"
		:class="{
			'app-menu-entry--active': app.active,
			'app-menu-entry--truncated': needsSpace,
		}">
		<a class="app-menu-entry__link"
			:href="app.href"
			:title="app.name"
			:aria-current="app.active ? 'page' : false"
			:target="app.target ? '_blank' : undefined"
			:rel="app.target ? 'noopener noreferrer' : undefined">
			<AppMenuIcon class="app-menu-entry__icon" :app="app" />
			<span ref="labelElement" class="app-menu-entry__label">
				{{ app.name }}
			</span>
		</a>
	</li>
</template>

<script setup lang="ts">
import type { INavigationEntry } from '../types/navigation'
import { onMounted, ref, watch } from 'vue'
import AppMenuIcon from './AppMenuIcon.vue'

const props = defineProps<{
	app: INavigationEntry
}>()

const containerElement = ref<HTMLLIElement>()
const labelElement = ref<HTMLSpanElement>()
const needsSpace = ref(false)

/** Update the space requirements of the app label */
function calculateSize() {
	const maxWidth = containerElement.value!.clientWidth
	// Also keep the 0.5px letter spacing in mind
	needsSpace.value = (maxWidth - props.app.name.length * 0.5) < (labelElement.value!.scrollWidth)
}
// Update size on mounted and when the app name changes
onMounted(calculateSize)
watch(() => props.app.name, calculateSize)
</script>

<style scoped lang="scss">
.app-menu-entry {
	--app-menu-entry-font-size: 12px;
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
		font-size: var(--app-menu-entry-font-size);
		// this is shown directly on the background
		color: var(--color-background-plain-text);
		text-align: center;
		bottom: 0;
		inset-inline-start: 50%;
		top: 50%;
		display: block;
		transform: translateX(-50%);
		max-width: 100%;
		text-overflow: ellipsis;
		overflow: hidden;
		letter-spacing: -0.5px;
	}
	body[dir=rtl] &__label {
		transform: translateX(50%) !important;
	}

	&__icon {
		font-size: var(--app-menu-entry-font-size);
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
			inset-inline-start: 50%;
			bottom: 8px;
			display: block;
			transition: all var(--animation-quick) ease-in-out;
			opacity: 1;
		}
		body[dir=rtl] &::before {
			transform: translateX(50%) !important;
		}
	}

	&__icon,
	&__label {
		transition: all var(--animation-quick) ease-in-out;
	}

	// Make the hovered entry bold to see that it is hovered
	&:hover .app-menu-entry__label,
	&:focus-within .app-menu-entry__label {
		font-weight: bold;
	}

	// Adjust the width when an entry is focussed
	// The focussed / hovered entry should grow, while both neighbors need to shrink
	&--truncated:hover,
	&--truncated:focus-within {
		.app-menu-entry__label {
			max-width: calc(var(--header-height) + var(--app-menu-entry-growth));
		}

		// The next entry needs to shrink half the growth
		+ .app-menu-entry {
			.app-menu-entry__label {
				font-weight: normal;
				max-width: calc(var(--header-height) - var(--app-menu-entry-growth));
			}
		}
	}

	// The previous entry needs to shrink half the growth
	&:has(+ .app-menu-entry--truncated:hover),
	&:has(+ .app-menu-entry--truncated:focus-within) {
		.app-menu-entry__label {
			font-weight: normal;
			max-width: calc(var(--header-height) - var(--app-menu-entry-growth));
		}
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
		margin-block-end: 1lh;
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
