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
	<NcContent app-name="settings" :navigation-class="{ 'icon-loading': loadingAddGroup }">
		<NcAppNavigation>
			<NcAppNavigationNew button-id="new-user-button"
				:text="t('settings','New user')"
				button-class="icon-add"
				@click="showNewUserMenu"
				@keyup.enter="showNewUserMenu"
				@keyup.space="showNewUserMenu" />
			<template #list>
				<NcAppNavigationNewItem id="addgroup"
					ref="addGroup"
					:edit-placeholder="t('settings', 'Enter group name')"
					:editable="true"
					:loading="loadingAddGroup"
					:title="t('settings', 'Add group')"
					@click="showAddGroupForm"
					@new-item="createGroup">
					<template #icon>
						<Plus :size="20" />
					</template>
				</NcAppNavigationNewItem>
				<NcAppNavigationItem id="everyone"
					:exact="true"
					:title="t('settings', 'Active users')"
					:to="{ name: 'users' }"
					icon="icon-contacts-dark">
					<template #counter>
						<NcCounterBubble :type="!selectedGroupDecoded ? 'highlighted' : undefined">
							{{ userCount }}
						</NcCounterBubble>
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem v-if="settings.isAdmin"
					id="admin"
					:exact="true"
					:title="t('settings', 'Admins')"
					:to="{ name: 'group', params: { selectedGroup: 'admin' } }"
					icon="icon-user-admin">
					<template v-if="adminGroupMenu.count > 0" #counter>
						<NcCounterBubble :type="selectedGroupDecoded === 'admin' ? 'highlighted' : undefined">
							{{ adminGroupMenu.count }}
						</NcCounterBubble>
					</template>
				</NcAppNavigationItem>

				<!-- Hide the disabled if none, if we don't have the data (-1) show it -->
				<NcAppNavigationItem v-if="disabledGroupMenu.usercount > 0 || disabledGroupMenu.usercount === -1"
					id="disabled"
					:exact="true"
					:title="t('settings', 'Disabled users')"
					:to="{ name: 'group', params: { selectedGroup: 'disabled' } }"
					icon="icon-disabled-users">
					<template v-if="disabledGroupMenu.usercount > 0" #counter>
						<NcCounterBubble :type="selectedGroupDecoded === 'disabled' ? 'highlighted' : undefined">
							{{ disabledGroupMenu.usercount }}
						</NcCounterBubble>
					</template>
				</NcAppNavigationItem>

				<NcAppNavigationCaption v-if="groupList.length > 0" :title="t('settings', 'Groups')" />
				<GroupListItem v-for="group in groupList"
					:id="group.id"
					:key="group.id"
					:active="selectedGroupDecoded === group.id"
					:title="group.title"
					:count="group.count" />
			</template>
			<template #footer>
				<NcAppNavigationSettings exclude-click-outside-selectors=".vs__dropdown-menu">
					<label for="default-quota-select">{{ t('settings', 'Default quota:') }}</label>
					<NcSelect v-model="defaultQuota"
						input-id="default-quota-select"
						:taggable="true"
						:options="quotaOptions"
						:create-option="validateQuota"
						:placeholder="t('settings', 'Select default quota')"
						:clearable="false"
						@option:selected="setDefaultQuota" />
					<NcCheckboxRadioSwitch type="switch"
						data-test="showLanguages"
						:checked.sync="showLanguages">
						{{ t('settings', 'Show languages') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch type="switch"
						data-test="showLastLogin"
						:checked.sync="showLastLogin">
						{{ t('settings', 'Show last login') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch type="switch"
						data-test="showUserBackend"
						:checked.sync="showUserBackend">
						{{ t('settings', 'Show user backend') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch type="switch"
						data-test="showStoragePath"
						:checked.sync="showStoragePath">
						{{ t('settings', 'Show storage path') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch type="switch"
						data-test="sendWelcomeMail"
						:checked.sync="sendWelcomeMail"
						:disabled="loadingSendMail">
						{{ t('settings', 'Send email to new user') }}
					</NcCheckboxRadioSwitch>
				</NcAppNavigationSettings>
			</template>
		</NcAppNavigation>
		<NcAppContent>
			<UserList :selected-group="selectedGroupDecoded"
				:external-actions="externalActions" />
		</NcAppContent>
	</NcContent>
</template>

<script>
import Vue from 'vue'
import VueLocalStorage from 'vue-localstorage'

import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation.js'
import NcAppNavigationCaption from '@nextcloud/vue/dist/Components/NcAppNavigationCaption.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcAppNavigationNew from '@nextcloud/vue/dist/Components/NcAppNavigationNew.js'
import NcAppNavigationNewItem from '@nextcloud/vue/dist/Components/NcAppNavigationNewItem.js'
import NcAppNavigationSettings from '@nextcloud/vue/dist/Components/NcAppNavigationSettings.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcCounterBubble from '@nextcloud/vue/dist/Components/NcCounterBubble.js'
import NcContent from '@nextcloud/vue/dist/Components/NcContent.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'

import Plus from 'vue-material-design-icons/Plus.vue'

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

import GroupListItem from '../components/GroupListItem.vue'
import UserList from '../components/UserList.vue'
import { unlimitedQuota } from '../utils/userUtils.ts'

Vue.use(VueLocalStorage)

export default {
	name: 'Users',
	components: {
		GroupListItem,
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationCaption,
		NcAppNavigationItem,
		NcAppNavigationNew,
		NcAppNavigationNewItem,
		NcAppNavigationSettings,
		NcCheckboxRadioSwitch,
		NcCounterBubble,
		NcContent,
		NcSelect,
		Plus,
		UserList,
	},
	props: {
		selectedGroup: {
			type: String,
			default: null,
		},
	},
	data() {
		return {
			// temporary value used for multiselect change
			selectedQuota: false,
			externalActions: [],
			loadingAddGroup: false,
			loadingSendMail: false,
		}
	},
	computed: {
		showConfig() {
			return this.$store.getters.getShowConfig
		},
		selectedGroupDecoded() {
			return this.selectedGroup ? decodeURIComponent(this.selectedGroup) : null
		},
		users() {
			return this.$store.getters.getUsers
		},
		groups() {
			return this.$store.getters.getGroups
		},
		usersOffset() {
			return this.$store.getters.getUsersOffset
		},
		usersLimit() {
			return this.$store.getters.getUsersLimit
		},

		// Local settings
		showLanguages: {
			get() {
				return this.getLocalstorage('showLanguages')
			},
			set(status) {
				this.setLocalStorage('showLanguages', status)
			},
		},
		showLastLogin: {
			get() {
				return this.getLocalstorage('showLastLogin')
			},
			set(status) {
				this.setLocalStorage('showLastLogin', status)
			},
		},
		showUserBackend: {
			get() {
				return this.getLocalstorage('showUserBackend')
			},
			set(status) {
				this.setLocalStorage('showUserBackend', status)
			},
		},
		showStoragePath: {
			get() {
				return this.getLocalstorage('showStoragePath')
			},
			set(status) {
				this.setLocalStorage('showStoragePath', status)
			},
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
			const quotaPreset = this.settings.quotaPreset.reduce((acc, cur) => acc.concat({ id: cur, label: cur }), [])
			// add default presets
			if (this.settings.allowUnlimitedQuota) {
				quotaPreset.unshift(unlimitedQuota)
			}
			return quotaPreset
		},
		// mapping saved values to objects
		defaultQuota: {
			get() {
				if (this.selectedQuota !== false) {
					return this.selectedQuota
				}
				if (this.settings.defaultQuota !== unlimitedQuota.id && OC.Util.computerFileSize(this.settings.defaultQuota) >= 0) {
					// if value is valid, let's map the quotaOptions or return custom quota
					return { id: this.settings.defaultQuota, label: this.settings.defaultQuota }
				}
				return unlimitedQuota // unlimited
			},
			set(quota) {
				this.selectedQuota = quota
			},

		},

		sendWelcomeMail: {
			get() {
				return this.settings.newUserSendEmail
			},
			async set(value) {
				try {
					this.loadingSendMail = true
					this.$store.commit('setServerData', {
						...this.settings,
						newUserSendEmail: value,
					})
					await axios.post(generateUrl('/settings/users/preferences/newUser.sendEmail'), { value: value ? 'yes' : 'no' })
				} catch (e) {
					console.error('could not update newUser.sendEmail preference: ' + e.message, e)
				} finally {
					this.loadingSendMail = false
				}
			},
		},

		groupList() {
			const groups = Array.isArray(this.groups) ? this.groups : []

			return groups
				// filter out disabled and admin
				.filter(group => group.id !== 'disabled' && group.id !== 'admin')
				.map(group => this.formatGroupMenu(group))
		},

		adminGroupMenu() {
			return this.formatGroupMenu(this.groups.find(group => group.id === 'admin'))
		},
		disabledGroupMenu() {
			return this.formatGroupMenu(this.groups.find(group => group.id === 'disabled'))
		},
	},
	beforeMount() {
		this.$store.commit('initGroups', {
			groups: this.$store.getters.getServerData.groups,
			orderBy: this.$store.getters.getServerData.sortGroups,
			userCount: this.$store.getters.getServerData.userCount,
		})
		this.$store.dispatch('getPasswordPolicyMinLength')
	},
	created() {
		// init the OCA.Settings.UserList object
		// and add the registerAction method
		Object.assign(OCA, {
			Settings: {
				UserList: {
					registerAction: this.registerAction,
				},
			},
		})
	},
	methods: {
		showNewUserMenu() {
			this.$store.commit('setShowConfig', {
				key: 'showNewUserForm',
				value: true,
			})
		},
		getLocalstorage(key) {
			// force initialization
			const localConfig = this.$localStorage.get(key)
			// if localstorage is null, fallback to original values
			this.$store.commit('setShowConfig', { key, value: localConfig !== null ? localConfig === 'true' : this.showConfig[key] })
			return this.showConfig[key]
		},
		setLocalStorage(key, status) {
			this.$store.commit('setShowConfig', { key, value: status })
			this.$localStorage.set(key, status)
			return status
		},

		/**
		 * Dispatch default quota set request
		 *
		 * @param {string | object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
		 */
		setDefaultQuota(quota = 'none') {
			// Make sure correct label is set for unlimited quota
			if (quota === 'none') {
				quota = unlimitedQuota
			}
			this.$store.dispatch('setAppConfig', {
				app: 'files',
				key: 'default_quota',
				// ensure we only send the preset id
				value: quota.id ? quota.id : quota,
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
		 * @param {string | object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
		 * @return {object} The validated quota object or unlimited quota if input is invalid
		 */
		validateQuota(quota) {
			if (typeof quota === 'object') {
				quota = quota?.id || quota.label
			}
			// only used for new presets sent through @Tag
			const validQuota = OC.Util.computerFileSize(quota)
			if (validQuota === null) {
				return unlimitedQuota
			} else {
				// unify format output
				quota = OC.Util.humanFileSize(OC.Util.computerFileSize(quota))
				return { id: quota, label: quota }
			}
		},

		/**
		 * Register a new action for the user menu
		 *
		 * @param {string} icon the icon class
		 * @param {string} text the text to display
		 * @param {Function} action the function to run
		 * @return {Array}
		 */
		registerAction(icon, text, action) {
			this.externalActions.push({
				icon,
				text,
				action,
			})
			return this.externalActions
		},

		/**
		 * Create a new group
		 *
		 * @param {string} gid The group id
		 */
		async createGroup(gid) {
			// group is not valid
			if (gid.trim() === '') {
				return
			}

			try {
				this.loadingAddGroup = true
				await this.$store.dispatch('addGroup', gid.trim())

				this.hideAddGroupForm()
				await this.$router.push({
					name: 'group',
					params: {
						selectedGroup: encodeURIComponent(gid.trim()),
					},
				})
			} catch {
				this.showAddGroupForm()
			} finally {
				this.loadingAddGroup = false
			}
		},

		showAddGroupForm() {
			this.$refs.addGroup.newItemActive = true
			this.$nextTick(() => {
				this.$refs.addGroup.$refs.newItemInput.focusInput()
			})
		},

		hideAddGroupForm() {
			this.$refs.addGroup.newItemActive = false
			this.$refs.addGroup.newItemValue = ''
		},

		/**
		 * Format a group to a menu entry
		 *
		 * @param {object} group the group
		 * @return {object}
		 */
		formatGroupMenu(group) {
			const item = {}
			if (typeof group === 'undefined') {
				return {}
			}

			item.id = group.id
			item.title = group.name
			item.usercount = group.usercount

			// users count for all groups
			if (group.usercount - group.disabled > 0) {
				item.count = group.usercount - group.disabled
			}

			return item
		},
	},
}
</script>

<style lang="scss" scoped>
.app-content {
	// Virtual list needs to be full height and is scrollable
	display: flex;
	overflow: hidden;
	flex-direction: column;
	max-height: 100%;
}

// force hiding the editing action for the add group entry
.app-navigation__list #addgroup::v-deep .app-navigation-entry__utils {
	display: none;
}
</style>
