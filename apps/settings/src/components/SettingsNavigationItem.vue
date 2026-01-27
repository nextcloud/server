<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { generateUrl } from '@nextcloud/router'
import { computed } from 'vue'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'

export interface ISettingsSection {
	id: string
	name: string
	icon?: string
	active: boolean
}

const props = defineProps<{
	section: ISettingsSection
	type: 'admin' | 'personal'
}>()

const href = computed(() => generateUrl('/settings/{type}/{section}', {
	type: props.type === 'personal' ? 'user' : 'admin',
	section: props.section.id,
}))
</script>

<template>
	<NcAppNavigationItem
		:name="section.name"
		:active="section.active"
		:href="href">
		<template v-if="section.icon" #icon>
			<img
				:class="$style.settingsNavigationItem__icon"
				:src="section.icon"
				alt="">
		</template>
	</NcAppNavigationItem>
</template>

<style module>
.settingsNavigationItem__icon {
	width: var(--default-font-size);
	height: var(--default-font-size);
	object-fit: contain;
	filter: var(--background-invert-if-dark);
}

:global(.active) .settingsNavigationItem__icon {
	filter: var(--primary-invert-if-dark);
}
</style>
