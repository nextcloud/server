<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppSidebarTab
		v-if="app?.daemon"
		id="daemon"
		:name="t('settings', 'Daemon')"
		:order="3">
		<template #icon>
			<NcIconSvgWrapper :path="mdiFileChart" :size="24" />
		</template>
		<div class="daemon">
			<h4>{{ t('settings', 'Deploy Daemon') }}</h4>
			<p><b>{{ t('settings', 'Type') }}</b>: {{ app?.daemon.accepts_deploy_id }}</p>
			<p><b>{{ t('settings', 'Name') }}</b>: {{ app?.daemon.name }}</p>
			<p><b>{{ t('settings', 'Display Name') }}</b>: {{ app?.daemon.display_name }}</p>
			<p><b>{{ t('settings', 'GPUs support') }}</b>: {{ gpuSupport }}</p>
			<p><b>{{ t('settings', 'Compute device') }}</b>: {{ app?.daemon?.deploy_config?.computeDevice?.label }}</p>
		</div>
	</NcAppSidebarTab>
</template>

<script setup lang="ts">
import type { IAppstoreExApp } from '../../app-types.ts'

import { mdiFileChart } from '@mdi/js'
import { ref } from 'vue'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

const props = defineProps<{
	app: IAppstoreExApp
}>()

const gpuSupport = ref(props.app?.daemon?.deploy_config?.computeDevice?.id !== 'cpu' || false)
</script>

<style scoped lang="scss">
.daemon {
  padding: 20px;

  h4 {
    font-weight: bold;
    margin: 10px auto;
  }
}
</style>
