<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<ul class="share-skeleton" aria-hidden="true">
		<li v-for="i in count" :key="i" class="share-skeleton__row">
			<span class="share-skeleton__avatar" />
			<span class="share-skeleton__lines">
				<span class="share-skeleton__line share-skeleton__line--title" />
				<span class="share-skeleton__line share-skeleton__line--subtitle" />
			</span>
		</li>
	</ul>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
	/** How many placeholder rows to render. */
	count?: number
}>(), {
	count: 3,
})
</script>

<style lang="scss" scoped>
.share-skeleton {
	&__row {
		display: flex;
		align-items: center;
		gap: 8px;
		min-height: 52px;
	}

	&__avatar {
		flex: 0 0 auto;
		width: 32px;
		height: 32px;
		border-radius: 50%;
	}

	&__lines {
		display: flex;
		flex-direction: column;
		gap: 6px;
		flex: 1 1 auto;
	}

	&__line {
		height: 12px;
		border-radius: 6px;

		&--title {
			width: 40%;
		}

		&--subtitle {
			width: 25%;
		}
	}

	&__avatar,
	&__line {
		background-color: var(--color-background-dark);
		animation: share-skeleton-pulse 1.5s ease-in-out infinite;
	}
}

@keyframes share-skeleton-pulse {
	0%, 100% {
		opacity: 1;
	}
	50% {
		opacity: 0.5;
	}
}

@media (prefers-reduced-motion: reduce) {
	.share-skeleton__avatar,
	.share-skeleton__line {
		animation: none;
	}
}
</style>
