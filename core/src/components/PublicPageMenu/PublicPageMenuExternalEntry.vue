<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<Fragment>
		<PublicPageMenuEntry
			:id="id"
			:icon="icon"
			href="#"
			:label="label"
			@click="openDialog" />
		<PublicPageMenuExternalDialog v-if="showDialog" :label="label" />
	</Fragment>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Fragment } from 'vue-frag'
import PublicPageMenuEntry from './PublicPageMenuEntry.vue'
import PublicPageMenuExternalDialog from './PublicPageMenuExternalDialog.vue'

defineProps<{
	id: string
	label: string
	icon: string
	href: string
}>()

const emit = defineEmits<{
	(e: 'click'): void
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
