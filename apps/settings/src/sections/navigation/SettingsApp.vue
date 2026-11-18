<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { onMounted, useTemplateRef } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcContent from '@nextcloud/vue/components/NcContent'
import SettingsNavigation from './SettingsNavigation.vue'

const props = defineProps<{
	/** Element with the page heading and the server-rendered settings sections */
	sections: HTMLElement
}>()

const contentMain = useTemplateRef<HTMLDivElement>('contentMain')
onMounted(() => {
	contentMain.value!.replaceChildren(...props.sections.childNodes)
})
</script>

<template>
	<NcContent appName="settings">
		<SettingsNavigation />
		<NcAppContent>
			<div ref="contentMain" :class="$style.settingsContent" />
		</NcAppContent>
	</NcContent>
</template>

<style module>
/* The NcAppNavigationToggle is absolutely positioned in the top inline-start
   corner of the content. Reserve the standard toggle clearance
   (--app-navigation-padding above + --default-clickable-area + matching padding
   below) so the first section's heading clears it. Sections already contribute
   ~7 grid units of leading space, so subtract that to avoid a large empty band. */
.settingsContent {
	padding-block-start: calc(2 * var(--app-navigation-padding) + var(--default-clickable-area) - var(--default-grid-baseline) * 7);
}
</style>
