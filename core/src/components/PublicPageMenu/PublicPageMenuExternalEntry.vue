<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import { ref } from 'vue'
import PublicPageMenuEntry from './PublicPageMenuEntry.vue'
import PublicPageMenuExternalDialog from './PublicPageMenuExternalDialog.vue'

defineOptions({
	// The entry is the element of the menu, the dialog renders next to it
	inheritAttrs: false,
})

defineProps<{
	id: string
	label: string
	icon: string
}>()

const emit = defineEmits<{
	click: []
}>()

const showDialog = ref(false)

/**
 * Open the "create federated share" dialog
 */
function openDialog() {
	showDialog.value = true
	emit('click')
}
</script>

<template>
	<PublicPageMenuEntry
		v-bind="$attrs"
		:id="id"
		:icon="icon"
		href="#"
		:label="label"
		@click="openDialog" />
	<PublicPageMenuExternalDialog
		v-if="showDialog"
		:label="label"
		@close="showDialog = false" />
</template>
