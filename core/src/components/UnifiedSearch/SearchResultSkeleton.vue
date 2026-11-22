<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="search-result-skeleton" aria-hidden="true">
		<div class="search-result-skeleton__bar search-result-skeleton__bar--heading" />
		<div
			v-for="row in rows"
			:key="row"
			class="search-result-skeleton__bar" />
	</div>
</template>

<script setup lang="ts">
/**
 * Results land a whole category at a time, so the block leads with a heading bar.
 *
 * Decoration only: the modal's live region announces the search state.
 */

defineProps<{
	/** Rows below the heading. The caller may overdraw: the panel clips and fades. */
	rows: number
}>()
</script>

<style lang="scss" scoped>
.search-result-skeleton {
	--bar-block-size: calc(2lh + 2 * (2px + var(--default-grid-baseline) + 2px));
	display: flex;
	flex-direction: column;
	gap: calc(3 * var(--default-grid-baseline));

	&__bar {
		flex: none;
		position: relative;
		overflow: hidden;
		block-size: var(--bar-block-size);
		border-radius: var(--border-radius-element);
		background-color: var(--color-background-hover);

		// Full width at one line reads as a divider; an explicit size also mirrors in RTL.
		&--heading {
			block-size: 1lh;
			inline-size: 25%;
		}

		// Transform, not background-position: keeps the animation off the main thread.
		&::after {
			content: '';
			position: absolute;
			inset: 0;
			background-image: linear-gradient(90deg, transparent, var(--color-placeholder-light), transparent);
			transform: translateX(-100%);
			animation: search-result-skeleton-sweep 1.6s linear infinite;
		}

		&:dir(rtl)::after {
			animation-direction: reverse;
		}

		@media (prefers-reduced-motion: reduce) {
			&::after {
				content: none;
			}
		}
	}
}

@keyframes search-result-skeleton-sweep {
	to {
		transform: translateX(100%);
	}
}
</style>
