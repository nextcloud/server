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
				<NcLoadingIcon v-if="loadingGroups || loadingAddGroup" />
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

		<NcAppNavigationList class="account-management__group-list"
			aria-describedby="group-list-desc"
			data-cy-users-settings-navigation-groups="custom">
			<p id="group-list-desc" class="hidden-visually">
				{{ t('settings', 'List of groups. This list is not fully populated on initial page load for performance reasons. The groups will be loaded as you navigate through the list.') }}
			</p>
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
import { computed, ref, watch, onBeforeMount } from 'vue'
import { Fragment } from 'vue-frag'
import { useRoute, useRouter } from 'vue-router/composables'
import { useElementVisibility } from '@vueuse/core'
import { showError } from '@nextcloud/dialogs'
import { mdiAccountGroup, mdiPlus } from '@mdi/js'

import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import NcActionText from '@nextcloud/vue/components/NcActionText'
import NcAppNavigationCaption from '@nextcloud/vue/components/NcAppNavigationCaption'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationSearch from '@nextcloud/vue/components/NcAppNavigationSearch'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

import GroupListItem from './GroupListItem.vue'

import { useFormatGroups } from '../composables/useGroupsNavigation'
import { useStore } from '../store'
import logger from '../logger.ts'

interface Group {
		id: string
    displayname: string
    usercount: number
    disabled: number
    canAdd: boolean
    canRemove: boolean
}

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
const groups = computed(() => store.getters.getSortedGroups)
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

/** Search query for groups */
const groupsSearchQuery = ref('')
/** Groups filtered by search query */
const filteredGroups = computed(() => userGroups.value.filter((group) => {
	const displayName = group.title
	return displayName.toLocaleLowerCase().includes(groupsSearchQuery.value.toLocaleLowerCase())
}))

/** Search offset */
const offset = ref(0)
/** True if paginated groups are loading */
const loadingGroups = ref(false)

watch(groupsSearchQuery, async () => {
	store.commit('resetGroups')
	offset.value = 0
	await loadGroups()
})

const groupListItems = ref([])
const lastGroupListItem = computed(() => {
	return groupListItems.value.at(-1)?.$el?.frag?.find(node => node instanceof HTMLLIElement) // GroupListItem uses a Fragment root so we find the actual list element
})

const isLastGroupVisible = useElementVisibility(lastGroupListItem)
watch(isLastGroupVisible, async () => {
	if (!isLastGroupVisible.value) {
		return
	}
	await loadGroups()
})

/**
 * Load groups
 */
async function loadGroups() {
	loadingGroups.value = true
	try {
		const { data } = await store.dispatch('searchGroups', {
			search: groupsSearchQuery.value,
			offset: offset.value,
			limit: 25,
		})
		const groups: Group[] = data.ocs?.data?.groups ?? []
		if (groups.length > 0) {
			offset.value += 25
		}
		for (const group of groups) {
			store.commit('addGroup', {
				id: group.id,
				name: group.displayname,
				usercount: group.usercount,
				disabled: group.disabled,
				canAdd: group.canAdd,
				canRemove: group.canRemove,
			})
		}
	} catch (error) {
		logger.error(t('settings', 'Failed to load groups'), { error })
	}
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
		newGroupName.value = ''
	} catch {
		showError(t('settings', 'Failed to create group'))
	}
	loadingAddGroup.value = false
}
</script>
