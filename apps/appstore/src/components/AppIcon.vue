<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'

import { mdiCogOutline } from '@mdi/js'
import { computed, ref, watch } from 'vue'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

const { app, noFallback, size = 20 } = defineProps<{
	app: IAppstoreApp | IAppstoreExApp
	noFallback?: boolean
	size?: number
}>()

const isSvg = computed(() => app.icon?.endsWith('.svg'))
const svgIcon = ref<string>('')
watch(() => app.icon, async () => {
	svgIcon.value = ''
	if (app.icon?.endsWith('.svg')) {
		const response = await fetch(app.icon)
		if (response.ok) {
			svgIcon.value = await response.text()
		}
	}
}, { immediate: true })
</script>

<template>
	<span :class="$style.appIcon">
		<NcIconSvgWrapper
			v-if="svgIcon"
			:size
			:svg="svgIcon" />
		<img
			v-else-if="app.icon && !isSvg"
			:class="$style.appIcon__image"
			alt=""
			:src="app.icon"
			:height="size"
			:width="size">
		<NcIconSvgWrapper
			v-else-if="!noFallback"
			:path="mdiCogOutline"
			:size />
	</span>
</template>

<style module>
.appIcon {
	display: inline-flex;
	justify-content: center;
}

.appImage__image {
	filter: var(--invert-if-dark);
	object-fit: cover;
	height: 100%;
	width: 100%;
}
</style>
