<!--
  - @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
  -
  - @author Ferdinand Thiessen <opensource@fthiessen.de>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<AppItem v-if="app"
		:app="app"
		category="discover"
		class="app-discover-app"
		inline
		:list-view="false" />
	<a v-else
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
import type { IAppDiscoverApp } from '../../constants/AppDiscoverTypes'

import { computed } from 'vue'
import { useAppsStore } from '../../store/apps-store.ts'

import AppItem from '../AppList/AppItem.vue'

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
