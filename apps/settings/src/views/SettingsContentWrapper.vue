<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppContent>
		<div ref="contentMain" />
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
