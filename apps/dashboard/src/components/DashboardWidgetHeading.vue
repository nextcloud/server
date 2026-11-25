<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
withDefaults(defineProps<{
	title: string
	tag?: 'h2' | 'h3'
	iconUrl?: string
	iconClass?: string
	href?: string | null
}>(), {
	tag: 'h2',
	iconUrl: '',
	iconClass: '',
	href: null,
})
</script>

<template>
	<component :is="tag">
		<img v-if="iconUrl" :src="iconUrl" alt="">
		<span v-else :class="iconClass" aria-hidden="true" />
		<a v-if="href" :href="href">{{ title }}</a>
		<template v-else>
			{{ title }}
		</template>
	</component>
</template>
