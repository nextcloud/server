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
	<Content app-name="settings" :navigation-class="{ 'icon-loading': loadingAddGroup }">
		<AppNavigation>
			<AppNavigationNew button-id="new-user-button"
				:text="t('settings','New user')"
				button-class="icon-add"
				@click="toggleNewUserMenu" />
			<ul id="usergrouplist">
				<AppNavigationItem v-for="item in menu" :key="item.key" :item="item" />
			</ul>
			<AppNavigationSettings>
				<div>
					<p>{{ t('settings', 'Default quota:') }}</p>
					<Multiselect :value="defaultQuota"
						:options="quotaOptions"
						tag-placeholder="create"
						:placeholder="t('settings', 'Select default quota')"
						label="label"
						track-by="id"
						:allow-empty="false"
						:taggable="true"
						@tag="validateQuota"
						@input="setDefaultQuota" />
				</div>
				<div>
					<input id="showLanguages"
						v-model="showLanguages"
						type="checkbox"
						class="checkbox">
					<label for="showLanguages">{{ t('settings', 'Show Languages') }}</label>
				</div>
				<div>
					<input id="showLastLogin"
						v-model="showLastLogin"
						type="checkbox"
						class="checkbox">
					<label for="showLastLogin">{{ t('settings', 'Show last login') }}</label>
				</div>
				<div>
					<input id="showUserBackend"
						v-model="showUserBackend"
						type="checkbox"
						class="checkbox">
					<label for="showUserBackend">{{ t('settings', 'Show user backend') }}</label>
				</div>
				<div>
					<input id="showStoragePath"
						v-model="showStoragePath"
						type="checkbox"
						class="checkbox">
					<label for="showStoragePath">{{ t('settings', 'Show storage path') }}</label>
				</div>
			</AppNavigationSettings>
		</AppNavigation>
		<AppContent>
			<UserList #content
				:users="users"
				:show-config="showConfig"
				:selected-group="selectedGroup"
				:external-actions="externalActions" />
		</AppContent>
	</Content>
</template>

<script>
import Vue from 'vue'
import VueLocalStorage from 'vue-localstorage'
import {
	AppContent,
	AppNavigation,
	AppNavigationItem,
	AppNavigationNew,
	AppNavigationSettings,
	Content,
	Multiselect
} from 'nextcloud-vue'
import UserList from '../components/UserList'

Vue.use(VueLocalStorage)

export default {
	name: 'Users',
	components: {
		AppContent,
		AppNavigation,
		AppNavigationItem,
		AppNavigationNew,
		AppNavigationSettings,
		Content,
		UserList,
		Multiselect
	},
	props: {
		selectedGroup: {
			type: String,
			default: null
		}
	},
	data() {
		return {
			// default quota is set to unlimited
			unlimitedQuota: { id: 'none', label: t('settings', 'Unlimited') },
			// temporary value used for multiselect change
			selectedQuota: false,
			externalActions: [],
			showAddGroupEntry: false,
			loadingAddGroup: false,
			showConfig: {
				showStoragePath: false,
				showUserBackend: false,
				showLastLogin: false,
				showNewUserForm: false,
				showLanguages: false
			}
		}
	},
	computed: {
		users() {
			return this.$store.getters.getUsers
		},
		usersOffset() {
			return this.$store.getters.getUsersOffset
		},
		usersLimit() {
			return this.$store.getters.getUsersLimit
		},

		// Local settings
		showLanguages: {
			get: function() { return this.getLocalstorage('showLanguages') },
			set: function(status) {
				this.setLocalStorage('showLanguages', status)
			}
		},
		showLastLogin: {
			get: function() { return this.getLocalstorage('showLastLogin') },
			set: function(status) {
				this.setLocalStorage('showLastLogin', status)
			}
		},
		showUserBackend: {
			get: function() { return this.getLocalstorage('showUserBackend') },
			set: function(status) {
				this.setLocalStorage('showUserBackend', status)
			}
		},
		showStoragePath: {
			get: function() { return this.getLocalstorage('showStoragePath') },
			set: function(status) {
				this.setLocalStorage('showStoragePath', status)
			}
		},

		userCount() {
			return this.$store.getters.getUserCount
		},
		settings() {
			return this.$store.getters.getServerData
		},

		// default quota
		quotaOptions() {
			// convert the preset array into objects
			let quotaPreset = this.settings.quotaPreset.reduce((acc, cur) => acc.concat({ id: cur, label: cur }), [])
			// add default presets
			quotaPreset.unshift(this.unlimitedQuota)
			return quotaPreset
		},
		// mapping saved values to objects
		defaultQuota: {
			get: function() {
				if (this.selectedQuota !== false) {
					return this.selectedQuota
				}
				if (this.settings.defaultQuota !== this.unlimitedQuota.id && OC.Util.computerFileSize(this.settings.defaultQuota) >= 0) {
					// if value is valid, let's map the quotaOptions or return custom quota
					return { id: this.settings.defaultQuota, label: this.settings.defaultQuota }
				}
				return this.unlimitedQuota // unlimited
			},
			set: function(quota) {
				this.selectedQuota = quota
			}

		},

		// BUILD APP NAVIGATION MENU OBJECT
		menu() {
			// Data provided php side
			let self = this
			let groups = this.$store.getters.getGroups
			groups = Array.isArray(groups) ? groups : []

			// Map groups
			groups = groups.map(group => {
				let item = {}
				item.id = group.id.replace(' ', '_')
				item.key = item.id
				item.utils = {}

				// router link to
				item.router = {
					name: 'group',
					params: { selectedGroup: group.id }
				}

				// group name
				item.text = group.name
				item.title = group.name

				// users count for all groups
				if (group.usercount - group.disabled > 0 || group.usercount === -1) {
					item.utils.counter = group.usercount - group.disabled
				}

				if (item.id !== 'admin' && item.id !== 'disabled' && this.settings.isAdmin) {
					// add delete button on real groups
					item.utils.actions = [{
						icon: 'icon-delete',
						text: t('settings', 'Remove group'),
						action: function() {
							self.removeGroup(group.id)
						}
					}]
				}
				return item
			})

			// Every item is added on top of the array, so we're going backward
			// Groups, separator, disabled, admin, everyone

			// Add separator
			let realGroups = groups.find((group) => { return group.id !== 'disabled' && group.id !== 'admin' })
			realGroups = typeof realGroups === 'undefined' ? [] : realGroups
			realGroups = Array.isArray(realGroups) ? realGroups : [realGroups]
			if (realGroups.length > 0) {
				let separator = {
					caption: true,
					text: t('settings', 'Groups')
				}
				groups.unshift(separator)
			}

			// Adjust admin and disabled groups
			let adminGroup = groups.find(group => group.id === 'admin')
			let disabledGroup = groups.find(group => group.id === 'disabled')

			// filter out admin and disabled
			groups = groups.filter(group => ['admin', 'disabled'].indexOf(group.id) === -1)

			if (adminGroup && adminGroup.text) {
				adminGroup.text = t('settings', 'Admins')	// rename admin group
				adminGroup.icon = 'icon-user-admin'			// set icon
				groups.unshift(adminGroup)					// add admin group if present
			}
			if (disabledGroup && disabledGroup.text) {
				disabledGroup.text = t('settings', 'Disabled users')	// rename disabled group
				disabledGroup.icon = 'icon-disabled-users'				// set icon
				if (disabledGroup.utils && (
					disabledGroup.utils.counter > 0						// add disabled if not empty
					|| disabledGroup.utils.counter === -1)				// add disabled if ldap enabled
				) {
					groups.unshift(disabledGroup)
					if (disabledGroup.utils.counter === -1) {
						// hides the counter instead of showing -1
						delete disabledGroup.utils.counter
					}
				}
			}

			// Add everyone group
			let everyoneGroup = {
				id: 'everyone',
				key: 'everyone',
				icon: 'icon-contacts-dark',
				router: { name: 'users' },
				text: t('settings', 'Everyone')
			}
			// users count
			if (this.userCount > 0) {
				Vue.set(everyoneGroup, 'utils', {
					counter: this.userCount
				})
			}
			groups.unshift(everyoneGroup)

			let addGroup = {
				id: 'addgroup',
				key: 'addgroup',
				icon: 'icon-add',
				text: t('settings', 'Add group'),
				classes: this.loadingAddGroup ? 'icon-loading-small' : ''
			}
			if (this.showAddGroupEntry) {
				Vue.set(addGroup, 'edit', {
					text: t('settings', 'Add group'),
					action: this.createGroup,
					reset: function() {
						self.showAddGroupEntry = false
					}
				})
				addGroup.classes = 'editing'
			} else {
				Vue.set(addGroup, 'action', function() {
					self.showAddGroupEntry = true
					// focus input
					Vue.nextTick(() => {
						window.addgroup.querySelector('form > input[type="text"]').focus()
					})
				})
			}
			groups.unshift(addGroup)

			return groups
		}
	},
	beforeMount() {
		this.$store.commit('initGroups', {
			groups: this.$store.getters.getServerData.groups,
			orderBy: this.$store.getters.getServerData.sortGroups,
			userCount: this.$store.getters.getServerData.userCount
		})
		this.$store.dispatch('getPasswordPolicyMinLength')
	},
	created() {
		// init the OCA.Settings.UserList object
		// and add the registerAction method
		Object.assign(OCA, {
			Settings: {
				UserList: {
					registerAction: this.registerAction
				}
			}
		})
	},
	methods: {
		toggleNewUserMenu() {
			this.showConfig.showNewUserForm = !this.showConfig.showNewUserForm
			if (this.showConfig.showNewUserForm) {
				Vue.nextTick(() => {
					window.newusername.focus()
				})
			}
		},
		getLocalstorage(key) {
			// force initialization
			let localConfig = this.$localStorage.get(key)
			// if localstorage is null, fallback to original values
			this.showConfig[key] = localConfig !== null ? localConfig === 'true' : this.showConfig[key]
			return this.showConfig[key]
		},
		setLocalStorage(key, status) {
			this.showConfig[key] = status
			this.$localStorage.set(key, status)
			return status
		},
		removeGroup(groupid) {
			let self = this
			// TODO migrate to a vue js confirm dialog component
			OC.dialogs.confirm(
				t('settings', 'You are about to remove the group {group}. The users will NOT be deleted.', { group: groupid }),
				t('settings', 'Please confirm the group removal '),
				function(success) {
					if (success) {
						self.$store.dispatch('removeGroup', groupid)
					}
				}
			)
		},

		/**
		 * Dispatch default quota set request
		 *
		 * @param {string|Object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
		 */
		setDefaultQuota(quota = 'none') {
			this.$store.dispatch('setAppConfig', {
				app: 'files',
				key: 'default_quota',
				// ensure we only send the preset id
				value: quota.id ? quota.id : quota
			}).then(() => {
				if (typeof quota !== 'object') {
					quota = { id: quota, label: quota }
				}
				this.defaultQuota = quota
			})
		},

		/**
		 * Validate quota string to make sure it's a valid human file size
		 *
		 * @param {string} quota Quota in readable format '5 GB'
		 * @returns {Promise|boolean}
		 */
		validateQuota(quota) {
			// only used for new presets sent through @Tag
			let validQuota = OC.Util.computerFileSize(quota)
			if (validQuota === null) {
				return this.setDefaultQuota('none')
			} else {
				// unify format output
				return this.setDefaultQuota(OC.Util.humanFileSize(OC.Util.computerFileSize(quota)))
			}
		},

		/**
		 * Register a new action for the user menu
		 *
		 * @param {string} icon the icon class
		 * @param {string} text the text to display
		 * @param {Function} action the function to run
		 * @returns {Array}
		 */
		registerAction(icon, text, action) {
			this.externalActions.push({
				icon: icon,
				text: text,
				action: action
			})
			return this.externalActions
		},

		/**
		 * Create a new group
		 *
		 * @param {Object} event The form submit event
		 */
		createGroup(event) {
			let gid = event.target[0].value
			this.loadingAddGroup = true
			this.$store.dispatch('addGroup', gid)
				.then(() => {
					this.showAddGroupEntry = false
					this.loadingAddGroup = false
					this.$router.push({
						name: 'group',
						params: {
							selectedGroup: gid
						}
					})
				})
				.catch(() => {
					this.loadingAddGroup = false
				})
		}
	}
}
</script>
