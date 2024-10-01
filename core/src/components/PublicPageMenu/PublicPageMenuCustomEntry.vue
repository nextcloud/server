<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<!-- eslint-disable-next-line vue/no-v-html -->
	<li ref="listItem" :role="itemRole" v-html="html" />
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'

defineProps<{
	id: string
	html: string
}>()

const listItem = ref<HTMLLIElement>()
const itemRole = ref('presentation')

onMounted(() => {
	// check for proper roles
	const menuitem = listItem.value?.querySelector('[role="menuitem"]')
	if (menuitem) {
		return
	}
	// check if a button is available
	const button = listItem.value?.querySelector('button') ?? listItem.value?.querySelector('a')
	if (button) {
		button.role = 'menuitem'
	} else {
		// if nothing is available set role on `<li>`
		itemRole.value = 'menuitem'
	}
})
</script>
