/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getBuilder } from '@nextcloud/browser-storage'
import { getCapabilities } from '@nextcloud/capabilities'
import { parseFileSize } from '@nextcloud/files'
import { showError } from '@nextcloud/dialogs'
import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

import { GroupSorting } from '../constants/GroupManagement.ts'
import api from './api.js'
import logger from '../logger.ts'

const localStorage = getBuilder('settings').persist(true).build()

const defaults = {
	group: {
		id: '',
		name: '',
		usercount: 0,
		disabled: 0,
		canAdd: true,
		canRemove: true,
	},
}

const state = {
	users: [],
	groups: [],
	orderBy: GroupSorting.UserCount,
	minPasswordLength: 0,
	usersOffset: 0,
	usersLimit: 25,
	disabledUsersOffset: 0,
	disabledUsersLimit: 25,
	userCount: 0,
	showConfig: {
		showStoragePath: localStorage.getItem('account_settings__showStoragePath') === 'true',
		showUserBackend: localStorage.getItem('account_settings__showUserBackend') === 'true',
		showFirstLogin: localStorage.getItem('account_settings__showFirstLogin') === 'true',
		showLastLogin: localStorage.getItem('account_settings__showLastLogin') === 'true',
		showNewUserForm: localStorage.getItem('account_settings__showNewUserForm') === 'true',
		showLanguages: localStorage.getItem('account_settings__showLanguages') === 'true',
	},
}

const mutations = {
	appendUsers(state, usersObj) {
		const existingUsers = state.users.map(({ id }) => id)
		const newUsers = Object.values(usersObj)
			.filter(({ id }) => !existingUsers.includes(id))

		const users = state.users.concat(newUsers)
		state.usersOffset += state.usersLimit
		state.users = users
	},
	updateDisabledUsers(state, _usersObj) {
		state.disabledUsersOffset += state.disabledUsersLimit
	},
	setPasswordPolicyMinLength(state, length) {
		state.minPasswordLength = length !== '' ? length : 0
	},
	initGroups(state, { groups, orderBy, userCount }) {
		state.groups = groups.map(group => Object.assign({}, defaults.group, group))
		state.orderBy = orderBy
		state.userCount = userCount
	},
	addGroup(state, { gid, displayName }) {
		try {
			if (typeof state.groups.find((group) => group.id === gid) !== 'undefined') {
				return
			}
			// extend group to default values
			const group = Object.assign({}, defaults.group, {
				id: gid,
				name: displayName,
			})
			state.groups.unshift(group)
		} catch (e) {
			console.error('Can\'t create group', e)
		}
	},
	renameGroup(state, { gid, displayName }) {
		const groupIndex = state.groups.findIndex(groupSearch => groupSearch.id === gid)
		if (groupIndex >= 0) {
			const updatedGroup = state.groups[groupIndex]
			updatedGroup.name = displayName
			state.groups.splice(groupIndex, 1, updatedGroup)
		}
	},
	removeGroup(state, gid) {
		const groupIndex = state.groups.findIndex(groupSearch => groupSearch.id === gid)
		if (groupIndex >= 0) {
			state.groups.splice(groupIndex, 1)
		}
	},
	addUserGroup(state, { userid, gid }) {
		const group = state.groups.find(groupSearch => groupSearch.id === gid)
		const user = state.users.find(user => user.id === userid)
		// increase count if user is enabled
		if (group && user.enabled && state.userCount > 0) {
			group.usercount++
		}
		const groups = user.groups
		groups.push(gid)
	},
	removeUserGroup(state, { userid, gid }) {
		const group = state.groups.find(groupSearch => groupSearch.id === gid)
		const user = state.users.find(user => user.id === userid)
		// lower count if user is enabled
		if (group && user.enabled && state.userCount > 0) {
			group.usercount--
		}
		const groups = user.groups
		groups.splice(groups.indexOf(gid), 1)
	},
	addUserSubAdmin(state, { userid, gid }) {
		const groups = state.users.find(user => user.id === userid).subadmin
		groups.push(gid)
	},
	removeUserSubAdmin(state, { userid, gid }) {
		const groups = state.users.find(user => user.id === userid).subadmin
		groups.splice(groups.indexOf(gid), 1)
	},
	deleteUser(state, userid) {
		const userIndex = state.users.findIndex(user => user.id === userid)
		this.commit('updateUserCounts', { user: state.users[userIndex], actionType: 'remove' })
		state.users.splice(userIndex, 1)
	},
	addUserData(state, response) {
		const user = response.data.ocs.data
		state.users.unshift(user)
		this.commit('updateUserCounts', { user, actionType: 'create' })
	},
	enableDisableUser(state, { userid, enabled }) {
		const user = state.users.find(user => user.id === userid)
		user.enabled = enabled
		this.commit('updateUserCounts', { user, actionType: enabled ? 'enable' : 'disable' })
	},
	// update active/disabled counts, groups counts
	updateUserCounts(state, { user, actionType }) {
		// 0 is a special value
		if (state.userCount === 0) {
			return
		}

		const recentGroup = state.groups.find(group => group.id === '__nc_internal_recent')
		const disabledGroup = state.groups.find(group => group.id === 'disabled')
		switch (actionType) {
		case 'enable':
		case 'disable':
			disabledGroup.usercount += user.enabled ? -1 : 1 // update Disabled Users count
			recentGroup.usercount += user.enabled ? 1 : -1
			state.userCount += user.enabled ? 1 : -1 // update Active Users count
			user.groups.forEach(userGroup => {
				const group = state.groups.find(groupSearch => groupSearch.id === userGroup)
				group.disabled += user.enabled ? -1 : 1 // update group disabled count
			})
			break
		case 'create':
			recentGroup.usercount++
			state.userCount++ // increment Active Users count

			user.groups.forEach(userGroup => {
				state.groups
					.find(groupSearch => groupSearch.id === userGroup)
				    .usercount++ // increment group total count
			})
			break
		case 'remove':
			if (user.enabled) {
				recentGroup.usercount--
				state.userCount-- // decrement Active Users count
				user.groups.forEach(userGroup => {
					const group = state.groups.find(groupSearch => groupSearch.id === userGroup)
					if (!group) {
						console.warn('User group ' + userGroup + ' does not exist during user removal')
						return
					}
					group.usercount-- // decrement group total count
				})
			} else {
				disabledGroup.usercount-- // decrement Disabled Users count
				user.groups.forEach(userGroup => {
					const group = state.groups.find(groupSearch => groupSearch.id === userGroup)
					group.disabled-- // decrement group disabled count
				})
			}
			break
		default:
			logger.error(`Unknown action type in updateUserCounts: '${actionType}'`)
			// not throwing error to interrupt execution as this is not fatal
		}
	},
	setUserData(state, { userid, key, value }) {
		if (key === 'quota') {
			const humanValue = parseFileSize(value, true)
			state.users.find(user => user.id === userid)[key][key] = humanValue !== null ? humanValue : value
		} else {
			state.users.find(user => user.id === userid)[key] = value
		}
	},

	/**
	 * Reset users list
	 *
	 * @param {object} state the store state
	 */
	resetUsers(state) {
		state.users = []
		state.usersOffset = 0
		state.disabledUsersOffset = 0
	},

	setShowConfig(state, { key, value }) {
		localStorage.setItem(`account_settings__${key}`, JSON.stringify(value))
		state.showConfig[key] = value
	},

	setGroupSorting(state, sorting) {
		const oldValue = state.orderBy
		state.orderBy = sorting

		// Persist the value on the server
		axios.post(
			generateUrl('/settings/users/preferences/group.sortBy'),
			{
				value: String(sorting),
			},
		).catch((error) => {
			state.orderBy = oldValue
			showError(t('settings', 'Could not set group sorting'))
			logger.error(error)
		})
	},
}

const getters = {
	getUsers(state) {
		return state.users
	},
	getGroups(state) {
		return state.groups
	},
	getSubadminGroups(state) {
		// Can't be subadmin of admin, recent, or disabled
		return state.groups.filter(group => group.id !== 'admin' && group.id !== '__nc_internal_recent' && group.id !== 'disabled')
	},
	getSortedGroups(state) {
		const groups = [...state.groups]
		if (state.orderBy === GroupSorting.UserCount) {
			return groups.sort((a, b) => {
				const numA = a.usercount - a.disabled
				const numB = b.usercount - b.disabled
				return (numA < numB) ? 1 : (numB < numA ? -1 : a.name.localeCompare(b.name))
			})
		} else {
			return groups.sort((a, b) => a.name.localeCompare(b.name))
		}
	},
	getGroupSorting(state) {
		return state.orderBy
	},
	getPasswordPolicyMinLength(state) {
		return state.minPasswordLength
	},
	getUsersOffset(state) {
		return state.usersOffset
	},
	getUsersLimit(state) {
		return state.usersLimit
	},
	getDisabledUsersOffset(state) {
		return state.disabledUsersOffset
	},
	getDisabledUsersLimit(state) {
		return state.disabledUsersLimit
	},
	getUserCount(state) {
		return state.userCount
	},
	getShowConfig(state) {
		return state.showConfig
	},
}

const CancelToken = axios.CancelToken
let searchRequestCancelSource = null

const actions = {

	/**
	 * search users
	 *
	 * @param {object} context store context
	 * @param {object} options destructuring object
	 * @param {number} options.offset List offset to request
	 * @param {number} options.limit List number to return from offset
	 * @param {string} options.search Search amongst users
	 * @return {Promise}
	 */
	searchUsers(context, { offset, limit, search }) {
		search = typeof search === 'string' ? search : ''

		return api.get(generateOcsUrl('cloud/users/details?offset={offset}&limit={limit}&search={search}', { offset, limit, search })).catch((error) => {
			if (!axios.isCancel(error)) {
				context.commit('API_FAILURE', error)
			}
		})
	},

	/**
	 * Get user details
	 *
	 * @param {object} context store context
	 * @param {string} userId user id
	 * @return {Promise}
	 */
	getUser(context, userId) {
		return api.get(generateOcsUrl(`cloud/users/${userId}`)).catch((error) => {
			if (!axios.isCancel(error)) {
				context.commit('API_FAILURE', error)
			}
		})
	},

	/**
	 * Get all users with full details
	 *
	 * @param {object} context store context
	 * @param {object} options destructuring object
	 * @param {number} options.offset List offset to request
	 * @param {number} options.limit List number to return from offset
	 * @param {string} options.search Search amongst users
	 * @param {string} options.group Get users from group
	 * @return {Promise}
	 */
	getUsers(context, { offset, limit, search, group }) {
		if (searchRequestCancelSource) {
			searchRequestCancelSource.cancel('Operation canceled by another search request.')
		}
		searchRequestCancelSource = CancelToken.source()
		search = typeof search === 'string' ? search : ''

		/**
		 * Adding filters in the search bar such as in:files, in:users, etc.
		 * collides with this particular search, so we need to remove them
		 * here and leave only the original search query
		 */
		search = search.replace(/in:[^\s]+/g, '').trim()

		group = typeof group === 'string' ? group : ''
		if (group !== '') {
			return api.get(generateOcsUrl('cloud/groups/{group}/users/details?offset={offset}&limit={limit}&search={search}', { group: encodeURIComponent(group), offset, limit, search }), {
				cancelToken: searchRequestCancelSource.token,
			})
				.then((response) => {
					const usersCount = Object.keys(response.data.ocs.data.users).length
					if (usersCount > 0) {
						context.commit('appendUsers', response.data.ocs.data.users)
					}
					return usersCount
				})
				.catch((error) => {
					if (!axios.isCancel(error)) {
						context.commit('API_FAILURE', error)
					}
				})
		}

		return api.get(generateOcsUrl('cloud/users/details?offset={offset}&limit={limit}&search={search}', { offset, limit, search }), {
			cancelToken: searchRequestCancelSource.token,
		})
			.then((response) => {
				const usersCount = Object.keys(response.data.ocs.data.users).length
				if (usersCount > 0) {
					context.commit('appendUsers', response.data.ocs.data.users)
				}
				return usersCount
			})
			.catch((error) => {
				if (!axios.isCancel(error)) {
					context.commit('API_FAILURE', error)
				}
			})
	},

	/**
	 * Get recent users with full details
	 *
	 * @param {object} context store context
	 * @param {object} options destructuring object
	 * @param {number} options.offset List offset to request
	 * @param {number} options.limit List number to return from offset
	 * @param {string} options.search Search query
	 * @return {Promise<number>}
	 */
	async getRecentUsers(context, { offset, limit, search }) {
		const url = generateOcsUrl('cloud/users/recent?offset={offset}&limit={limit}&search={search}', { offset, limit, search })
		try {
			const response = await api.get(url)
			const usersCount = Object.keys(response.data.ocs.data.users).length
			if (usersCount > 0) {
				context.commit('appendUsers', response.data.ocs.data.users)
			}
			return usersCount
		} catch (error) {
			context.commit('API_FAILURE', error)
		}
	},

	/**
	 * Get disabled users with full details
	 *
	 * @param {object} context store context
	 * @param {object} options destructuring object
	 * @param {number} options.offset List offset to request
	 * @param {number} options.limit List number to return from offset
	 * @param options.search
	 * @return {Promise<number>}
	 */
	async getDisabledUsers(context, { offset, limit, search }) {
		const url = generateOcsUrl('cloud/users/disabled?offset={offset}&limit={limit}&search={search}', { offset, limit, search })
		try {
			const response = await api.get(url)
			const usersCount = Object.keys(response.data.ocs.data.users).length
			if (usersCount > 0) {
				context.commit('appendUsers', response.data.ocs.data.users)
				context.commit('updateDisabledUsers', response.data.ocs.data.users)
			}
			return usersCount
		} catch (error) {
			context.commit('API_FAILURE', error)
		}
	},

	getGroups(context, { offset, limit, search }) {
		search = typeof search === 'string' ? search : ''
		const limitParam = limit === -1 ? '' : `&limit=${limit}`
		return api.get(generateOcsUrl('cloud/groups?offset={offset}&search={search}', { offset, search }) + limitParam)
			.then((response) => {
				if (Object.keys(response.data.ocs.data.groups).length > 0) {
					response.data.ocs.data.groups.forEach(function(group) {
						context.commit('addGroup', { gid: group, displayName: group })
					})
					return true
				}
				return false
			})
			.catch((error) => context.commit('API_FAILURE', error))
	},

	/**
	 * Get all users with full details
	 *
	 * @param {object} context store context
	 * @param {object} options destructuring object
	 * @param {number} options.offset List offset to request
	 * @param {number} options.limit List number to return from offset
	 * @param {string} options.search -
	 * @return {Promise}
	 */
	getUsersFromList(context, { offset, limit, search }) {
		search = typeof search === 'string' ? search : ''
		return api.get(generateOcsUrl('cloud/users/details?offset={offset}&limit={limit}&search={search}', { offset, limit, search }))
			.then((response) => {
				if (Object.keys(response.data.ocs.data.users).length > 0) {
					context.commit('appendUsers', response.data.ocs.data.users)
					return true
				}
				return false
			})
			.catch((error) => context.commit('API_FAILURE', error))
	},

	/**
	 * Get all users with full details from a groupid
	 *
	 * @param {object} context store context
	 * @param {object} options destructuring object
	 * @param {number} options.offset List offset to request
	 * @param {number} options.limit List number to return from offset
	 * @param {string} options.groupid -
	 * @return {Promise}
	 */
	getUsersFromGroup(context, { groupid, offset, limit }) {
		return api.get(generateOcsUrl('cloud/users/{groupId}/details?offset={offset}&limit={limit}', { groupId: encodeURIComponent(groupid), offset, limit }))
			.then((response) => context.commit('getUsersFromList', response.data.ocs.data.users))
			.catch((error) => context.commit('API_FAILURE', error))
	},

	getPasswordPolicyMinLength(context) {
		if (getCapabilities().password_policy && getCapabilities().password_policy.minLength) {
			context.commit('setPasswordPolicyMinLength', getCapabilities().password_policy.minLength)
			return getCapabilities().password_policy.minLength
		}
		return false
	},

	/**
	 * Add group
	 *
	 * @param {object} context store context
	 * @param {string} gid Group id
	 * @return {Promise}
	 */
	addGroup(context, gid) {
		return api.requireAdmin().then((response) => {
			return api.post(generateOcsUrl('cloud/groups'), { groupid: gid })
				.then((response) => {
					context.commit('addGroup', { gid, displayName: gid })
					return { gid, displayName: gid }
				})
				.catch((error) => { throw error })
		}).catch((error) => {
			context.commit('API_FAILURE', { gid, error })
			// let's throw one more time to prevent the view
			// from adding the user to a group that doesn't exists
			throw error
		})
	},

	/**
	 * Rename group
	 *
	 * @param {object} context store context
	 * @param {string} groupid Group id
	 * @param {string} displayName Group display name
	 * @return {Promise}
	 */
	renameGroup(context, { groupid, displayName }) {
		return api.requireAdmin().then((response) => {
			return api.put(generateOcsUrl('cloud/groups/{groupId}', { groupId: encodeURIComponent(groupid) }), { key: 'displayname', value: displayName })
				.then((response) => {
					context.commit('renameGroup', { gid: groupid, displayName })
					return { groupid, displayName }
				})
				.catch((error) => { throw error })
		}).catch((error) => {
			context.commit('API_FAILURE', { groupid, error })
			// let's throw one more time to prevent the view
			// from renaming the group
			throw error
		})
	},

	/**
	 * Remove group
	 *
	 * @param {object} context store context
	 * @param {string} gid Group id
	 * @return {Promise}
	 */
	removeGroup(context, gid) {
		return api.requireAdmin().then((response) => {
			return api.delete(generateOcsUrl('cloud/groups/{groupId}', { groupId: encodeURIComponent(gid) }))
				.then((response) => context.commit('removeGroup', gid))
				.catch((error) => { throw error })
		}).catch((error) => context.commit('API_FAILURE', { gid, error }))
	},

	/**
	 * Add user to group
	 *
	 * @param {object} context store context
	 * @param {object} options destructuring object
	 * @param {string} options.userid User id
	 * @param {string} options.gid Group id
	 * @return {Promise}
	 */
	addUserGroup(context, { userid, gid }) {
		return api.requireAdmin().then((response) => {
			return api.post(generateOcsUrl('cloud/users/{userid}/groups', { userid }), { groupid: gid })
				.then((response) => context.commit('addUserGroup', { userid, gid }))
				.catch((error) => { throw error })
		}).catch((error) => context.commit('API_FAILURE', { userid, error }))
	},

	/**
	 * Remove user from group
	 *
	 * @param {object} context store context
	 * @param {object} options destructuring object
	 * @param {string} options.userid User id
	 * @param {string} options.gid Group id
	 * @return {Promise}
	 */
	removeUserGroup(context, { userid, gid }) {
		return api.requireAdmin().then((response) => {
			return api.delete(generateOcsUrl('cloud/users/{userid}/groups', { userid }), { groupid: gid })
				.then((response) => context.commit('removeUserGroup', { userid, gid }))
				.catch((error) => { throw error })
		}).catch((error) => {
			context.commit('API_FAILURE', { userid, error })
			// let's throw one more time to prevent
			// the view from removing the user row on failure
			throw error
		})
	},

	/**
	 * Add user to group admin
	 *
	 * @param {object} context store context
	 * @param {object} options destructuring object
	 * @param {string} options.userid User id
	 * @param {string} options.gid Group id
	 * @return {Promise}
	 */
	addUserSubAdmin(context, { userid, gid }) {
		return api.requireAdmin().then((response) => {
			return api.post(generateOcsUrl('cloud/users/{userid}/subadmins', { userid }), { groupid: gid })
				.then((response) => context.commit('addUserSubAdmin', { userid, gid }))
				.catch((error) => { throw error })
		}).catch((error) => context.commit('API_FAILURE', { userid, error }))
	},

	/**
	 * Remove user from group admin
	 *
	 * @param {object} context store context
	 * @param {object} options destructuring object
	 * @param {string} options.userid User id
	 * @param {string} options.gid Group id
	 * @return {Promise}
	 */
	removeUserSubAdmin(context, { userid, gid }) {
		return api.requireAdmin().then((response) => {
			return api.delete(generateOcsUrl('cloud/users/{userid}/subadmins', { userid }), { groupid: gid })
				.then((response) => context.commit('removeUserSubAdmin', { userid, gid }))
				.catch((error) => { throw error })
		}).catch((error) => context.commit('API_FAILURE', { userid, error }))
	},

	/**
	 * Mark all user devices for remote wipe
	 *
	 * @param {object} context store context
	 * @param {string} userid User id
	 * @return {Promise}
	 */
	async wipeUserDevices(context, userid) {
		try {
			await api.requireAdmin()
			return await api.post(generateOcsUrl('cloud/users/{userid}/wipe', { userid }))
		} catch (error) {
			context.commit('API_FAILURE', { userid, error })
			return Promise.reject(new Error('Failed to wipe user devices'))
		}
	},

	/**
	 * Delete a user
	 *
	 * @param {object} context store context
	 * @param {string} userid User id
	 * @return {Promise}
	 */
	deleteUser(context, userid) {
		return api.requireAdmin().then((response) => {
			return api.delete(generateOcsUrl('cloud/users/{userid}', { userid }))
				.then((response) => context.commit('deleteUser', userid))
				.catch((error) => { throw error })
		}).catch((error) => context.commit('API_FAILURE', { userid, error }))
	},

	/**
	 * Add a user
	 *
	 * @param {object} context store context
	 * @param {Function} context.commit -
	 * @param {Function} context.dispatch -
	 * @param {object} options destructuring object
	 * @param {string} options.userid User id
	 * @param {string} options.password User password
	 * @param {string} options.displayName User display name
	 * @param {string} options.email User email
	 * @param {string} options.groups User groups
	 * @param {string} options.subadmin User subadmin groups
	 * @param {string} options.quota User email
	 * @param {string} options.language User language
	 * @param {string} options.manager User manager
	 * @return {Promise}
	 */
	addUser({ commit, dispatch }, { userid, password, displayName, email, groups, subadmin, quota, language, manager }) {
		return api.requireAdmin().then((response) => {
			return api.post(generateOcsUrl('cloud/users'), { userid, password, displayName, email, groups, subadmin, quota, language, manager })
				.then((response) => dispatch('addUserData', userid || response.data.ocs.data.id))
				.catch((error) => { throw error })
		}).catch((error) => {
			commit('API_FAILURE', { userid, error })
			throw error
		})
	},

	/**
	 * Get user data and commit addition
	 *
	 * @param {object} context store context
	 * @param {string} userid User id
	 * @return {Promise}
	 */
	addUserData(context, userid) {
		return api.requireAdmin().then((response) => {
			return api.get(generateOcsUrl('cloud/users/{userid}', { userid }))
				.then((response) => context.commit('addUserData', response))
				.catch((error) => { throw error })
		}).catch((error) => context.commit('API_FAILURE', { userid, error }))
	},

	/**
	 * Enable or disable user
	 *
	 * @param {object} context store context
	 * @param {object} options destructuring object
	 * @param {string} options.userid User id
	 * @param {boolean} options.enabled User enablement status
	 * @return {Promise}
	 */
	enableDisableUser(context, { userid, enabled = true }) {
		const userStatus = enabled ? 'enable' : 'disable'
		return api.requireAdmin().then((response) => {
			return api.put(generateOcsUrl('cloud/users/{userid}/{userStatus}', { userid, userStatus }))
				.then((response) => context.commit('enableDisableUser', { userid, enabled }))
				.catch((error) => { throw error })
		}).catch((error) => context.commit('API_FAILURE', { userid, error }))
	},

	/**
	 * Edit user data
	 *
	 * @param {object} context store context
	 * @param {object} options destructuring object
	 * @param {string} options.userid User id
	 * @param {string} options.key User field to edit
	 * @param {string} options.value Value of the change
	 * @return {Promise}
	 */
	async setUserData(context, { userid, key, value }) {
		const allowedEmpty = ['email', 'displayname', 'manager']
		if (['email', 'language', 'quota', 'displayname', 'password', 'manager'].indexOf(key) !== -1) {
			// We allow empty email or displayname
			if (typeof value === 'string'
				&& (
					(allowedEmpty.indexOf(key) === -1 && value.length > 0)
					|| allowedEmpty.indexOf(key) !== -1
				)
			) {
				try {
					await api.requireAdmin()
					await api.put(generateOcsUrl('cloud/users/{userid}', { userid }), { key, value })
					return context.commit('setUserData', { userid, key, value })
				} catch (error) {
					context.commit('API_FAILURE', { userid, error })
				}
			}
		}
		return Promise.reject(new Error('Invalid request data'))
	},

	/**
	 * Send welcome mail
	 *
	 * @param {object} context store context
	 * @param {string} userid User id
	 * @return {Promise}
	 */
	sendWelcomeMail(context, userid) {
		return api.requireAdmin().then((response) => {
			return api.post(generateOcsUrl('cloud/users/{userid}/welcome', { userid }))
				.then(response => true)
				.catch((error) => { throw error })
		}).catch((error) => context.commit('API_FAILURE', { userid, error }))
	},
}

export default { state, mutations, getters, actions }
