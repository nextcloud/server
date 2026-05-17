<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IDeployDaemon } from '../../apps.d.ts'

import { t } from '@nextcloud/l10n'
import DaemonSelectionDialogListEntry from './DaemonSelectionDialogListEntry.vue'
import { useExAppsStore } from '../../store/exApps.ts'

defineEmits<{
	selected: [daemon: IDeployDaemon]
}>()

const store = useExAppsStore()
</script>

<template>
	<ul
		:class="$style.DaemonSelectionDialogList"
		:aria-label="t('appstore', 'Registered Deploy daemons list')">
		<DaemonSelectionDialogListEntry
			v-for="daemon in store.dockerDaemons"
			:key="daemon.id"
			:daemon
			:isDefault="store.defaultDaemon?.name === daemon.name"
			@selected="$emit('selected', daemon)" />
	</ul>
</template>

<style module>
.DaemonSelectionDialogList {
	max-height: 350px;
	overflow-y: scroll;
	padding: 2rem;
}
</style>
