<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreExApp, IDeployOptions } from '../../apps.d.ts'

import { t } from '@nextcloud/l10n'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import DaemonSelectionDialogList from './DaemonSelectionDialogList.vue'

/**
 * Whether the dialog is shown
 */
const show = defineModel<boolean>('show')

defineProps<{
	/**
	 * The app to enable
	 */
	app: IAppstoreExApp

	/**
	 * Deployment options
	 */
	deployOptions?: IDeployOptions
}>()

/**
 * Close the dialog
 */
function closeModal() {
	show.value = false
}
</script>

<template>
	<NcDialog
		:open="show"
		:name="t('settings', 'Choose Deploy Daemon for {appName}', { appName: app.name })"
		size="normal"
		@update:open="closeModal">
		<DaemonSelectionDialogList
			:app="app"
			:deploy-options="deployOptions"
			@close="closeModal" />
	</NcDialog>
</template>
