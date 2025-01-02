<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<FileListFilter class="file-list-filter-accounts"
		:is-active="selectedAccounts.length > 0"
		:filter-name="t('files_sharing', 'People')"
		@reset-filter="resetFilter">
		<template #icon>
			<NcIconSvgWrapper :path="mdiAccountMultiple" />
		</template>
		<NcActionInput v-if="availableAccounts.length > 1"
			:label="t('files_sharing', 'Filter accounts')"
			:label-outside="false"
			:show-trailing-button="false"
			type="search"
			:value.sync="accountFilter" />
		<NcActionButton v-for="account of shownAccounts"
			:key="account.id"
			class="file-list-filter-accounts__item"
			type="radio"
			:model-value="selectedAccounts.includes(account)"
			:value="account.id"
			@click="toggleAccount(account.id)">
			<template #icon>
				<NcAvatar class="file-list-filter-accounts__avatar"
					v-bind="account"
					:size="24"
					disable-menu
					:show-user-status="false" />
			</template>
			{{ account.displayName }}
		</NcActionButton>
	</FileListFilter>
</template>

<script setup lang="ts">
import type { IAccountData } from '../filters/AccountFilter.ts'

import { translate as t } from '@nextcloud/l10n'
import { ShareType } from '@nextcloud/sharing'
import { mdiAccountMultiple } from '@mdi/js'
import { useBrowserLocation } from '@vueuse/core'
import { computed, ref, watch } from 'vue'
import { useNavigation } from '../../../files/src/composables/useNavigation.ts'

import FileListFilter from '../../../files/src/components/FileListFilter/FileListFilter.vue'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

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
const accountFilter = ref('')
const availableAccounts = ref<IUserSelectData[]>([])
const selectedAccounts = ref<IUserSelectData[]>([])

/**
 * Currently shown accounts (filtered)
 */
const shownAccounts = computed(() => {
	if (!accountFilter.value) {
		return availableAccounts.value
	}
	const queryParts = accountFilter.value.toLocaleLowerCase().trim().split(' ')
	return availableAccounts.value.filter((account) =>
		queryParts.every((part) =>
			account.user.toLocaleLowerCase().includes(part)
			|| account.displayName.toLocaleLowerCase().includes(part),
		),
	)
})

/**
 * Toggle an account as selected
 * @param accountId The account to toggle
 */
function toggleAccount(accountId: string) {
	const account = availableAccounts.value.find(({ id }) => id === accountId)
	if (account && selectedAccounts.value.includes(account)) {
		selectedAccounts.value = selectedAccounts.value.filter(({ id }) => id !== accountId)
	} else {
		if (account) {
			selectedAccounts.value = [...selectedAccounts.value, account]
		}
	}
}

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
		const owner = node.owner
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
				if (sharee.type !== ShareType.User && sharee.type !== ShareType.Remote) {
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
	accountFilter.value = ''
}
defineExpose({ resetFilter, toggleAccount })

// When the current view changes or the current directory,
// then we need to rebuild the available accounts
watch([currentView, currentLocation], () => {
	if (currentView.value) {
		// we have no access to the files router here...
		const path = (currentLocation.value.search ?? '?dir=/').match(/(?<=&|\?)dir=([^&#]+)/)?.[1]
		resetFilter()
		updateAvailableAccounts(decodeURIComponent(path ?? '/'))
	}
}, { immediate: true })
</script>

<style scoped lang="scss">
.file-list-filter-accounts {
	&__item {
		min-width: 250px;
	}

	&__avatar {
		// 24px is the avatar size
		margin: calc((var(--default-clickable-area) - 24px) / 2)
	}
}
</style>
