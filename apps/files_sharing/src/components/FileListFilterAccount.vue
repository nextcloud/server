<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSelect v-model="selectedAccounts"
		:aria-label-combobox="t('files_sharing', 'Accounts')"
		class="file-list-filter-accounts"
		multiple
		no-wrap
		:options="availableAccounts"
		:placeholder="t('files_sharing', 'Accounts')"
		user-select />
</template>

<script setup lang="ts">
import type { IAccountData } from '../filters/AccountFilter.ts'

import { translate as t } from '@nextcloud/l10n'
import { useBrowserLocation } from '@vueuse/core'
import { ref, watch, watchEffect } from 'vue'
import { useNavigation } from '../../../files/src/composables/useNavigation.ts'

import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'

interface IUserSelectData {
	id: string
	user: string
	displayName: string
}

const emit = defineEmits<{
	(event: 'update:accounts', value: IAccountData[]): void
}>()

const { currentView } = useNavigation()
const currentLocation = useBrowserLocation()
const availableAccounts = ref<IUserSelectData[]>([])
const selectedAccounts = ref<IUserSelectData[]>([])

// Watch selected account, on change we emit the new account data to the filter instance
watch(selectedAccounts, () => {
	// Emit selected accounts as account data
	const accounts = selectedAccounts.value.map(({ id: uid, displayName }) => ({ uid, displayName }))
	emit('update:accounts', accounts)
})

/**
 * Update the accounts owning nodes or have nodes shared to them
 * @param path The path inside the current view to load for accounts
 */
async function updateAvailableAccounts(path: string = '/') {
	availableAccounts.value = []
	if (!currentView.value) {
		return
	}

	const { contents } = await currentView.value.getContents(path)
	const available = new Map<string, IUserSelectData>()
	for (const node of contents) {
		const owner = node.owner ?? node.attributes['owner-id']
		if (owner && !available.has(owner)) {
			available.set(owner, {
				id: owner,
				user: owner,
				displayName: node.attributes['owner-display-name'] ?? node.owner,
			})
		}

		const sharees = node.attributes.sharees?.sharee
		if (sharees) {
			// ensure sharees is an array (if only one share then it is just an object)
			for (const sharee of [sharees].flat()) {
				// Skip link shares and other without user
				if (sharee.id === '') {
					continue
				}
				// Add if not already added
				if (!available.has(sharee.id)) {
					available.set(sharee.id, {
						id: sharee.id,
						user: sharee.id,
						displayName: sharee['display-name'],
					})
				}
			}
		}
	}
	availableAccounts.value = [...available.values()]
}

/**
 * Reset this filter
 */
function resetFilter() {
	selectedAccounts.value = []
}
defineExpose({ resetFilter })

// When the current view changes or the current directory,
// then we need to rebuild the available accounts
watchEffect(() => {
	if (currentView.value) {
		// we have no access to the files router here...
		const path = (currentLocation.value.search ?? '?dir=/').match(/(?<=&|\?)dir=([^&#]+)/)?.[1]
		selectedAccounts.value = []
		updateAvailableAccounts(decodeURIComponent(path ?? '/'))
	}
})
</script>

<style scoped lang="scss">
.file-list-filter-accounts {
	max-width: 300px;
}
</style>
