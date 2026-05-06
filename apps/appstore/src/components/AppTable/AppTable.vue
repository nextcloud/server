<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreApp, IAppstoreExApp } from '../../apps.d.ts'

import { t } from '@nextcloud/l10n'
import { useElementSize } from '@vueuse/core'
import { computed, useTemplateRef } from 'vue'
import AppTableRow from './AppTableRow.vue'

defineProps<{
	apps: (IAppstoreApp | IAppstoreExApp)[]
}>()

const tableElement = useTemplateRef('table')
const { width: tableWidth } = useElementSize(tableElement)

const isNarrow = computed(() => tableWidth.value < 768)
</script>

<template>
	<table ref="table" :class="[$style.appTable, { [$style.appTable_narrow]: isNarrow }]">
		<colgroup>
			<col :class="$style.appTable__colName">
			<col :class="$style.appTable__colVersion">
			<col v-if="!isNarrow" :class="$style.appTable__colSupport">
			<col :class="$style.appTable__colActions">
		</colgroup>
		<thead hidden>
			<tr>
				<th>{{ t('appstore', 'App name') }}</th>
				<th>{{ t('appstore', 'Version') }}</th>
				<th v-if="!isNarrow">
					{{ t('appstore', 'Support level') }}
				</th>
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
	table-layout: fixed;
	width: 100%;
}

.appTable__colName {
	width: 45%;
}

.appTable_narrow .appTable__colName {
	width: 60%;
}

.appTable__colSupport {
	width: 15%;
}

.appTable__colActions {
	width: 25%;
}

.appTable_narrow .appTable__colActions {
	width: calc(3 * var(--default-grid-baseline) + 2 * var(--default-clickable-area));
}
</style>
