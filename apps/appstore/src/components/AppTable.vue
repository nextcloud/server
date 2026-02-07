<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreApp, IAppstoreExApp } from '../apps.ts';

import { t } from '@nextcloud/l10n'
import AppTableRow from './AppTableRow.vue';
import { computed, useTemplateRef } from 'vue';
import { useElementSize } from '@vueuse/core';

defineProps<{
	apps: (IAppstoreApp | IAppstoreExApp)[]
}>()

const tableElement = useTemplateRef('table')
const { width: tableWidth } = useElementSize(tableElement)

const isNarrow = computed(() => tableWidth.value < 768)
</script>

<template>
	<table ref="table" :class="$style.appTable">
		<thead hidden>
			<tr>
				<th>{{ t('appstore', 'App name') }}</th>
				<th>{{ t('appstore', 'Version') }}</th>
				<th v-if="!isNarrow">{{ t('appstore', 'Support level') }}</th>
				<th>{{ t('appstore', 'Actions') }}</th>
			</tr>
		</thead>
		<tbody>
			<AppTableRow
				v-for="app in apps"
				:key="app.id"
				:app
				:isNarrow />
		</tbody>
	</table>
</template>

<style module>
.appTable {
	width: 100%;
}
</style>
