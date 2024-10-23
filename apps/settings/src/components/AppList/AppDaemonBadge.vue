<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<span v-if="daemon"
		class="app-daemon-badge"
		:title="daemon.name">
		<NcIconSvgWrapper :path="mdiFileChart" :size="20" inline />
		{{ daemon.display_name }}
	</span>
</template>

<script setup lang="ts">
import type { IDeployDaemon } from '../../app-types.ts'
import { mdiFileChart } from '@mdi/js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

defineProps<{
	daemon?: IDeployDaemon
}>()
</script>

<style scoped lang="scss">
.app-daemon-badge {
	color: var(--color-text-maxcontrast);
	background-color: transparent;
	border: 1px solid var(--color-text-maxcontrast);
	border-radius: var(--border-radius);

	display: flex;
	flex-direction: row;
	gap: 6px;
	padding: 3px 6px;
	width: fit-content;
}
</style>
