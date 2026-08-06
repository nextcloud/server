<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Fragment>
		<NewUserDialog
			v-if="showConfig.showNewUserForm"
			:loading="loading"
			:newUser="newUser"
			:quotaOptions="quotaOptions"
			@closing="closeDialog" />

		<EditUserDialog
			v-if="editingUser"
			:user="editingUser"
			:quotaOptions="quotaOptions"
			@closing="editingUser = null" />

		<NcEmptyContent
			v-if="filteredUsers.length === 0"
			class="empty"
			:name="loading.users ? undefined : t('settings', 'No accounts')">
			<template #icon>
				<NcLoadingIcon
					v-if="loading.users"
					:name="t('settings', 'Loading accounts …')"
					:size="64" />
				<NcIconSvgWrapper v-else :path="mdiAccountGroupOutline" :size="64" />
			</template>
		</NcEmptyContent>

		<VirtualList
			v-else
			:data-component="UserRow"
			:data-sources="filteredUsers"
			data-key="id"
			data-cy-user-list
			:itemHeight="rowHeight"
			:style="style"
			:extraProps="{
				users,
				settings,
				quotaOptions,
				languages,
				externalActions,
				onEditUser: openEditDialog,
			}"
			@scroll-end="handleScrollEnd">
			<template #before>
				<caption class="hidden-visually">
					{{ t('settings', 'List of accounts. This list is not fully rendered for performance reasons. The accounts will be rendered as you navigate through the list.') }}
				</caption>
			</template>

			<template #header>
				<UserListHeader />
			</template>

			<template #footer>
				<UserListFooter
					:loading="loading.users"
					:filteredUsers="filteredUsers" />
			</template>
		</VirtualList>
	</Fragment>
</template>

<script setup lang="ts">
import type { IUser } from '../views/user-types.d.ts'
import type { FormData } from './Users/userFormUtils.ts'

import { mdiAccountGroupOutline } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { computed, reactive, ref, watch } from 'vue'
import { Fragment } from 'vue-frag'
import { useRouter } from 'vue-router/composables'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import EditUserDialog from './Users/EditUserDialog.vue'
import NewUserDialog from './Users/NewUserDialog.vue'
import UserListFooter from './Users/UserListFooter.vue'
import UserListHeader from './Users/UserListHeader.vue'
import UserRow from './Users/UserRow.vue'
import VirtualList from './Users/VirtualList.vue'
import logger from '../logger.ts'
import { useStore } from '../store/index.js'
import { defaultQuota, unlimitedQuota } from '../utils/userUtils.ts'

const props = withDefaults(defineProps<{
	selectedGroup?: string | null
	externalActions?: { icon: string, text: string, action: (...args: unknown[]) => void }[]
}>(), {
	selectedGroup: null,
	externalActions: () => [],
})
const rowHeight = 55

const defaultNewUser = Object.freeze({
	username: '',
	displayName: '',
	password: '',
	email: '',
	groups: [],
	manager: '',
	subadminGroups: [],
	quota: defaultQuota,
	language: {
		code: 'en',
		name: t('settings', 'Default language'),
	},
})

const store = useStore()
const router = useRouter()

const loading = reactive({
	all: false,
	groups: false,
	users: false,
})
const newUser = reactive<FormData>({ ...defaultNewUser })
const editingUser = ref<IUser | null>(null)

const searchQuery = computed(() => store.getters.getSearchQuery)
const showConfig = computed(() => store.getters.getShowConfig)
const settings = computed(() => store.getters.getServerData)
const style = computed(() => ({ '--row-height': `${rowHeight}px` }))
const users = computed(() => store.getters.getUsers)

const filteredUsers = computed(() => {
	if (props.selectedGroup === 'disabled') {
		return users.value.filter((user) => user.enabled === false)
	}
	return users.value.filter((user) => user.enabled !== false)
})

const groups = computed(() => store.getters.getSortedGroups
	.filter((group) => group.id !== '__nc_internal_recent' && group.id !== 'disabled'))

const quotaOptions = computed(() => {
	// convert the preset array into objects
	const quotaPreset = settings.value.quotaPreset.reduce((acc, cur) => acc.concat({
		id: cur,
		label: cur,
	}), [])
	// add default presets
	if (settings.value.allowUnlimitedQuota) {
		quotaPreset.unshift(unlimitedQuota)
	}
	quotaPreset.unshift(defaultQuota)
	return quotaPreset
})

const usersOffset = computed(() => store.getters.getUsersOffset)
const usersLimit = computed(() => store.getters.getUsersLimit)
const disabledUsersOffset = computed(() => store.getters.getDisabledUsersOffset)
const disabledUsersLimit = computed(() => store.getters.getDisabledUsersLimit)

/* LANGUAGES */
const languages = computed(() => [
	{
		label: t('settings', 'Common languages'),
		languages: settings.value.languages.commonLanguages,
	},
	{
		label: t('settings', 'Other languages'),
		languages: settings.value.languages.otherLanguages,
	},
])

watch(searchQuery, async () => {
	store.commit('resetUsers')
	await loadUsers()
})

// watch url change and group select
watch(() => props.selectedGroup, async (val) => {
	// if selected is the disabled group but it's empty
	await redirectIfDisabled()
	store.commit('resetUsers')
	await loadUsers()
	setNewUserDefaultGroup(val)
})

watch(filteredUsers, (value) => {
	logger.debug(`${value.length} filtered user(s)`)
})

/**
 * Open the edit dialog for a user.
 *
 * @param user The user to edit
 */
function openEditDialog(user: IUser) {
	editingUser.value = user
}

/**
 * Load the next page when the list scrolls to the end.
 */
async function handleScrollEnd() {
	await loadUsers()
}

/**
 * Load accounts for the current selection (disabled, recent, or a group).
 */
async function loadUsers() {
	loading.users = true
	try {
		if (props.selectedGroup === 'disabled') {
			await store.dispatch('getDisabledUsers', {
				offset: disabledUsersOffset.value,
				limit: disabledUsersLimit.value,
				search: searchQuery.value,
			})
		} else if (props.selectedGroup === '__nc_internal_recent') {
			await store.dispatch('getRecentUsers', {
				offset: usersOffset.value,
				limit: usersLimit.value,
				search: searchQuery.value,
			})
		} else {
			await store.dispatch('getUsers', {
				offset: usersOffset.value,
				limit: usersLimit.value,
				group: props.selectedGroup,
				search: searchQuery.value,
			})
		}
		logger.debug(`${users.value.length} total user(s) loaded`)
	} catch (error) {
		logger.error('Failed to load accounts', { error })
		showError('Failed to load accounts')
	}
	loading.users = false
}

/**
 * Close the new-account dialog and reset the form.
 */
function closeDialog() {
	store.dispatch('setShowConfig', {
		key: 'showNewUserForm',
		value: false,
	})
	resetForm()
}

/**
 * Reset the new user form to its initial state.
 * Uses in-place mutation (Object.assign + splice) so the
 * provide/inject reference stays intact.
 */
function resetForm() {
	Object.assign(newUser, {
		...defaultNewUser,
		groups: [],
		subadminGroups: [],
	})
	newUser.groups.splice(0)
	newUser.subadminGroups.splice(0)
	initForm()
}

/**
 * Initialise the new-account form defaults (language and group).
 */
function initForm() {
	// Set the default language directly (not via a computed) to keep the form's v-model binding intact.
	if (settings.value.defaultLanguage) {
		newUser.language.code = settings.value.defaultLanguage
	}
	setNewUserDefaultGroup(props.selectedGroup)
	loading.all = false
}

/**
 * Preselect the new account's group from the current selection or role.
 *
 * @param value The currently selected group id, or null
 */
function setNewUserDefaultGroup(value: string | null) {
	// Is no value set, but user is a line manager we set their group as this is a requirement for line manager
	if (!value && !settings.value.isAdmin && !settings.value.isDelegatedAdmin) {
		const subAdminGroups = store.getters.getSubAdminGroups
		// if there are multiple groups we do not know which to add,
		// so we cannot make the managers life easier by preselecting it.
		if (subAdminGroups.length === 1) {
			newUser.groups = [...subAdminGroups]
		}
		return
	}

	if (value) {
		// setting new account default group to the current selected one
		const currentGroup = groups.value.find((group) => group.id === value)
		if (currentGroup) {
			newUser.groups = [currentGroup]
			return
		}
	}
	// fallback, empty selected group
	newUser.groups = []
}

/**
 * If the selected group is the disabled group but the count is 0
 * redirect to the all users page.
 * we only check for 0 because we don't have the count on ldap
 * and we therefore set the usercount to -1 in this specific case
 */
async function redirectIfDisabled() {
	const allGroups = store.getters.getGroups
	if (props.selectedGroup === 'disabled'
		&& allGroups.findIndex((group) => group.id === 'disabled' && group.usercount === 0) > -1) {
		// disabled group is empty, redirection to all users
		router.push({ name: 'users' })
		await loadUsers()
	}
}

// Setup-body work runs once, unawaited, matching the original created/mounted lifecycle.
loadUsers()

if (!settings.value.canChangePassword) {
	window.OC.Notification.showTemporary(t('settings', 'Password change is disabled because the master key is disabled'))
}
initForm()
redirectIfDisabled()
</script>

<style lang="scss" scoped>
@use './Users/shared/styles.scss' as *;

.empty {
	:deep {
		.icon-vue {
			width: 64px;
			height: 64px;

			svg {
				max-width: 64px;
				max-height: 64px;
			}
		}
	}
}
</style>
