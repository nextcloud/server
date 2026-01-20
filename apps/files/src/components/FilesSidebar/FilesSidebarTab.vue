<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ISidebarTab } from '@nextcloud/files'

import { NcIconSvgWrapper, NcLoadingIcon } from '@nextcloud/vue'
import { ref, toRef, watch } from 'vue'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import logger from '../../logger.ts'
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
watch(toRef(props, 'active'), async (active) => {
	if (!active) {
		return
	}

	logger.debug('sidebar: activating files sidebar tab ' + props.tab.id, { tab: props.tab })
	loading.value = true
	try {
		if (!initializedTabs.has(props.tab.tagName)) {
			initializedTabs.add(props.tab.tagName)
			logger.debug('sidebar: initializing ' + props.tab.id)
			await props.tab.onInit?.()
		}
		logger.debug('sidebar: waiting for sidebar tab component becoming defined ' + props.tab.id)
		await window.customElements.whenDefined(props.tab.tagName)
		logger.debug('sidebar: tab component defined and loaded ' + props.tab.id)
		loading.value = false
	} catch (error) {
		logger.error('Failed to get sidebar tab web component', { error })
	}
}, { immediate: true })
</script>

<script lang="ts">
const initializedTabs = new Set<string>()
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
			:active.prop="active"
			:node.prop="sidebar.currentNode"
			:folder.prop="activeStore.activeFolder"
			:view.prop="activeStore.activeView" />
	</NcAppSidebarTab>
</template>
