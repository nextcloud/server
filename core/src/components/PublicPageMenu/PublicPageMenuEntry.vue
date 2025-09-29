<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<NcListItem :anchor-id="`${id}--link`"
		compact
		:details="details"
		:href="href"
		:name="label"
		role="presentation"
		@click="$emit('click')">
		<template #icon>
			<slot v-if="$scopedSlots.icon" name="icon" />
			<div v-else role="presentation" :class="['icon', icon, 'public-page-menu-entry__icon']" />
		</template>
	</NcListItem>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'

import NcListItem from '@nextcloud/vue/components/NcListItem'

const props = defineProps<{
	/** Only emit click event but do not open href */
	clickOnly?: boolean
	// menu entry props
	id: string
	label: string
	icon?: string
	href: string
	details?: string
}>()

onMounted(() => {
	const anchor = document.getElementById(`${props.id}--link`) as HTMLAnchorElement
	// Make the `<a>` a menuitem
	anchor.role = 'menuitem'
	// Prevent native click handling if required
	if (props.clickOnly) {
		anchor.onclick = (event) => event.preventDefault()
	}
})
</script>

<style scoped>
.public-page-menu-entry__icon {
	padding-inline-start: var(--default-grid-baseline);
}
</style>
