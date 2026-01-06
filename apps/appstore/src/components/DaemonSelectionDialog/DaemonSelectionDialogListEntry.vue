<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreExApp, IDeployDaemon, IDeployOptions } from '../../apps.d.ts'

import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcListItem from '@nextcloud/vue/components/NcListItem'
import { useExAppsStore } from '../../store/exApps.ts'

const props = defineProps<{
	/**
	 * The app to enable
	 */
	app: IAppstoreExApp
	/**
	 * The daemon to use
	 */
	daemon: IDeployDaemon
	/**
	 * Whether this daemon is the default one
	 */
	isDefault: boolean
	/**
	 * Deployment options
	 */
	deployOptions?: IDeployOptions
}>()

const emit = defineEmits<{
	close: []
}>()

const store = useExAppsStore()
const itemTitle = computed(() => `${props.daemon.name} - ${props.daemon.display_name}`)

/**
 * Close the modal
 */
function closeModal() {
	emit('close')
}

/**
 * Enable the app using the selected daemon
 */
function selectDaemonAndInstall() {
	store.enable(props.app.id, props.daemon, props.deployOptions)
	closeModal()
}
</script>

<template>
	<NcListItem
		:name="itemTitle"
		:details="isDefault ? t('settings', 'Default') : ''"
		:force-display-actions="true"
		:counter-number="daemon.exAppsCount"
		:active="isDefault"
		counter-type="highlighted"
		@click.stop="selectDaemonAndInstall">
		<template #subname>
			{{ daemon.accepts_deploy_id }}
		</template>
	</NcListItem>
</template>
