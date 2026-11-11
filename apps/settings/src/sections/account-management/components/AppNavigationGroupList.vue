<!--
	- SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
	- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ComponentPublicInstance } from 'vue'

import { mdiAccountGroupOutline, mdiPlus } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { useElementVisibility } from '@vueuse/core'
import { computed, nextTick, onBeforeMount, ref, shallowReactive, watch } from 'vue'
import { useRouter } from 'vue-router'
import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import NcActionText from '@nextcloud/vue/components/NcActionText'
import NcAppNavigationCaption from '@nextcloud/vue/components/NcAppNavigationCaption'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import GroupListItem from './GroupListItem.vue'
import logger from '../../../logger.ts'
import { useGroupSearch } from '../composables/useGroupSearch.ts'
import { groupRouteParam, useFormatGroups, useSelectedGroup } from '../composables/useGroupsNavigation.ts'
import { useUsersStore } from '../store/users.ts'

const emit = defineEmits<{
	/** A group was picked */
	select: []
}>()

const usersStore = useUsersStore()
const router = useRouter()

onBeforeMount(loadGroups)

/** The group or internal view (e.g. `disabled`) that is shown, null for all accounts */
const selectedGroup = useSelectedGroup()
/** Server settings for current user */
const settings = computed(() => usersStore.serverData)
/** True if the current user is a (delegated) admin */
const isAdminOrDelegatedAdmin = computed(() => settings.value.isAdmin || settings.value.isDelegatedAdmin)
/** All available groups */
const groups = computed(() => {
	return isAdminOrDelegatedAdmin.value
		? usersStore.sortedGroups
		: usersStore.subAdminGroups
})
/** User groups */
const { userGroups } = useFormatGroups(groups)

/** Open state handed to the 'add-group' menu, the caption does not report changes back */
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
/** Search query — shared via the store */
const groupsSearchQuery = computed(() => usersStore.searchQuery)
const filteredGroups = computed(() => {
	if (isAdminOrDelegatedAdmin.value) {
		return userGroups.value
	}

	const substring = groupsSearchQuery.value.toLowerCase()
	return userGroups.value.filter((group) => group.id.toLowerCase().search(substring) !== -1 || group.title.toLowerCase().search(substring) !== -1)
})

/** Elements of the rendered group items, by group id */
const groupItemElements = shallowReactive(new Map<string, HTMLElement>())

/**
 * @param groupId Id of the group the item shows
 * @param item The rendered item, null once it is removed
 */
function setGroupItemRef(groupId: string, item: Element | ComponentPublicInstance | null) {
	const element = (item as InstanceType<typeof GroupListItem> | null)?.listItem?.$el
	if (element) {
		groupItemElements.set(groupId, element)
	} else {
		groupItemElements.delete(groupId)
	}
}

const lastGroupListItem = computed(() => groupItemElements.get(userGroups.value.at(-1)?.id ?? ''))
const isLastGroupVisible = useElementVisibility(lastGroupListItem)
watch(isLastGroupVisible, (visible) => {
	if (visible) {
		loadGroups()
	}
})

watch(groupsSearchQuery, async () => {
	usersStore.resetGroups()
	offset.value = 0
	await loadGroups()
})

const searchLatestGroups = useGroupSearch()

/**
 * Load groups
 */
async function loadGroups() {
	if (!isAdminOrDelegatedAdmin.value) {
		return
	}

	loadingGroups.value = true
	try {
		const groups = await searchLatestGroups({
			search: groupsSearchQuery.value,
			offset: offset.value,
			limit: 25,
		})
		if (groups === null) {
			// a newer search took over and resets the state itself
			return
		}
		if (groups.length > 0) {
			offset.value += 25
		}
		for (const group of groups) {
			usersStore.addGroup(group)
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

	// NcAppNavigationCaption (nc-vue 9) forwards `open` but not `update:open`,
	// so toggle it to make the menu close; use v-model:open once nc-vue forwards it
	isAddGroupOpen.value = true
	await nextTick()
	isAddGroupOpen.value = false
	loadingAddGroup.value = true

	try {
		await usersStore.createGroup(groupId)
		await router.push({
			name: 'group',
			params: {
				selectedGroup: groupRouteParam(groupId),
			},
		})
		groupItemElements.get(groupId)?.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
		newGroupName.value = ''
	} catch (error) {
		// the store already shows the error
		logger.debug('Could not create the group', { error })
	}
	loadingAddGroup.value = false
}
</script>

<template>
	<p id="group-list-desc" class="hidden-visually">
		{{ t('settings', 'List of groups. This list is not fully populated for performance reasons. The groups will be loaded as you navigate or search through the list.') }}
	</p>
	<NcAppNavigationList
		:aria-label="t('settings', 'Groups')"
		aria-describedby="group-list-desc"
		data-cy-users-settings-navigation-groups="custom">
		<NcAppNavigationCaption
			:open="isAddGroupOpen"
			:name="t('settings', 'Groups')"
			:disabled="loadingAddGroup"
			:aria-label="loadingAddGroup ? t('settings', 'Creating group…') : t('settings', 'Create group')"
			forceMenu>
			<template v-if="isAdminOrDelegatedAdmin" #actionsTriggerIcon>
				<NcLoadingIcon v-if="loadingAddGroup" />
				<NcIconSvgWrapper v-else :path="mdiPlus" />
			</template>
			<template v-if="isAdminOrDelegatedAdmin" #actions>
				<NcActionText>
					<template #icon>
						<NcIconSvgWrapper :path="mdiAccountGroupOutline" />
					</template>
					{{ t('settings', 'Create group') }}
				</NcActionText>
				<NcActionInput
					v-model="newGroupName"
					:label="t('settings', 'Group name')"
					data-cy-users-settings-new-group-name
					:labelOutside="false"
					:disabled="loadingAddGroup"
					:error="hasAddGroupError"
					:helperText="hasAddGroupError ? t('settings', 'Please enter a valid group name') : ''"
					@submit="createGroup" />
			</template>
		</NcAppNavigationCaption>
		<GroupListItem
			v-for="group in filteredGroups"
			:id="group.id"
			:key="group.id"
			:ref="(item) => setGroupItemRef(group.id, item)"
			:active="selectedGroup === group.id"
			:name="group.title"
			:count="group.count"
			@select="emit('select')" />
		<div v-if="loadingGroups" role="note">
			<NcLoadingIcon :name="t('settings', 'Loading groups…')" />
		</div>
	</NcAppNavigationList>
</template>
