<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ISidebarTab, SidebarComponent } from '@nextcloud/files'

import { NcIconSvgWrapper, NcLoadingIcon } from '@nextcloud/vue'
import { ref, toRef, watch, watchEffect } from 'vue'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import { useActiveStore } from '../../store/active.ts'
import { useSidebarStore } from '../../store/sidebar.ts'

const props = defineProps<{
	/**
	 * If this is the currently active tab
	 */
	active: boolean

	/**
	 * The sidebar tab definition.
	 */
	tab: ISidebarTab
}>()

const sidebar = useSidebarStore()
const activeStore = useActiveStore()

const loading = ref(true)
watch(toRef(props, 'tab'), async () => {
	loading.value = true
	await window.customElements.whenDefined(props.tab.tagName)
	loading.value = false
}, { immediate: true })

const tabElement = ref<SidebarComponent>()
watchEffect(async () => {
	if (tabElement.value) {
		// Mark as active
		await tabElement.value.setActive?.(props.active)
	}
})
</script>

<template>
	<NcAppSidebarTab
		:id="tab.id"
		:order="tab.order"
		:name="tab.displayName">
		<template #icon>
			<NcIconSvgWrapper :svg="tab.iconSvgInline" />
		</template>
		<NcEmptyContent v-if="loading">
			<template #icon>
				<NcLoadingIcon />
			</template>
		</NcEmptyContent>

		<component
			:is="tab.tagName"
			v-else
			ref="tabElement"
			:node.prop="sidebar.currentNode"
			:folder.prop="activeStore.activeFolder"
			:view.prop="activeStore.activeView" />
	</NcAppSidebarTab>
</template>
