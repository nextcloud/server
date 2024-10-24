<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<PublicPageMenuEntry :id="id"
		click-only
		:icon="icon"
		:href="href"
		:label="label"
		@click="onClick" />
</template>

<script setup lang="ts">
import { showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import PublicPageMenuEntry from './PublicPageMenuEntry.vue'

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
 * Copy the href to the clipboard
 */
async function copyLink() {
	try {
		await window.navigator.clipboard.writeText(props.href)
		showSuccess(t('core', 'Direct link copied to clipboard'))
	} catch {
		// No secure context -> fallback to dialog
		window.prompt(t('core', 'Please copy the link manually:'), props.href)
	}
}

/**
 * onclick handler to trigger the "copy link" action
 * and emit the event so the menu can be closed
 */
function onClick() {
	copyLink()
	emit('click')
}
</script>
