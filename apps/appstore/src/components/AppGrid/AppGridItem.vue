<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreApp, IAppstoreExApp } from '../../apps.d.ts'

import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppImage from '../AppImage.vue'
import BadgeAppDaemon from '../BadgeAppDaemon.vue'
import BadgeAppLevel from '../BadgeAppLevel.vue'
import BadgeAppScore from '../BadgeAppScore.vue'
import { useUserSettingsStore } from '../../store/userSettings.ts'

const { app } = defineProps<{
	app: IAppstoreApp | IAppstoreExApp
}>()

const userSettingsStore = useUserSettingsStore()
const route = useRoute()
const routeToDetails = computed(() => ({
	...route,
	params: {
		...route.params,
		id: app.id,
	},
	query: userSettingsStore.getQuery(),
}))
</script>

<template>
	<li :class="$style.appGridItem">
		<RouterLink :to="routeToDetails">
			<AppImage :app :class="$style.appGridItem__image" />
			<div :class="$style.appGridItem__content">
				<h3 :class="$style.appGridItem__name">
					{{ app.name }}
				</h3>
				<p>{{ app.summary }}</p>
			</div>
		</RouterLink>
		<div :class="$style.appGridItem__badges">
			<BadgeAppScore :app />
			<BadgeAppLevel :level="app.level" />
			<BadgeAppDaemon v-if="app.app_api && app.daemon" :daemon="app.daemon" />
		</div>
	</li>
</template>

<style module>
.appGridItem {
	background-color: var(--color-primary-element-light);
	color: var(--color-primary-element-light-text);
	border-radius: var(--border-radius-element);
	padding-block-end: var(--border-radius-element);;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	gap: var(--default-grid-baseline);

	&:hover {
		background-color: var(--color-primary-element-light-hover);
	}
}

.appGridItem__content {
	padding-inline: var(--border-radius-element);
}

.appGridItem__image {
	aspect-ratio: 16 / 9;
	height: min-content;
	border-start-start-radius: var(--border-radius-element);
	border-start-end-radius: var(--border-radius-element);
	overflow: hidden;
}

.appGridItem__name {
	font-size: 1.2em;
	font-weight: var(--font-weight-heading, bold);
	margin-block: var(--default-grid-baseline) calc(2 * var(--default-grid-baseline));
}

.appGridItem__badges {
	display: flex;
	flex-direction: row;
	gap: var(--default-grid-baseline);
	padding-inline: var(--border-radius-element);
	width: 100%;
}
</style>
