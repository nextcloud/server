<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user-form-groups">
		<div class="user-form__item">
			<NcSelect
				v-model="formData.groups"
				class="user-form__select"
				data-test="groups"
				:inputLabel="groupsLabel"
				:placeholder="t('settings', 'Set account groups')"
				:disabled="creatingGroup"
				:options="availableGroups"
				label="name"
				keepOpen
				:multiple="true"
				:taggable="settings.isAdmin || settings.isDelegatedAdmin"
				:required="!settings.isAdmin && !settings.isDelegatedAdmin"
				:createOption="(value) => ({ id: value, name: value, isCreating: true })"
				@search="searchGroups"
				@option:created="createGroup" />
		</div>

		<div
			v-if="settings.isAdmin || settings.isDelegatedAdmin"
			class="user-form__item">
			<NcSelect
				v-model="formData.subadminGroups"
				class="user-form__select"
				:inputLabel="t('settings', 'Admin of the following groups')"
				:placeholder="t('settings', 'Set account as admin for …')"
				:disabled="creatingGroup"
				:options="availableSubAdminGroups"
				keepOpen
				:multiple="true"
				label="name"
				@search="searchGroups" />
		</div>
	</div>
</template>

<script setup lang="ts">
import type { IGroup } from '../../views/user-types.d.ts'

import { translate as t } from '@nextcloud/l10n'
import { computed, inject, ref } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import logger from '../../logger.ts'
import { searchGroups as searchGroupsApi } from '../../service/groups.ts'
import { useStore } from '../../store/index.js'
import { formDataKey } from './injectionKeys.ts'
import { isSelectableGroup } from './userFormUtils.ts'

const store = useStore()

const formData = inject(formDataKey)!

const creatingGroup = ref(false)
// Kept so a new search can cancel the in-flight one.
let promise: ReturnType<typeof searchGroupsApi> | null = null

const settings = computed(() => store.getters.getServerData)

const availableGroups = computed(() => {
	const groups = (settings.value.isAdmin || settings.value.isDelegatedAdmin)
		? store.getters.getSortedGroups
		: store.getters.getSubAdminGroups

	return groups.filter(isSelectableGroup)
})

const availableSubAdminGroups = computed(() => availableGroups.value.filter(({ id }) => id !== 'admin'))

const groupsLabel = computed(() => !settings.value.isAdmin && !settings.value.isDelegatedAdmin
	? t('settings', 'Member of the following groups (required)')
	: t('settings', 'Member of the following groups'))

/**
 * Search groups from the backend and add them to the store.
 *
 * @param query The current search string
 * @param toggleLoading NcSelect callback to toggle its spinner
 */
async function searchGroups(query: string, toggleLoading: (loading: boolean) => void) {
	if (!settings.value.isAdmin && !settings.value.isDelegatedAdmin) {
		return
	}
	if (promise) {
		promise.cancel()
	}
	toggleLoading(true)
	try {
		promise = searchGroupsApi({ search: query, offset: 0, limit: 25 })
		const groups = await promise
		for (const group of groups) {
			store.commit('addGroup', group)
		}
	} catch (error) {
		logger.error(t('settings', 'Failed to search groups'), { error })
	}
	promise = null
	toggleLoading(false)
}

/**
 * Create a tagged group and add it to the selection.
 *
 * @param option The created NcSelect option
 * @param option.name The new group id/name
 */
async function createGroup({ name: gid }: { name: string }) {
	creatingGroup.value = true
	try {
		await store.dispatch('addGroup', gid)
		// A freshly tagged group has no member counts yet; the form only reads id/name.
		formData.groups.push({ id: gid, name: gid } as IGroup)
	} catch (error) {
		logger.error(t('settings', 'Failed to create group'), { error })
	}
	creatingGroup.value = false
}
</script>

<style lang="scss" scoped>
.user-form-groups {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline, 4px) * 2) 0;
	width: 100%;
}

.user-form__item {
	width: 100%;
}

.user-form__select {
	width: 100%;
}
</style>
