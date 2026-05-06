<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'

import { t } from '@nextcloud/l10n'
import { NcLoadingIcon } from '@nextcloud/vue'
import { useDebounceFn } from '@vueuse/core'
import { computed, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcSelectUsers, { type NcSelectUsersModel } from '@nextcloud/vue/components/NcSelectUsers'
import { useAppsStore } from '../store/apps.ts'
import { useGroupsStore } from '../store/groups.ts'

const { app } = defineProps<{ app: IAppstoreApp | IAppstoreExApp }>()
const emit = defineEmits<{ close: [] }>()

const store = useAppsStore()
const groupsStore = useGroupsStore()

const loading = ref(false)
const groups = ref<NcSelectUsersModel[]>([])
watch(() => app, () => {
	groups.value = (app.groups ?? [])
		.map((g) => {
			const group = groupsStore.getGroupById(g)
			if (!group) {
				groupsStore.searchGroups(g)
			}
			return group ?? { id: g, displayName: g, isNoUser: true }
		})
}, { immediate: true })

const availableGroups = computed(() => groupsStore.groups.filter((group) => !groups.value.includes(group)))
const onSearch = useDebounceFn(groupsStore.searchGroups, 400)

/**
 * Save the limitation of this app
 */
async function onSave() {
	try {
		loading.value = true
		await store.limitAppToGroups(app.id, groups.value.map((g) => g.id))
		emit('close')
	} finally {
		loading.value = false
	}
}

/**
 * Handle reset
 */
async function onReset() {
	try {
		loading.value = true
		await store.limitAppToGroups(app.id, [])
		emit('close')
	} finally {
		loading.value = false
	}
}
</script>

<template>
	<NcDialog
		isForm
		:name="t('appstore', 'Limit to groups')"
		@submit="onSave"
		@reset="onReset">
		<p>{{ t('appstore', 'Restrict the usage of {app} to members of the following groups.', { app: app.name }) }}</p>
		<NcSelectUsers
			v-model="groups"
			:class="$style.limitToGroupDialog__input"
			keepOpen
			labelOutside
			multiple
			:options="availableGroups"
			@search="onSearch" />

		<template #actions>
			<NcButton :disabled="loading" type="reset">
				{{ t('appstore', 'Reset limitation') }}
			</NcButton>
			<NcButton :disabled="loading" type="submit" variant="primary">
				<template v-if="loading" #icon>
					<NcLoadingIcon />
				</template>

				{{ t('appstore', 'Save') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<style module>
.limitToGroupDialog__input {
	width: 100%;
	padding-block: 1lh calc(2 * var(--default-clickable-area) + var(--default-grid-baseline));
}
</style>
