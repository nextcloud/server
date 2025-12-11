<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<AppItem
		v-if="app"
		:app="app"
		category="discover"
		class="app-discover-app"
		inline
		:list-view="false" />
	<a
		v-else
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
</template>

<script setup lang="ts">
import type { IAppDiscoverApp } from '../../constants/AppDiscoverTypes.ts'

import { computed } from 'vue'
import AppItem from '../AppList/AppItem.vue'
import { useAppsStore } from '../../store/apps-store.ts'

const props = defineProps<{
	modelValue: IAppDiscoverApp
}>()

const store = useAppsStore()
const app = computed(() => store.getAppById(props.modelValue.appId))

const appStoreLink = computed(() => props.modelValue.appId ? `https://apps.nextcloud.com/apps/${props.modelValue.appId}` : '#')
</script>

<style scoped lang="scss">
.app-discover-app {
	width: 100% !important; // full with of the showcase item

	&:hover {
		background: var(--color-background-hover);
		border-radius: var(--border-radius-rounded);
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
