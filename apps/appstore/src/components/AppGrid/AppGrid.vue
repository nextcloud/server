<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreApp, IAppstoreExApp } from '../../apps.d.ts'

import { computed } from 'vue'
import AppGridItem from './AppGridItem.vue'
import { useUserSettingsStore } from '../../store/userSettings.ts'

defineProps<{
	apps: (IAppstoreApp | IAppstoreExApp)[]
}>()

const userSettings = useUserSettingsStore()
const gridSize = computed(() => userSettings.gridSizePx)
</script>

<template>
	<ul :class="$style.appGrid">
		<AppGridItem
			v-for="app in apps"
			:key="app.id"
			:app />
	</ul>
</template>

<style module>
.appGrid {
	width: 100%;
	display: grid;
	gap: calc(4 * var(--default-grid-baseline));
	grid-template-columns: repeat(auto-fit, minmax(v-bind(gridSize), 1fr));
	padding-inline-start: var(--app-navigation-padding);
}
</style>
