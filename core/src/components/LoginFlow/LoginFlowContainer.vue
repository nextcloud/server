<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import NcGuestContent from '@nextcloud/vue/components/NcGuestContent'

defineProps<{
	heading: string
}>()
</script>

<template>
	<NcGuestContent class="picker-window" :class="$style.loginFlowContainer">
		<h2>{{ heading }}</h2>
		<slot />
	</NcGuestContent>
</template>

<style module>
.loginFlowContainer {
	display: flex;
	flex-direction: column;
}
</style>
