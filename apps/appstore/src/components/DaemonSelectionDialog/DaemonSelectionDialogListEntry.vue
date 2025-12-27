<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IDeployDaemon } from '../../apps.d.ts'

import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcListItem from '@nextcloud/vue/components/NcListItem'

const props = defineProps<{
	/**
	 * The daemon to use
	 */
	daemon: IDeployDaemon
	/**
	 * Whether this daemon is the default one
	 */
	isDefault: boolean
}>()

const emit = defineEmits<{
	selected: []
}>()

const itemTitle = computed(() => `${props.daemon.name} - ${props.daemon.display_name}`)
</script>

<template>
	<NcListItem
		:active="isDefault"
		:counterNumber="daemon.exAppsCount"
		counterType="highlighted"
		:details="isDefault ? t('appstore', 'Default') : ''"
		forceDisplayActions
		:name="itemTitle"
		@click.stop="emit('selected')">
		<template #subname>
			{{ daemon.accepts_deploy_id }}
		</template>
	</NcListItem>
</template>
