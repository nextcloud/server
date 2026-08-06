<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreExApp, IDeployDaemon } from '../../apps.d.ts'

import { mdiFormatListBulleted } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import DaemonSelectionDialogList from './DaemonSelectionDialogList.vue'
import { useExAppsStore } from '../../store/exApps.ts'

defineProps<{
	/**
	 * The app to enable
	 */
	app: IAppstoreExApp
}>()

defineEmits<{
	close: [daemon?: IDeployDaemon]
}>()

const store = useExAppsStore()
const appApiAdminPage = generateUrl('/settings/admin/app_api')
</script>

<template>
	<NcDialog
		:name="t('appstore', 'Choose Deploy Daemon for {appName}', { appName: app.name })"
		size="normal"
		@update:open="$event || $emit('close')">
		<NcEmptyContent
			v-if="store.dockerDaemons.length === 0"
			class="daemon-selection-list__empty-content"
			:name="t('appstore', 'No Deploy daemons configured')"
			:description="t('appstore', 'Register a custom one or setup from available templates')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiFormatListBulleted" />
			</template>
			<template #action>
				<NcButton :href="appApiAdminPage">
					{{ t('appstore', 'Manage Deploy daemons') }}
				</NcButton>
			</template>
		</NcEmptyContent>

		<DaemonSelectionDialogList
			v-else
			:app="app"
			@selected="$emit('close', $event)" />
	</NcDialog>
</template>
