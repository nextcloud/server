<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import ExternalStorageTableRow from './ExternalStorageTableRow.vue'
import { useStorages } from '../store/storages.ts'

const store = useStorages()
const { isAdmin } = loadState<{ isAdmin: boolean }>('files_external', 'settings')
const storages = computed(() => {
	if (isAdmin) {
		return store.globalStorages
	} else {
		return [
			...store.userStorages,
			...store.globalStorages,
		]
	}
})
</script>

<template>
	<table :class="$style.storageTable" :aria-label="t('files_external', 'External storages')">
		<thead :class="$style.storageTable__header">
			<tr>
				<th :class="$style.storageTable__headerStatus">
					<span class="hidden-visually">
						{{ t('files_external', 'Status') }}
					</span>
				</th>
				<th :class="$style.storageTable__headerFolder">
					{{ t('files_external', 'Folder name') }}
				</th>
				<th :class="$style.storageTable__headerBackend">
					{{ t('files_external', 'External storage') }}
				</th>
				<th :class="$style.storageTable__headerAuthentication">
					{{ t('files_external', 'Authentication') }}
				</th>
				<th v-if="isAdmin">
					{{ t('files_external', 'Restricted to') }}
				</th>
				<th :class="$style.storageTable__headerActions">
					<span class="hidden-visually">
						{{ t('files_external', 'Actions') }}
					</span>
				</th>
			</tr>
		</thead>
		<tbody>
			<ExternalStorageTableRow
				v-for="storage in storages"
				:key="storage.id"
				:is-admin
				:storage="storage" />
		</tbody>
	</table>
</template>

<style module>
.storageTable {
	width: 100%;
}

.storageTable td,th {
	padding-block: calc(var(--default-grid-baseline) / 2);
	padding-inline: var(--default-grid-baseline);
}

.storageTable__header {
	color: var(--color-text-maxcontrast);
	min-height: var(--default-clickable-area);
}

.storageTable__headerStatus {
	width: calc(var(--default-clickable-area) + 2 * var(--default-grid-baseline));
}

.storageTable__headerFolder {
	width: 25%;
}

.storageTable__headerBackend {
	width: 20%;
}

.storageTable__headerFAuthentication {
	width: 20%;
}

.storageTable__headerActions {
	width: calc(2 * var(--default-clickable-area) + 3 * var(--default-grid-baseline));
}
</style>
