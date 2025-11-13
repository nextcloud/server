<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IHotkeyConfig } from '@nextcloud/files'

import { getFileActions } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import NcAppSettingsShortcutsSection from '@nextcloud/vue/components/NcAppSettingsShortcutsSection'
import NcHotkey from '@nextcloud/vue/components/NcHotkey'
import NcHotkeyList from '@nextcloud/vue/components/NcHotkeyList'

const actionHotkeys = getFileActions()
	.filter((action) => !!action.hotkey)
	.sort((a, b) => (a.order || 0) - (b.order || 0))
	.map((action) => ({
		id: action.id,
		label: action.hotkey!.description,
		hotkey: hotkeyToString(action.hotkey!),
	}))

/**
 * Convert a hotkey configuration to a hotkey string.
 *
 * @param hotkey - The hotkey configuration
 */
function hotkeyToString(hotkey: IHotkeyConfig): string {
	const parts: string[] = []
	if (hotkey.ctrl) {
		parts.push('Control')
	}
	if (hotkey.alt) {
		parts.push('Alt')
	}
	if (hotkey.shift) {
		parts.push('Shift')
	}
	parts.push(hotkey.key)
	return parts.join(' ')
}
</script>

<template>
	<NcAppSettingsShortcutsSection>
		<NcHotkeyList :label="t('files', 'Actions')">
			<NcHotkey :label="t('files', 'File actions')" hotkey="A" />

			<NcHotkey
				v-for="hotkey of actionHotkeys"
				:key="hotkey.id"
				:label="hotkey.label"
				:hotkey="hotkey.hotkey" />
		</NcHotkeyList>

		<NcHotkeyList :label="t('files', 'Selection')">
			<NcHotkey :label="t('files', 'Select all files')" hotkey="Control A" />
			<NcHotkey :label="t('files', 'Deselect all')" hotkey="Escape" />
			<NcHotkey :label="t('files', 'Select or deselect')" hotkey="Control Space" />
			<NcHotkey :label="t('files', 'Select a range')" hotkey="Control Shift Space" />
		</NcHotkeyList>

		<NcHotkeyList :label="t('files', 'Navigation')">
			<NcHotkey :label="t('files', 'Go to parent folder')" hotkey="Alt ArrowUp" />
			<NcHotkey :label="t('files', 'Go to file above')" hotkey="ArrowUp" />
			<NcHotkey :label="t('files', 'Go to file below')" hotkey="ArrowDown" />
			<NcHotkey :label="t('files', 'Go left in grid')" hotkey="ArrowLeft" />
			<NcHotkey :label="t('files', 'Go right in grid')" hotkey="ArrowRight" />
		</NcHotkeyList>

		<NcHotkeyList :label="t('files', 'View')">
			<NcHotkey :label="t('files', 'Toggle grid view')" hotkey="V" />
			<NcHotkey :label="t('files', 'Open file sidebar')" hotkey="D" />
			<NcHotkey :label="t('files', 'Show those shortcuts')" hotkey="?" />
		</NcHotkeyList>
	</NcAppSettingsShortcutsSection>
</template>
