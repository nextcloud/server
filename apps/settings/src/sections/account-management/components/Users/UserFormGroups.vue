<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IGroup } from '../../../../views/user-types.d.ts'

import { t } from '@nextcloud/l10n'
import { computed, inject, ref } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { isSelectableGroup } from '../../../../components/Users/userFormUtils.ts'
import logger from '../../../../logger.ts'
import { useGroupSearch } from '../../composables/useGroupSearch.ts'
import { useUsersStore } from '../../store/users.ts'
import { formDataKey } from './injectionKeys.ts'

const usersStore = useUsersStore()

const formData = inject(formDataKey)!

const creatingGroup = ref(false)

const settings = computed(() => usersStore.serverData)

const availableGroups = computed(() => {
	const groups = (settings.value.isAdmin || settings.value.isDelegatedAdmin)
		? usersStore.sortedGroups
		: usersStore.subAdminGroups

	return groups.filter(isSelectableGroup)
})

const availableSubAdminGroups = computed(() => availableGroups.value.filter(({ id }) => id !== 'admin'))

const groupsLabel = computed(() => !settings.value.isAdmin && !settings.value.isDelegatedAdmin
	? t('settings', 'Member of the following groups (required)')
	: t('settings', 'Member of the following groups'))

/**
 * Create the search handler of one select, each select needs its own so
 * their searches don't abort each other.
 */
function createGroupSearchHandler() {
	const searchLatestGroups = useGroupSearch()

	/**
	 * Search groups from the backend and add them to the store.
	 *
	 * @param query The current search string
	 * @param toggleLoading NcSelect callback to toggle its spinner
	 */
	return async function searchGroups(query: string, toggleLoading: (loading: boolean) => void) {
		if (!settings.value.isAdmin && !settings.value.isDelegatedAdmin) {
			return
		}
		toggleLoading(true)
		try {
			const groups = await searchLatestGroups({ search: query, offset: 0, limit: 25 })
			if (groups === null) {
				// a newer search took over and resets the state itself
				return
			}
			for (const group of groups) {
				usersStore.addGroup(group)
			}
		} catch (error) {
			logger.error(t('settings', 'Failed to search groups'), { error })
		}
		toggleLoading(false)
	}
}

const searchMemberGroups = createGroupSearchHandler()
const searchSubAdminGroups = createGroupSearchHandler()

/**
 * Create a tagged group and add it to the selection.
 *
 * @param option The created NcSelect option
 * @param option.name The new group id/name
 */
async function createGroup({ name: gid }: { name: string }) {
	creatingGroup.value = true
	try {
		await usersStore.createGroup(gid)
		// A freshly tagged group has no member counts yet; the form only reads id/name.
		formData.groups.push({ id: gid, name: gid } as IGroup)
	} catch (error) {
		logger.error(t('settings', 'Failed to create group'), { error })
	}
	creatingGroup.value = false
}
</script>

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
				@search="searchMemberGroups"
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
				@search="searchSubAdminGroups" />
		</div>
	</div>
</template>

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
