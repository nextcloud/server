<!--
  - @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div id="app-content"
		role="grid"
		:aria-label="t('settings', 'User\'s table')"
		class="user-list-grid"
		@scroll.passive="onScroll">
		<NewUserModal v-if="showConfig.showNewUserForm"
			:loading="loading"
			:new-user="newUser"
			:show-config="showConfig"
			@reset="resetForm"
			@close="showConfig.showNewUserForm = false" />
		<div id="grid-header"
			:class="{'sticky': scrolled && !showConfig.showNewUserForm}"
			class="row">
			<div id="headerAvatar" class="avatar" />
			<div id="headerName" class="name">
				<div class="subtitle">
					<strong>
						{{ t('settings', 'Display name') }}
					</strong>
				</div>
				{{ t('settings', 'Username') }}
			</div>
			<div id="headerPassword" class="password">
				{{ t('settings', 'Password') }}
			</div>
			<div id="headerAddress" class="mailAddress">
				{{ t('settings', 'Email') }}
			</div>
			<div id="headerGroups" class="groups">
				{{ t('settings', 'Groups') }}
			</div>
			<div v-if="subAdminsGroups.length>0 && settings.isAdmin"
				id="headerSubAdmins"
				class="subadmins">
				{{ t('settings', 'Group admin for') }}
			</div>
			<div id="headerQuota" class="quota">
				{{ t('settings', 'Quota') }}
			</div>
			<div v-if="showConfig.showLanguages"
				id="headerLanguages"
				class="languages">
				{{ t('settings', 'Language') }}
			</div>

			<div v-if="showConfig.showUserBackend || showConfig.showStoragePath"
				class="headerUserBackend userBackend">
				<div v-if="showConfig.showUserBackend" class="userBackend">
					{{ t('settings', 'User backend') }}
				</div>
				<div v-if="showConfig.showStoragePath"
					class="subtitle storageLocation">
					{{ t('settings', 'Storage location') }}
				</div>
			</div>
			<div v-if="showConfig.showLastLogin"
				class="headerLastLogin lastLogin">
				{{ t('settings', 'Last login') }}
			</div>
			<div id="headerManager" class="manager">
				{{ t('settings', 'Manager') }}
			</div>
			<div class="userActions" />
		</div>

		<UserRow v-for="user in filteredUsers"
			:key="user.id"
			:external-actions="externalActions"
			:groups="groups"
			:languages="languages"
			:quota-options="quotaOptions"
			:settings="settings"
			:show-config="showConfig"
			:sub-admins-groups="subAdminsGroups"
			:user="user"
			:users="users"
			:is-dark-theme="isDarkTheme" />

		<InfiniteLoading ref="infiniteLoading" @infinite="infiniteHandler">
			<div slot="spinner">
				<div class="users-icon-loading icon-loading" />
			</div>
			<div slot="no-more">
				<div class="users-list-end" />
			</div>
			<div slot="no-results">
				<div id="emptycontent">
					<div class="icon-contacts-dark" />
					<h2>{{ t('settings', 'No users in here') }}</h2>
				</div>
			</div>
		</InfiniteLoading>
	</div>
</template>

<script>
import Vue from 'vue'
import InfiniteLoading from 'vue-infinite-loading'

import { subscribe, unsubscribe } from '@nextcloud/event-bus'

import UserRow from './Users/UserRow.vue'
import NewUserModal from './Users/NewUserModal.vue'

const unlimitedQuota = {
	id: 'none',
	label: t('settings', 'Unlimited'),
}

const defaultQuota = {
	id: 'default',
	label: t('settings', 'Default quota'),
}

const newUser = {
	id: '',
	displayName: '',
	password: '',
	mailAddress: '',
	groups: [],
	manager: '',
	subAdminsGroups: [],
	quota: defaultQuota,
	language: {
		code: 'en',
		name: t('settings', 'Default language'),
	},
}

export default {
	name: 'UserList',

	components: {
		InfiniteLoading,
		NewUserModal,
		UserRow,
	},

	props: {
		users: {
			type: Array,
			default: () => [],
		},
		showConfig: {
			type: Object,
			required: true,
		},
		selectedGroup: {
			type: String,
			default: null,
		},
		externalActions: {
			type: Array,
			default: () => [],
		},
	},

	data() {
		return {
			loading: {
				all: false,
				groups: false,
			},
			scrolled: false,
			searchQuery: '',
			newUser: Object.assign({}, newUser),
		}
	},

	computed: {
		settings() {
			return this.$store.getters.getServerData
		},
		selectedGroupDecoded() {
			return decodeURIComponent(this.selectedGroup)
		},
		filteredUsers() {
			if (this.selectedGroup === 'disabled') {
				return this.users.filter(user => user.enabled === false)
			}
			if (!this.settings.isAdmin) {
				// we don't want subadmins to edit themselves
				return this.users.filter(user => user.enabled !== false)
			}
			return this.users.filter(user => user.enabled !== false)
		},
		groups() {
			// data provided php side + remove the disabled group
			return this.$store.getters.getGroups
				.filter(group => group.id !== 'disabled')
				.sort((a, b) => a.name.localeCompare(b.name))
		},
		subAdminsGroups() {
			// data provided php side
			return this.$store.getters.getSubadminGroups
		},
		quotaOptions() {
			// convert the preset array into objects
			const quotaPreset = this.settings.quotaPreset.reduce((acc, cur) => acc.concat({
				id: cur,
				label: cur,
			}), [])
			// add default presets
			if (this.settings.allowUnlimitedQuota) {
				quotaPreset.unshift(unlimitedQuota)
			}
			quotaPreset.unshift(defaultQuota)
			return quotaPreset
		},
		usersOffset() {
			return this.$store.getters.getUsersOffset
		},
		usersLimit() {
			return this.$store.getters.getUsersLimit
		},
		usersCount() {
			return this.users.length
		},

		/* LANGUAGES */
		languages() {
			return [
				{
					label: t('settings', 'Common languages'),
					languages: this.settings.languages.commonLanguages,
				},
				{
					label: t('settings', 'Other languages'),
					languages: this.settings.languages.otherLanguages,
				},
			]
		},
		isDarkTheme() {
			return window.getComputedStyle(this.$el)
				.getPropertyValue('--background-invert-if-dark') === 'invert(100%)'
		},
	},
	watch: {
		// watch url change and group select
		selectedGroup(val, old) {
			// if selected is the disabled group but it's empty
			this.redirectIfDisabled()
			this.$store.commit('resetUsers')
			this.$refs.infiniteLoading.stateChanger.reset()
			this.setNewUserDefaultGroup(val)
		},

		// make sure the infiniteLoading state is changed if we manually
		// add/remove data from the store
		usersCount(val, old) {
			// deleting the last user, reset the list
			if (val === 0 && old === 1) {
				this.$refs.infiniteLoading.stateChanger.reset()
				// adding the first user, warn the infiniteLoader that
				// the list is not empty anymore (we don't fetch the newly
				// added user as we already have all the info we need)
			} else if (val === 1 && old === 0) {
				this.$refs.infiniteLoading.stateChanger.loaded()
			}
		},
	},

	mounted() {
		if (!this.settings.canChangePassword) {
			OC.Notification.showTemporary(t('settings', 'Password change is disabled because the master key is disabled'))
		}

		/**
		 * Reset and init new user form
		 */
		this.resetForm()

		/**
		 * Register search
		 */
		subscribe('nextcloud:unified-search.search', this.search)
		subscribe('nextcloud:unified-search.reset', this.resetSearch)

		/**
		 * If disabled group but empty, redirect
		 */
		this.redirectIfDisabled()
	},
	beforeDestroy() {
		unsubscribe('nextcloud:unified-search.search', this.search)
		unsubscribe('nextcloud:unified-search.reset', this.resetSearch)
	},

	methods: {
		onScroll(event) {
			this.scrolled = event.target.scrollTo > 0
		},

		infiniteHandler($state) {
			this.$store.dispatch('getUsers', {
				offset: this.usersOffset,
				limit: this.usersLimit,
				group: this.selectedGroup !== 'disabled' ? this.selectedGroup : '',
				search: this.searchQuery,
			})
				.then((usersCount) => {
					if (usersCount > 0) {
						$state.loaded()
					}
					if (usersCount < this.usersLimit) {
						$state.complete()
					}
				})
		},

		/* SEARCH */
		search({ query }) {
			this.searchQuery = query
			this.$store.commit('resetUsers')
			this.$refs.infiniteLoading.stateChanger.reset()
		},

		resetSearch() {
			this.search({ query: '' })
		},

		resetForm() {
			// revert form to original state
			this.newUser = Object.assign({}, newUser)

			/**
			 * Init default language from server data. The use of this.settings
			 * requires a computed variable, which break the v-model binding of the form,
			 * this is a much easier solution than getter and setter on a computed var
			 */
			if (this.settings.defaultLanguage) {
				Vue.set(this.newUser.language, 'code', this.settings.defaultLanguage)
			}

			/**
			 * In case the user directly loaded the user list within a group
			 * the watch won't be triggered. We need to initialize it.
			 */
			this.setNewUserDefaultGroup(this.selectedGroup)

			this.loading.all = false
		},

		setNewUserDefaultGroup(value) {
			if (value && value.length > 0) {
				// setting new user default group to the current selected one
				const currentGroup = this.groups.find(group => group.id === value)
				if (currentGroup) {
					this.newUser.groups = [currentGroup]
					return
				}
			}
			// fallback, empty selected group
			this.newUser.groups = []
		},

		/**
		 * If the selected group is the disabled group but the count is 0
		 * redirect to the all users page.
		 * we only check for 0 because we don't have the count on ldap
		 * and we therefore set the usercount to -1 in this specific case
		 */
		redirectIfDisabled() {
			const allGroups = this.$store.getters.getGroups
			if (this.selectedGroup === 'disabled'
						&& allGroups.findIndex(group => group.id === 'disabled' && group.usercount === 0) > -1) {
				// disabled group is empty, redirection to all users
				this.$router.push({ name: 'users' })
				this.$refs.infiniteLoading.stateChanger.reset()
			}
		},
	},
}
</script>
