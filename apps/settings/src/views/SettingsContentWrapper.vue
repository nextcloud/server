<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppContent>
		<div ref="contentMain" class="settings-content" />
	</NcAppContent>
</template>

<script setup lang="ts">
import { NcAppContent } from '@nextcloud/vue'
import { onMounted, ref } from 'vue'

const contentMain = ref<HTMLDivElement>()
onMounted(() => {
	const realElement = document.getElementById('original-settings-content')!
	contentMain.value!.replaceChildren(...realElement.childNodes)
	realElement.parentNode!.removeChild(realElement)
})
</script>

<style scoped>
/* The NcAppNavigationToggle is absolutely positioned in the top inline-start
   corner of the content. Reserve the standard toggle clearance
   (--app-navigation-padding above + --default-clickable-area + matching padding
   below) so the first section's heading clears it. Sections already contribute
   ~7 grid units of leading space, so subtract that to avoid a large empty band. */
.settings-content {
	padding-block-start: calc(2 * var(--app-navigation-padding) + var(--default-clickable-area) - var(--default-grid-baseline) * 7);
}
</style>
