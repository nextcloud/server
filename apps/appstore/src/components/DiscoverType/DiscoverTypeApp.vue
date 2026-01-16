<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<a
		v-if="!app"
		class="app-discover-app app-discover-app__skeleton"
		:href="appStoreLink"
		target="_blank"
		:title="modelValue.appId"
		rel="noopener noreferrer">
		<!-- This is a fallback skeleton -->
		<span class="skeleton-element" />
		<span class="skeleton-element" />
		<span class="skeleton-element" />
		<span class="skeleton-element" />
		<span class="skeleton-element" />
	</a>

	<article v-else class="app-discover-app">
		<AppImage class="app-discover-app__image" :app="app" />
		<div class="app-discover-app__wrapper">
			<h3 class="app-discover-app__name">
				<AppLink :href="`app:${app.id}`">
					{{ app.name }}
				</AppLink>
			</h3>
			<p>{{ app.summary }}</p>
			<AppScore
				v-if="app.ratingNumThresholdReached"
				class="app-discover-app__score"
				:score="app.score" />
		</div>
	</article>
</template>

<script setup lang="ts">
import type { IAppDiscoverApp } from '../../apps-discover.d.ts'

import { computed } from 'vue'
import AppImage from '../AppImage.vue'
import AppLink from '../AppLink.vue'
import AppScore from '../AppScore.vue'
import { useAppsStore } from '../../store/apps.ts'

const props = defineProps<{
	modelValue: IAppDiscoverApp
}>()

const store = useAppsStore()
const app = computed(() => store.getAppById(props.modelValue.appId))

const appStoreLink = computed(() => props.modelValue.appId
	? `https://apps.nextcloud.com/apps/${props.modelValue.appId}`
	: '#')
</script>

<style scoped lang="scss">
.app-discover-app {
	border-radius: var(--border-radius-element);
	display: flex;
	flex-direction: column;
	overflow: hidden;
	width: 100% !important;

	&:hover {
		background: var(--color-background-hover);
	}

	&__image {
		height: 96px;
		width: 100%;
	}

	&__name {
		margin-block: 0.5rem;
		font-size: 1.2rem;
	}

	&__score {
		margin-top: auto;
	}

	&__wrapper {
		display: flex;
		flex-direction: column;
		padding: calc(2 * var(--default-grid-baseline));
		padding-top: 0px;
	}

	&__skeleton {
		display: flex;
		flex-direction: column;
		gap: 8px;

		padding: 30px; // Same as AppItem

		> :first-child {
			height: 50%;
			min-height: 130px;
		}

		> :nth-child(2) {
			width: 50px;
		}

		> :nth-child(5) {
			height: 20px;
			width: 100px;
		}

		> :not(:first-child) {
			border-radius: 4px;
		}
	}
}

.skeleton-element {
	min-height: var(--default-font-size, 15px);

	background: linear-gradient(90deg, var(--color-background-dark), var(--color-background-darker), var(--color-background-dark));
	background-size: 400% 400%;
	animation: gradient 6s ease infinite;
}

@keyframes gradient {
	0% {
		background-position: 0% 50%;
	}
	50% {
		background-position: 100% 50%;
	}
	100% {
		background-position: 0% 50%;
	}
}
</style>
