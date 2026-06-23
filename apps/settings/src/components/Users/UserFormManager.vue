<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user-form__item user-form__managers">
		<NcSelectUsers
			:modelValue="managerModel"
			class="user-form__select"
			:inputLabel="t('settings', 'Manager')"
			:placeholder="t('settings', 'Search for a manager…')"
			:options="managerOptions"
			:loading="loading"
			@update:modelValue="onManagerChange"
			@search="searchUserManager" />
	</div>
</template>

<script setup lang="ts">
import type { NcSelectUsersModel } from '@nextcloud/vue/components/NcSelectUsers'

import { translate as t } from '@nextcloud/l10n'
import { computed, inject, onBeforeUnmount, ref } from 'vue'
import NcSelectUsers from '@nextcloud/vue/components/NcSelectUsers'
import logger from '../../logger.ts'
import { useStore } from '../../store/index.js'
import { formDataKey } from './injectionKeys.ts'

const store = useStore()

const formData = inject(formDataKey)!

const possibleManagers = ref<Array<{ id: string, displayname?: string, email?: string }>>([])
const loading = ref(false)
let searchTimeout: ReturnType<typeof setTimeout> | undefined
let managerModelCache: NcSelectUsersModel | undefined

// Cache by value so NcSelectUsers keeps a stable modelValue reference across re-renders.
const managerModel = computed<NcSelectUsersModel | undefined>(() => {
	const m = formData.manager
	if (!m) {
		return undefined
	}
	const id = typeof m === 'object' ? m.id : m
	const displayName = typeof m === 'object' ? (m.displayname ?? m.id) : m
	if (managerModelCache?.id === id && managerModelCache?.displayName === displayName) {
		return managerModelCache
	}
	managerModelCache = { id, displayName }
	return managerModelCache
})

const managerOptions = computed<NcSelectUsersModel[]>(() => possibleManagers.value.map((u) => ({
	id: u.id,
	displayName: u.displayname ?? u.id,
	subname: u.email ?? '',
})))

onBeforeUnmount(() => clearTimeout(searchTimeout))

/**
 * Write the selected manager back to formData.
 *
 * @param value The selected model, or null when cleared
 */
function onManagerChange(value: NcSelectUsersModel | NcSelectUsersModel[] | null) {
	const manager = Array.isArray(value) ? value[0] : value
	formData.manager = manager
		? { id: manager.id, displayname: manager.displayName }
		: ''
}

/**
 * Debounce the search so a 10-char query produces 1-2 requests, not 10.
 *
 * @param query The current search string
 */
function searchUserManager(query: string) {
	clearTimeout(searchTimeout)
	searchTimeout = setTimeout(() => fetchManagers(query), 200)
}

/**
 * Fetch matching users to populate the manager dropdown.
 *
 * @param query The current search string
 */
async function fetchManagers(query: string) {
	loading.value = true
	try {
		const response = await store.dispatch('searchUsers', {
			offset: 0,
			limit: 10,
			search: query,
		})
		const users = response?.data ? Object.values(response.data.ocs.data.users) : []
		possibleManagers.value = users as typeof possibleManagers.value
	} catch (error) {
		logger.error('Failed to search user managers', { error })
	} finally {
		loading.value = false
	}
}
</script>
