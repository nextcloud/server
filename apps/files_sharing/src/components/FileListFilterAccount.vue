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
import type { IAccountData } from '../files_filters/AccountFilter.ts'

import { translate as t } from '@nextcloud/l10n'
import { mdiAccountMultiple } from '@mdi/js'
import { computed, ref, watch } from 'vue'

import FileListFilter from '../../../files/src/components/FileListFilter/FileListFilter.vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

interface IUserSelectData {
	id: string
	user: string
	displayName: string
}

const emit = defineEmits<{
	(event: 'update:accounts', value: IAccountData[]): void
}>()

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
 * Reset this filter
 */
function resetFilter() {
	selectedAccounts.value = []
	accountFilter.value = ''
}

/**
 * Update list of available accounts in current view.
 *
 * @param accounts - Accounts to use
 */
function setAvailableAccounts(accounts: IAccountData[]): void {
	availableAccounts.value = accounts.map(({ uid, displayName }) => ({ displayName, id: uid, user: uid }))
}

defineExpose({
	resetFilter,
	setAvailableAccounts,
	toggleAccount,
})
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
