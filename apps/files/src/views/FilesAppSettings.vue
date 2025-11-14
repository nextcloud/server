<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { useHotKey } from '@nextcloud/vue/composables/useHotKey'
import { nextTick } from 'vue'
import NcAppSettingsDialog from '@nextcloud/vue/components/NcAppSettingsDialog'
import FilesAppSettingsAppearance from '../components/FilesAppSettings/FilesAppSettingsAppearance.vue'
import FilesAppSettingsGeneral from '../components/FilesAppSettings/FilesAppSettingsGeneral.vue'
import FilesAppSettingsLegacyApi from '../components/FilesAppSettings/FilesAppSettingsLegacyApi.vue'
import FilesAppSettingsShortcuts from '../components/FilesAppSettings/FilesAppSettingsShortcuts.vue'
import FilesAppSettingsWarnings from '../components/FilesAppSettings/FilesAppSettingsWarnings.vue'
import FilesAppSettingsWebDav from '../components/FilesAppSettings/FilesAppSettingsWebDav.vue'

defineProps<{
	open: boolean
}>()

const emit = defineEmits<{
	(e: 'close'): void
	(e: 'update:open', open: boolean): void
}>()

// ? opens the settings dialog on the keyboard shortcuts section
useHotKey('?', showKeyboardShortcuts, {
	stop: true,
	prevent: true,
})

/**
 * Opens the settings dialog and scrolls to the keyboard shortcuts section
 */
async function showKeyboardShortcuts() {
	emit('update:open', true)

	await nextTick()
	document.getElementById('settings-section_keyboard-shortcuts')!.scrollIntoView({
		behavior: 'smooth',
		inline: 'nearest',
	})
}
</script>

<template>
	<NcAppSettingsDialog
		:legacy="false"
		:name="t('files', 'Files settings')"
		no-version
		:open="open"
		show-navigation
		@update:open="emit('close')">
		<FilesAppSettingsGeneral />
		<FilesAppSettingsAppearance />
		<FilesAppSettingsLegacyApi />
		<FilesAppSettingsWarnings />
		<FilesAppSettingsWebDav />
		<FilesAppSettingsShortcuts />
	</NcAppSettingsDialog>
</template>
