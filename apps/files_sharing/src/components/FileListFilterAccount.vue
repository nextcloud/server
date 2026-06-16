<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div :class="$style.fileListFilterAccount">
		<NcTextField
			v-if="availableAccounts.length > 1"
			v-model="accountFilter"
			type="search"
			:label="t('files_sharing', 'Filter accounts')" />
		<NcButton
			v-for="account of shownAccounts"
			:key="account.id"
			alignment="start"
			:pressed="selectedAccounts.includes(account)"
			variant="tertiary"
			wide
			@update:pressed="toggleAccount(account.id, $event)">
			<template #icon>
				<NcAvatar
					:class="$style.fileListFilterAccount__avatar"
					v-bind="account"
					:size="24"
					disable-menu
					hide-status />
			</template>
			{{ account.displayName }}
			<span v-if="account.id === currentUserId" :class="$style.fileListFilterAccount__currentUser">
				({{ t('files', 'you') }})
			</span>
		</NcButton>
	</div>
</template>

<script setup lang="ts">
import type { AccountFilter, IAccountData } from '../files_filters/AccountFilter.ts'

import { t } from '@nextcloud/l10n'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { getCurrentUser } from '../../../../core/src/OC/currentuser.js'

interface IUserSelectData {
	id: string
	user: string
	displayName: string
}

const props = defineProps<{
	filter: AccountFilter
}>()

const currentUserId = getCurrentUser()!.uid

const accountFilter = ref('')
const availableAccounts = ref<IUserSelectData[]>([])
const selectedAccounts = ref<IUserSelectData[]>([])
watch(selectedAccounts, () => {
	const accounts = selectedAccounts.value.map(({ id: uid, displayName }) => ({ uid, displayName }))
	props.filter.setAccounts(accounts.length > 0 ? accounts : undefined)
})

onMounted(() => {
	setAvailableAccounts(props.filter.availableAccounts)
	selectedAccounts.value = availableAccounts.value.filter(({ id }) => props.filter.filterAccounts?.some(({ uid }) => uid === id)) ?? []
	props.filter.addEventListener('accounts-updated', setAvailableAccounts)
	props.filter.addEventListener('reset', resetFilter)
	props.filter.addEventListener('deselect', deselect)
})
onUnmounted(() => {
	props.filter.removeEventListener('accounts-updated', setAvailableAccounts)
	props.filter.removeEventListener('reset', resetFilter)
	props.filter.removeEventListener('deselect', deselect)
})

/**
 * Currently shown accounts (filtered)
 */
const shownAccounts = computed(() => {
	if (!accountFilter.value) {
		return [...availableAccounts.value].sort(sortAccounts)
	}

	const queryParts = accountFilter.value.toLocaleLowerCase().trim().split(' ')
	const accounts = availableAccounts.value.filter((account) => queryParts.every((part) => account.user.toLocaleLowerCase().includes(part)
		|| account.displayName.toLocaleLowerCase().includes(part)))
	return accounts.sort(sortAccounts)
})

/**
 * Sort accounts, putting the current user at the begin
 *
 * @param a - First account
 * @param b - Second account
 */
function sortAccounts(a: IUserSelectData, b: IUserSelectData) {
	if (a.id === currentUserId) {
		return -1
	}
	if (b.id === currentUserId) {
		return 1
	}
	return a.displayName.localeCompare(b.displayName)
}

/**
 * Toggle an account as selected
 *
 * @param accountId The account to toggle
 * @param selected Whether to select or deselect the account
 */
function toggleAccount(accountId: string, selected: boolean) {
	selectedAccounts.value = selectedAccounts.value.filter(({ id }) => id !== accountId)
	if (selected) {
		const account = availableAccounts.value.find(({ id }) => id === accountId)
		if (account) {
			selectedAccounts.value = [...selectedAccounts.value, account]
		}
	}
}

/**
 * Deselect an account
 *
 * @param event - The custom event
 */
function deselect(event: CustomEvent) {
	const accountId = event.detail as string
	selectedAccounts.value = selectedAccounts.value.filter(({ id }) => id !== accountId)
}

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
function setAvailableAccounts(accounts: IAccountData[] | CustomEvent): void {
	if (accounts instanceof CustomEvent) {
		accounts = accounts.detail as IAccountData[]
	}
	availableAccounts.value = accounts.map(({ uid, displayName }) => ({ displayName, id: uid, user: uid }))
}
</script>

<style module>
.fileListFilterAccount {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
}

.fileListFilterAccount__avatar {
	/* 24px is the avatar size */
	margin: calc((var(--default-clickable-area) - 24px) / 2);
}

.fileListFilterAccount__currentUser {
	font-weight: normal !important;
}
</style>
