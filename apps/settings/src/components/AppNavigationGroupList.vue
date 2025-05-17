<!--
	- SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
	- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Fragment>
		<NcAppNavigationCaption :name="t('settings', 'Groups')"
			:disabled="loadingAddGroup"
			:aria-label="loadingAddGroup ? t('settings', 'Creating group…') : t('settings', 'Create group')"
			force-menu
			is-heading
			:open.sync="isAddGroupOpen">
			<template v-if="isAdminOrDelegatedAdmin" #actionsTriggerIcon>
				<NcLoadingIcon v-if="loadingAddGroup" />
				<NcIconSvgWrapper v-else :path="mdiPlus" />
			</template>
			<template v-if="isAdminOrDelegatedAdmin" #actions>
				<NcActionText>
					<template #icon>
						<NcIconSvgWrapper :path="mdiAccountGroup" />
					</template>
					{{ t('settings', 'Create group') }}
				</NcActionText>
				<NcActionInput :label="t('settings', 'Group name')"
					data-cy-users-settings-new-group-name
					:label-outside="false"
					:disabled="loadingAddGroup"
					:value.sync="newGroupName"
					:error="hasAddGroupError"
					:helper-text="hasAddGroupError ? t('settings', 'Please enter a valid group name') : ''"
					@submit="createGroup" />
			</template>
		</NcAppNavigationCaption>

		<NcAppNavigationSearch v-model="groupsSearchQuery"
			:label="t('settings', 'Search groups…')" />

		<p id="group-list-desc" class="hidden-visually">
			{{ t('settings', 'List of groups. This list is not fully populated for performance reasons. The groups will be loaded as you navigate or search through the list.') }}
		</p>
		<NcAppNavigationList class="account-management__group-list"
			aria-describedby="group-list-desc"
			data-cy-users-settings-navigation-groups="custom">
			<GroupListItem v-for="group in filteredGroups"
				:id="group.id"
				ref="groupListItems"
				:key="group.id"
				:active="selectedGroupDecoded === group.id"
				:name="group.title"
				:count="group.count" />
			<div v-if="loadingGroups" role="note">
				<NcLoadingIcon :name="t('settings', 'Loading groups…')" />
			</div>
		</NcAppNavigationList>
	</Fragment>
</template>

<script setup lang="ts">
import type CancelablePromise from 'cancelable-promise'
import type { IGroup } from '../views/user-types.d.ts'

import { mdiAccountGroup, mdiPlus } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { useElementVisibility } from '@vueuse/core'
import { computed, ref, watch, onBeforeMount } from 'vue'
import { Fragment } from 'vue-frag'
import { useRoute, useRouter } from 'vue-router/composables'

import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import NcActionText from '@nextcloud/vue/components/NcActionText'
import NcAppNavigationCaption from '@nextcloud/vue/components/NcAppNavigationCaption'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationSearch from '@nextcloud/vue/components/NcAppNavigationSearch'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

import GroupListItem from './GroupListItem.vue'

import { useFormatGroups } from '../composables/useGroupsNavigation.ts'
import { useStore } from '../store'
import { searchGroups } from '../service/groups.ts'
import logger from '../logger.ts'

const store = useStore()
const route = useRoute()
const router = useRouter()

onBeforeMount(async () => {
	await loadGroups()
})

/** Current active group in the view - this is URL encoded */
const selectedGroup = computed(() => route.params?.selectedGroup)
/** Current active group - URL decoded  */
const selectedGroupDecoded = computed(() => selectedGroup.value ? decodeURIComponent(selectedGroup.value) : null)
/** All available groups */
const groups = computed(() => {
	return isAdminOrDelegatedAdmin.value
		? store.getters.getSortedGroups
		: store.getters.getSubAdminGroups
})
/** User groups */
const { userGroups } = useFormatGroups(groups)
/** Server settings for current user */
const settings = computed(() => store.getters.getServerData)
/** True if the current user is a (delegated) admin */
const isAdminOrDelegatedAdmin = computed(() => settings.value.isAdmin || settings.value.isDelegatedAdmin)

/** True if the 'add-group' dialog is open - needed to be able to close it when the group is created */
const isAddGroupOpen = ref(false)
/** True if the group creation is in progress to show loading spinner and disable adding another one */
const loadingAddGroup = ref(false)
/** Error state for creating a new group */
const hasAddGroupError = ref(false)
/** Name of the group to create (used in the group creation dialog) */
const newGroupName = ref('')

/** True if groups are loading */
const loadingGroups = ref(false)
/** Search offset */
const offset = ref(0)
/** Search query for groups */
const groupsSearchQuery = ref('')
const filteredGroups = computed(() => {
	if (isAdminOrDelegatedAdmin.value) {
		return userGroups.value
	}

	const substring = groupsSearchQuery.value.toLowerCase()
	return userGroups.value.filter(group => group.id.toLowerCase().search(substring) !== -1 || group.title.toLowerCase().search(substring) !== -1)
})

const groupListItems = ref([])
const lastGroupListItem = computed(() => {
	return groupListItems.value
		.findLast(component => component?.$vnode?.key === userGroups.value?.at(-1)?.id) // Order of refs is not guaranteed to match source array order
		?.$refs?.listItem?.$el
})
const isLastGroupVisible = useElementVisibility(lastGroupListItem)
watch(isLastGroupVisible, async () => {
	if (!isLastGroupVisible.value) {
		return
	}
	await loadGroups()
})

watch(groupsSearchQuery, async () => {
	store.commit('resetGroups')
	offset.value = 0
	await loadGroups()
})

/** Cancelable promise for search groups request */
const promise = ref<CancelablePromise<IGroup[]>>()

/**
 * Load groups
 */
async function loadGroups() {
	if (!isAdminOrDelegatedAdmin.value) {
		return
	}

	if (promise.value) {
		promise.value.cancel()
	}
	loadingGroups.value = true
	try {
		promise.value = searchGroups({
			search: groupsSearchQuery.value,
			offset: offset.value,
			limit: 25,
		})
		const groups = await promise.value
		if (groups.length > 0) {
			offset.value += 25
		}
		for (const group of groups) {
			store.commit('addGroup', group)
		}
	} catch (error) {
		logger.error(t('settings', 'Failed to load groups'), { error })
	}
	promise.value = undefined
	loadingGroups.value = false
}

/**
 * Create a new group
 */
async function createGroup() {
	hasAddGroupError.value = false
	const groupId = newGroupName.value.trim()
	if (groupId === '') {
		hasAddGroupError.value = true
		return
	}

	isAddGroupOpen.value = false
	loadingAddGroup.value = true

	try {
		await store.dispatch('addGroup', groupId)
		await router.push({
			name: 'group',
			params: {
				selectedGroup: encodeURIComponent(groupId),
			},
		})
		const newGroupListItem = groupListItems.value.findLast(component => component?.$vnode?.key === groupId)
		newGroupListItem?.$refs?.listItem?.$el?.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
		newGroupName.value = ''
	} catch {
		showError(t('settings', 'Failed to create group'))
	}
	loadingAddGroup.value = false
}
</script>
