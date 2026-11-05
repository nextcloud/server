/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IGroup, IUser } from '../views/user-types.d.ts'

import axios from '@nextcloud/axios'
import { getCapabilities } from '@nextcloud/capabilities'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'
import { GroupSorting } from '../constants/GroupManagement.ts'
import logger from '../logger.ts'
import { naturalCollator } from '../utils/sorting.ts'
import api from './api.js'

/** The parts of the `usersSettings` initial state the store itself reads. */
interface UsersSettings {
	getSubAdminGroups?: IGroup[]
	systemGroups?: IGroup[]
	subAdminGroups?: IGroup[]
	sortGroups?: GroupSorting
	userCount?: number
	showConfig?: Record<string, boolean>
}

/** The full `usersSettings` initial state; loose until it has a type of its own */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type ServerData = Record<string, any>

/** Database keys of the user list column preferences */
const showConfigPreferenceKeys = {
	showStoragePath: 'user_list_show_storage_path',
	showUserBackend: 'user_list_show_user_backend',
	showFirstLogin: 'user_list_show_first_login',
	showLastLogin: 'user_list_show_last_login',
	showNewUserForm: 'user_list_show_new_user_form',
	showLanguages: 'user_list_show_languages',
} as const

type ShowConfig = Record<keyof typeof showConfigPreferenceKeys, boolean>

interface UsersState {
	users: IUser[]
	groups: IGroup[]
	orderBy: GroupSorting
	minPasswordLength: number
	usersOffset: number
	usersLimit: number
	disabledUsersOffset: number
	disabledUsersLimit: number
	searchQuery: string
	userCount: number
	showConfig: ShowConfig
	serverData: ServerData
}

type UserCountAction = 'enable' | 'disable' | 'create' | 'remove'

const usersSettings = loadState<UsersSettings>('settings', 'usersSettings', {})

const defaultGroup: IGroup = {
	id: '',
	name: '',
	usercount: 0,
	disabled: 0,
	canAdd: true,
	canRemove: true,
}

let searchRequestCancelSource: ReturnType<typeof axios.CancelToken.source> | null = null

/**
 * Show and log a failed request.
 *
 * @param payload The request error, or an object holding it as `error`; only the latter shows the server's message
 */
function handleApiFailure(payload: unknown) {
	try {
		const message = (payload as { error: { response: { data: { ocs: { meta: { message: string } } } } } }).error.response.data.ocs.meta.message
		showError(t('settings', 'An error occurred during the request. Unable to proceed.') + '<br>' + message, { isHTML: true })
	} catch {
		showError(t('settings', 'An error occurred during the request. Unable to proceed.'))
	}
	logger.error('An error occurred during the request.', { error: payload })
}

/**
 * @param state The store state
 * @param usersObj Users keyed by id, as returned by the OCS user details endpoints
 */
function appendUsers(state: UsersState, usersObj: Record<string, IUser>) {
	const existingUsers = state.users.map(({ id }) => id)
	const newUsers = Object.values(usersObj)
		.filter(({ id }) => !existingUsers.includes(id))

	const users = state.users.concat(newUsers)
	state.usersOffset += state.usersLimit
	state.users = users
}

/** A response that may carry a `Link` header to the next page */
type LinkedResponse = { headers?: Record<string, unknown> }

/**
 * Get the offset of the next page from the `Link: <url>; rel="next"` response header.
 *
 * @param response axios response
 * @return The offset, or null if there is no next page
 */
function getNextPageOffset(response: LinkedResponse): number | null {
	const link = response.headers?.link
	const url = typeof link === 'string' ? link.match(/<([^>]+)>;\s*rel="next"/)?.[1] : undefined
	const offset = url ? new URL(url).searchParams.get('offset') : null
	return offset === null ? null : Number(offset)
}

/**
 * Build the `enabled` query parameter filtering users by their enabled state.
 *
 * @param enabled The enabled state to filter by, or null for all users
 */
function getEnabledParam(enabled: boolean | null): string {
	return enabled === null ? '' : `&enabled=${enabled ? 1 : 0}`
}

/**
 * Continue from the next page offset given by the server, as it may have skipped users.
 *
 * @param state The store state
 * @param response axios response
 */
function applyNextPageOffset(state: UsersState, response: LinkedResponse) {
	const nextOffset = getNextPageOffset(response)
	if (nextOffset !== null) {
		state.usersOffset = nextOffset
	}
}

/**
 * Update active/disabled counts, groups counts
 *
 * @param state The store state
 * @param user The affected user
 * @param actionType What happened to the user
 */
function updateUserCounts(state: UsersState, user: IUser, actionType: UserCountAction) {
	// 0 is a special value
	if (state.userCount === 0) {
		return
	}

	const recentGroup = state.groups.find((group) => group.id === '__nc_internal_recent')!
	const disabledGroup = state.groups.find((group) => group.id === 'disabled')!
	switch (actionType) {
		case 'enable':
		case 'disable':
			disabledGroup.usercount += user.enabled ? -1 : 1 // update Disabled Users count
			recentGroup.usercount += user.enabled ? 1 : -1
			state.userCount += user.enabled ? 1 : -1 // update Active Users count
			user.groups.forEach((userGroup) => {
				const group = state.groups.find((groupSearch) => groupSearch.id === userGroup)
				if (!group) {
					return
				}
				group.disabled += user.enabled ? -1 : 1 // update group disabled count
			})
			break
		case 'create':
			recentGroup.usercount++
			state.userCount++ // increment Active Users count

			user.groups.forEach((userGroup) => {
				const group = state.groups.find((groupSearch) => groupSearch.id === userGroup)
				if (!group) {
					return
				}
				group.usercount++ // increment group total count
			})
			break
		case 'remove':
			if (user.enabled) {
				recentGroup.usercount--
				state.userCount-- // decrement Active Users count
				user.groups.forEach((userGroup) => {
					const group = state.groups.find((groupSearch) => groupSearch.id === userGroup)
					if (!group) {
						logger.warn('User group ' + userGroup + ' does not exist during user removal')
						return
					}
					group.usercount-- // decrement group total count
				})
			} else {
				disabledGroup.usercount-- // decrement Disabled Users count
				user.groups.forEach((userGroup) => {
					const group = state.groups.find((groupSearch) => groupSearch.id === userGroup)
					if (!group) {
						return
					}
					group.disabled-- // decrement group disabled count
				})
			}
			break
	}
}

/**
 * @param state The store state
 * @param userid The user to add to the group
 * @param gid The group id
 */
function addUserGroup(state: UsersState, userid: string, gid: string) {
	const group = state.groups.find((groupSearch) => groupSearch.id === gid)
	const user = state.users.find((user) => user.id === userid)!
	// increase count if user is enabled
	if (group && user.enabled && state.userCount > 0) {
		group.usercount++
	}
	const groups = user.groups
	groups.push(gid)
}

/**
 * @param state The store state
 * @param userid The user to remove from the group
 * @param gid The group id
 */
function removeUserGroup(state: UsersState, userid: string, gid: string) {
	const group = state.groups.find((groupSearch) => groupSearch.id === gid)
	const user = state.users.find((user) => user.id === userid)!
	// lower count if user is enabled
	if (group && user.enabled && state.userCount > 0) {
		group.usercount--
	}
	const groups = user.groups
	groups.splice(groups.indexOf(gid), 1)
}

/**
 * Groups known before the first request
 */
function initialGroups(): IGroup[] {
	return [
		...(usersSettings.getSubAdminGroups ?? []),
		...(usersSettings.systemGroups ?? []),
	]
}

export const useUsersStore = defineStore('settings-users', {
	state: (): UsersState => ({
		users: [],
		groups: initialGroups(),
		orderBy: usersSettings.sortGroups ?? GroupSorting.UserCount,
		minPasswordLength: (getCapabilities() as { password_policy?: { minLength?: number } }).password_policy?.minLength || 0,
		usersOffset: 0,
		usersLimit: 25,
		disabledUsersOffset: 0,
		disabledUsersLimit: 25,
		searchQuery: '',
		userCount: usersSettings.userCount ?? 0,
		showConfig: Object.fromEntries(Object.entries(showConfigPreferenceKeys)
			.map(([key, dbKey]) => [key, usersSettings.showConfig?.[dbKey]])) as ShowConfig,
		serverData: loadState('settings', 'usersSettings', {}),
	}),

	getters: {
		sortedGroups(state): IGroup[] {
			const groups = [...state.groups]
			if (state.orderBy === GroupSorting.UserCount) {
				return groups.sort((a, b) => {
					const numA = a.usercount - a.disabled
					const numB = b.usercount - b.disabled
					return (numA < numB) ? 1 : (numB < numA ? -1 : naturalCollator.compare(a.name, b.name))
				})
			} else {
				return groups.sort((a, b) => naturalCollator.compare(a.name, b.name))
			}
		},

		subAdminGroups(): IGroup[] {
			return usersSettings.subAdminGroups ?? []
		},
	},

	actions: {
		/**
		 * Add a group to the local list, or merge new details into a known one
		 *
		 * @param newGroup The group, missing details are filled with defaults
		 */
		addGroup(newGroup: Partial<IGroup> & Pick<IGroup, 'id'>) {
			try {
				const existingGroup = this.groups.find((group) => group.id === newGroup.id)
				if (existingGroup) {
					// merge in whatever is provided, e.g. to upgrade a stub group with full details
					Object.assign(existingGroup, newGroup)
					return
				}
				// extend group to default values
				const group = { ...defaultGroup, ...newGroup }
				this.groups.unshift(group)
			} catch (error) {
				logger.error('Cannt create group', { error })
			}
		},

		/**
		 * Reset users list
		 */
		resetUsers() {
			this.users = []
			this.usersOffset = 0
			this.disabledUsersOffset = 0
		},

		/**
		 * Reset group list
		 */
		resetGroups() {
			this.groups = initialGroups()
		},

		/**
		 * search users
		 *
		 * @param options destructuring object
		 * @param options.offset List offset to request
		 * @param options.limit List number to return from offset
		 * @param options.search Search amongst users
		 */
		searchUsers({ offset, limit, search }: { offset: number, limit: number, search?: string }) {
			search = typeof search === 'string' ? search : ''

			return api.get(generateOcsUrl('cloud/users/details?offset={offset}&limit={limit}&search={search}', { offset, limit, search })).catch((error) => {
				if (!axios.isCancel(error)) {
					handleApiFailure(error)
				}
			})
		},

		/**
		 * Get all users with full details
		 *
		 * @param options destructuring object
		 * @param options.offset List offset to request
		 * @param options.limit List number to return from offset
		 * @param options.search Search amongst users
		 * @param options.group Get users from group
		 * @param options.enabled Only get users with this enabled state, or all users if null
		 * @return The number of users received
		 */
		getUsers({ offset, limit, search, group, enabled = null }: { offset: number, limit: number, search?: string, group?: string | null, enabled?: boolean | null }) {
			if (searchRequestCancelSource) {
				searchRequestCancelSource.cancel('Operation canceled by another search request.')
			}
			searchRequestCancelSource = axios.CancelToken.source()
			search = typeof search === 'string' ? search : ''
			group = typeof group === 'string' ? group : ''
			const url = (group !== ''
				? generateOcsUrl('cloud/groups/{group}/users/details?offset={offset}&limit={limit}&search={search}', { group: encodeURIComponent(group), offset, limit, search })
				: generateOcsUrl('cloud/users/details?offset={offset}&limit={limit}&search={search}', { offset, limit, search })) + getEnabledParam(enabled)

			return api.get(url, { cancelToken: searchRequestCancelSource.token })
				.then((response) => {
					const usersCount = Object.keys(response.data.ocs.data.users).length
					if (usersCount > 0) {
						appendUsers(this, response.data.ocs.data.users)
					}
					applyNextPageOffset(this, response)

					// The response only carries {id, displayname} (see AUserDataOCSController::findGroupsWithDisplayname),
					// so addGroup fills usercount/disabled/canAdd/canRemove with defaults. Trade-off: a group
					// only known through this path shows a wrong usercount until the sidebar loads full details.
					const groups: { id: string, displayname: string }[] = response.data.ocs.data.groups ?? []
					groups.forEach((group) => {
						this.addGroup({ id: group.id, name: group.displayname })
					})
					return usersCount
				})
				.catch((error) => {
					if (!axios.isCancel(error)) {
						handleApiFailure(error)
					}
				})
		},

		/**
		 * Get recent users with full details
		 *
		 * @param options destructuring object
		 * @param options.offset List offset to request
		 * @param options.limit List number to return from offset
		 * @param options.search Search query
		 * @param options.enabled Only get users with this enabled state, or all users if null
		 * @return The number of users received
		 */
		async getRecentUsers({ offset, limit, search, enabled = null }: { offset: number, limit: number, search: string, enabled?: boolean | null }) {
			const url = generateOcsUrl('cloud/users/recent?offset={offset}&limit={limit}&search={search}', { offset, limit, search })
				+ getEnabledParam(enabled)
			try {
				const response = await api.get(url)
				const usersCount = Object.keys(response.data.ocs.data.users).length
				if (usersCount > 0) {
					appendUsers(this, response.data.ocs.data.users)
				}
				applyNextPageOffset(this, response)
				return usersCount
			} catch (error) {
				handleApiFailure(error)
			}
		},

		/**
		 * Get disabled users with full details
		 *
		 * @param options destructuring object
		 * @param options.offset List offset to request
		 * @param options.limit List number to return from offset
		 * @param options.search Search query
		 * @return The number of users received
		 */
		async getDisabledUsers({ offset, limit, search }: { offset: number, limit: number, search: string }) {
			const url = generateOcsUrl('cloud/users/disabled?offset={offset}&limit={limit}&search={search}', { offset, limit, search })
			try {
				const response = await api.get(url)
				const usersCount = Object.keys(response.data.ocs.data.users).length
				if (usersCount > 0) {
					appendUsers(this, response.data.ocs.data.users)
					this.disabledUsersOffset += this.disabledUsersLimit
				}
				return usersCount
			} catch (error) {
				handleApiFailure(error)
			}
		},

		/**
		 * Create a group on the server and add it to the local list
		 *
		 * @param gid Group id
		 */
		createGroup(gid: string) {
			return api.requireAdmin().then(() => {
				return api.post(generateOcsUrl('cloud/groups'), { groupid: gid })
					.then(() => {
						this.addGroup({ id: gid, name: gid })
						return { gid, displayName: gid }
					})
			}).catch((error) => {
				handleApiFailure({ gid, error })
				// let's throw one more time to prevent the view
				// from adding the user to a group that doesn't exists
				throw error
			})
		},

		/**
		 * Rename group
		 *
		 * @param options destructuring object
		 * @param options.groupid Group id
		 * @param options.displayName Group display name
		 */
		renameGroup({ groupid, displayName }: { groupid: string, displayName: string }) {
			return api.requireAdmin().then(() => {
				return api.put(generateOcsUrl('cloud/groups/{groupId}', { groupId: encodeURIComponent(groupid) }), { key: 'displayname', value: displayName })
					.then(() => {
						const group = this.groups.find((groupSearch) => groupSearch.id === groupid)
						if (group) {
							group.name = displayName
						}
						return { groupid, displayName }
					})
			}).catch((error) => {
				handleApiFailure({ groupid, error })
				// let's throw one more time to prevent the view
				// from renaming the group
				throw error
			})
		},

		/**
		 * Remove group
		 *
		 * @param gid Group id
		 */
		removeGroup(gid: string) {
			return api.requireAdmin().then(() => {
				return api.delete(generateOcsUrl('cloud/groups/{groupId}', { groupId: encodeURIComponent(gid) }))
					.then(() => {
						const groupIndex = this.groups.findIndex((groupSearch) => groupSearch.id === gid)
						if (groupIndex >= 0) {
							this.groups.splice(groupIndex, 1)
						}
					})
			}).catch((error) => handleApiFailure({ gid, error }))
		},

		/**
		 * Mark all user devices for remote wipe
		 *
		 * @param userid User id
		 */
		async wipeUserDevices(userid: string) {
			try {
				await api.requireAdmin()
				return await api.post(generateOcsUrl('cloud/users/{userid}/wipe', { userid }))
			} catch (error) {
				handleApiFailure({ userid, error })
				return Promise.reject(new Error('Failed to wipe user devices'))
			}
		},

		/**
		 * Delete a user
		 *
		 * @param userid User id
		 */
		deleteUser(userid: string) {
			return api.requireAdmin().then(() => {
				return api.delete(generateOcsUrl('cloud/users/{userid}', { userid }))
					.then(() => {
						const userIndex = this.users.findIndex((user) => user.id === userid)
						updateUserCounts(this, this.users[userIndex]!, 'remove')
						this.users.splice(userIndex, 1)
					})
			}).catch((error) => handleApiFailure({ userid, error }))
		},

		/**
		 * Add a user
		 *
		 * @param options destructuring object
		 * @param options.userid User id
		 * @param options.password User password
		 * @param options.displayName User display name
		 * @param options.email User email
		 * @param options.groups User groups
		 * @param options.subadmin User subadmin groups
		 * @param options.quota User quota
		 * @param options.language User language
		 * @param options.manager User manager
		 */
		addUser({ userid, password, displayName, email, groups, subadmin, quota, language, manager }: {
			userid: string
			password: string
			displayName: string
			email: string
			groups: string[]
			subadmin: string[]
			quota: string
			language: string
			manager?: string
		}) {
			return api.requireAdmin().then(() => {
				return api.post(generateOcsUrl('cloud/users'), { userid, password, displayName, email, groups, subadmin, quota, language, manager })
					.then((response) => this.addUserData(userid || response.data.ocs.data.id))
			}).catch((error) => {
				handleApiFailure({ userid, error })
				throw error
			})
		},

		/**
		 * Get user data and add it to the list
		 *
		 * @param userid User id
		 */
		addUserData(userid: string) {
			return api.requireAdmin().then(() => {
				return api.get(generateOcsUrl('cloud/users/{userid}', { userid }))
					.then((response) => {
						const user: IUser = response.data.ocs.data
						this.users.unshift(user)
						updateUserCounts(this, user, 'create')
					})
			}).catch((error) => handleApiFailure({ userid, error }))
		},

		/**
		 * Enable or disable user
		 *
		 * @param options destructuring object
		 * @param options.userid User id
		 * @param options.enabled User enablement status
		 */
		enableDisableUser({ userid, enabled = true }: { userid: string, enabled?: boolean }) {
			const userStatus = enabled ? 'enable' : 'disable'
			return api.requireAdmin().then(() => {
				return api.put(generateOcsUrl('cloud/users/{userid}/{userStatus}', { userid, userStatus }))
					.then(() => {
						const user = this.users.find((user) => user.id === userid)!
						user.enabled = enabled
						updateUserCounts(this, user, enabled ? 'enable' : 'disable')
					})
			}).catch((error) => handleApiFailure({ userid, error }))
		},

		/**
		 * Update multiple user fields atomically via the new bulk endpoint.
		 *
		 * @param options destructuring object
		 * @param options.userid User id
		 * @param options.payload Changed fields to send
		 */
		async editUserMultiField({ userid, payload }: { userid: string, payload: Record<string, unknown> }) {
			try {
				await api.requireAdmin()
				const response = await api.patch(
					generateOcsUrl('cloud/users/{userid}', { userid }),
					payload,
				)
				const data: Partial<IUser> = response.data.ocs.data

				const index = this.users.findIndex((user) => user.id === userid)
				if (index === -1) {
					return
				}

				// Delegate group membership changes so sidebar usercount stays in sync.
				if (Array.isArray(data.groups)) {
					const prevGids = this.users[index]!.groups ?? []
					for (const gid of data.groups.filter((g) => !prevGids.includes(g))) {
						addUserGroup(this, userid, gid)
					}
					for (const gid of prevGids.filter((g) => !data.groups!.includes(g))) {
						removeUserGroup(this, userid, gid)
					}
				}

				this.users.splice(index, 1, { ...this.users[index]!, ...data })
			} catch (error) {
				handleApiFailure({ userid, error })
				throw error
			}
		},

		/**
		 * Send welcome mail
		 *
		 * @param userid User id
		 */
		sendWelcomeMail(userid: string) {
			return api.requireAdmin().then(() => {
				return api.post(generateOcsUrl('cloud/users/{userid}/welcome', { userid }))
					.then(() => true)
			}).catch((error) => handleApiFailure({ userid, error }))
		},

		/**
		 * Migrate local storage keys to database
		 */
		migrateLocalStorage() {
			for (const [key, dbKey] of Object.entries(showConfigPreferenceKeys) as [keyof ShowConfig, string][]) {
				const localKey = `account_settings__${key}`
				const localValue = window.localStorage.getItem(localKey)
				if (localValue === null) {
					continue
				}

				const value = localValue === 'true'
				this.showConfig[key] = value

				axios.post(generateUrl(`/settings/users/preferences/${dbKey}`), {
					value: value ? 'true' : 'false',
				}).then(() => {
					window.localStorage.removeItem(localKey)
				}).catch((error) => {
					logger.error(`Failed to migrate preference ${key}`, { error })
				})
			}
		},

		/**
		 * Set show config
		 *
		 * @param options destructuring object
		 * @param options.key Key to set
		 * @param options.value Value to set
		 */
		setShowConfig({ key, value }: { key: keyof ShowConfig, value: boolean }) {
			this.showConfig[key] = value
			axios.post(generateUrl(`settings/users/preferences/${showConfigPreferenceKeys[key]}`), { value: value ? 'true' : 'false' })
				.catch((error) => logger.error(`Could not update ${key} preference`, { error }))
		},

		/**
		 * @param sorting The new sorting
		 */
		setGroupSorting(sorting: GroupSorting) {
			const oldValue = this.orderBy
			this.orderBy = sorting

			// Persist the value on the server
			axios.post(
				generateUrl('/settings/users/preferences/group.sortBy'),
				{
					value: String(sorting),
				},
			).catch((error) => {
				this.orderBy = oldValue
				showError(t('settings', 'Could not set group sorting'))
				logger.error(error)
			})
		},

		/**
		 * Set application config in database
		 *
		 * @param options destructuring object
		 * @param options.app Application name
		 * @param options.key Config key
		 * @param options.value Value to set
		 */
		setAppConfig({ app, key, value }: { app: string, key: string, value: string }) {
			return api.requireAdmin().then(() => {
				return api.post(generateOcsUrl('apps/provisioning_api/api/v1/config/apps/{app}/{key}', { app, key }), { value })
			}).catch((error) => handleApiFailure({ app, key, value, error }))
		},
	},
})
