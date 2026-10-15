<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { useDebounceFn } from '@vueuse/core'
import { computed, ref } from 'vue'
import NcSelectUsers from '@nextcloud/vue/components/NcSelectUsers'
import { mapGroupToUserData, useGroups, useUsers } from '../../composables/useEntities.ts'

type IUserData = InstanceType<typeof NcSelectUsers>['$props']['options'][number]

const groups = defineModel<string[]>('groups', { default: () => [] })
const users = defineModel<string[]>('users', { default: () => [] })

const entities = ref<IUserData[]>([])
const selectedUsers = useUsers(users)
const selectedGroups = useGroups(groups)

const model = computed({
	get() {
		return [...selectedGroups.value, ...selectedUsers.value]
	},
	set(value: IUserData[]) {
		users.value = value.filter((u) => u.user).map((u) => u.user!)
		groups.value = value.filter((g) => g.isNoUser).map((g) => g.id)
	},
})

const debouncedSearch = useDebounceFn(onSearch, 500)

/**
 * Handle searching for users and groups
 *
 * @param pattern - The pattern to search
 */
async function onSearch(pattern: string) {
	const { data } = await axios.get<{ groups: Record<string, string>, users: Record<string, string> }>(
		generateUrl('apps/files_external/ajax/applicable'),
		{ params: { pattern, limit: 20 } },
	)

	const newEntries = [
		...entities.value.map((e) => [e.id, e]),
		...Object.entries(data.groups)
			.map(([id, displayName]) => [id, { ...mapGroupToUserData(id), displayName }]),
		...Object.entries(data.users)
			.map(([id, displayName]) => [`user:${id}`, { id: `user:${id}`, user: id, displayName }]),
	] as [string, IUserData][]

	entities.value = [...new Map(newEntries).values()]
}
</script>

<template>
	<NcSelectUsers
		v-model="model"
		keepOpen
		multiple
		:options="entities"
		:inputLabel="t('files_external', 'Restrict to')"
		@search="debouncedSearch" />
</template>
