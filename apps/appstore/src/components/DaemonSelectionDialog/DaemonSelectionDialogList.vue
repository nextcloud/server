<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreExApp, IDeployOptions } from '../../apps.d.ts'

import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import FormatListBullet from 'vue-material-design-icons/FormatListBulleted.vue'
import DaemonSelectionDialogListEntry from './DaemonSelectionDialogListEntry.vue'
import { useExAppsStore } from '../../store/exApps.ts'

defineProps<{
	/**
	 * The app to enable
	 */
	app: IAppstoreExApp

	/**
	 * Deployment options
	 */
	deployOptions?: IDeployOptions | undefined
}>()

const emit = defineEmits(['close'])

const appApiAdminPage = generateUrl('/settings/admin/app_api')

const store = useExAppsStore()

/**
 * Close the modal
 */
function closeModal() {
	emit('close')
}
</script>

<template>
	<div class="daemon-selection-list">
		<ul
			v-if="store.dockerDaemons.length > 0"
			:aria-label="t('settings', 'Registered Deploy daemons list')">
			<DaemonSelectionDialogListEntry
				v-for="daemon in store.dockerDaemons"
				:key="daemon.id"
				:daemon="daemon"
				:is-default="store.defaultDaemon!.name === daemon.name"
				:app="app"
				:deploy-options="deployOptions"
				@close="closeModal" />
		</ul>
		<NcEmptyContent
			v-else
			class="daemon-selection-list__empty-content"
			:name="t('settings', 'No Deploy daemons configured')"
			:description="t('settings', 'Register a custom one or setup from available templates')">
			<template #icon>
				<FormatListBullet :size="20" />
			</template>
			<template #action>
				<NcButton :href="appApiAdminPage">
					{{ t('settings', 'Manage Deploy daemons') }}
				</NcButton>
			</template>
		</NcEmptyContent>
	</div>
</template>

<style scoped lang="scss">
.daemon-selection-list {
	max-height: 350px;
	overflow-y: scroll;
	padding: 2rem;

	&__empty-content {
		margin-top: 0;
		text-align: center;
	}
}
</style>
