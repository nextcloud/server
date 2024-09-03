<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<PublicPageMenuEntry :id="id"
		:icon="icon"
		href="#"
		:label="label"
		@click="openDialog" />
</template>

<script setup lang="ts">
import { spawnDialog } from '@nextcloud/dialogs'
import PublicPageMenuEntry from './PublicPageMenuEntry.vue'
import PublicPageMenuExternalDialog from './PublicPageMenuExternalDialog.vue'

const props = defineProps<{
	id: string
	label: string
	icon: string
	href: string
}>()

const emit = defineEmits<{
	(e: 'click'): void
}>()

/**
 * Open the "create federated share" dialog
 */
function openDialog() {
	spawnDialog(PublicPageMenuExternalDialog, { label: props.label })
	emit('click')
}
</script>
