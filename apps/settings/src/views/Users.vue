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
	<Fragment>
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
						:name="t('settings', 'Add group')"
						@click="showAddGroupForm"
						@new-item="createGroup">
						<template #icon>
							<Plus :size="20" />
						</template>
					</NcAppNavigationNewItem>
					<NcAppNavigationItem id="everyone"
						:exact="true"
						:name="t('settings', 'Active users')"
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
						:name="t('settings', 'Admins')"
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
						:name="t('settings', 'Disabled users')"
						:to="{ name: 'group', params: { selectedGroup: 'disabled' } }"
						icon="icon-disabled-users">
						<template v-if="disabledGroupMenu.usercount > 0" #counter>
							<NcCounterBubble :type="selectedGroupDecoded === 'disabled' ? 'highlighted' : undefined">
								{{ disabledGroupMenu.usercount }}
							</NcCounterBubble>
						</template>
					</NcAppNavigationItem>

					<NcAppNavigationCaption v-if="groupList.length > 0" :name="t('settings', 'Groups')" />
					<GroupListItem v-for="group in groupList"
						:id="group.id"
						:key="group.id"
						:active="selectedGroupDecoded === group.id"
						:name="group.title"
						:count="group.count" />
				</template>

				<template #footer>
					<ul class="app-navigation-entry__settings">
						<NcAppNavigationItem :name="t('settings', 'User management settings')"
							@click="isDialogOpen = true">
							<template #icon>
								<Cog :size="20" />
							</template>
						</NcAppNavigationItem>
					</ul>
				</template>
			</NcAppNavigation>

			<NcAppContent>
				<UserList :selected-group="selectedGroupDecoded"
					:external-actions="externalActions" />
			</NcAppContent>
		</NcContent>

		<UserSettingsDialog :open.sync="isDialogOpen" />
	</Fragment>
</template>

<script>
import Vue from 'vue'
import VueLocalStorage from 'vue-localstorage'
import { Fragment } from 'vue-frag'

import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation.js'
import NcAppNavigationCaption from '@nextcloud/vue/dist/Components/NcAppNavigationCaption.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcAppNavigationNew from '@nextcloud/vue/dist/Components/NcAppNavigationNew.js'
import NcAppNavigationNewItem from '@nextcloud/vue/dist/Components/NcAppNavigationNewItem.js'
import NcContent from '@nextcloud/vue/dist/Components/NcContent.js'
import NcCounterBubble from '@nextcloud/vue/dist/Components/NcCounterBubble.js'

import Cog from 'vue-material-design-icons/Cog.vue'
import Plus from 'vue-material-design-icons/Plus.vue'

import GroupListItem from '../components/GroupListItem.vue'
import UserList from '../components/UserList.vue'
import UserSettingsDialog from '../components/Users/UserSettingsDialog.vue'

Vue.use(VueLocalStorage)

export default {
	name: 'Users',

	components: {
		Cog,
		Fragment,
		GroupListItem,
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationCaption,
		NcAppNavigationItem,
		NcAppNavigationNew,
		NcAppNavigationNewItem,
		NcContent,
		NcCounterBubble,
		Plus,
		UserList,
		UserSettingsDialog,
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
			externalActions: [],
			loadingAddGroup: false,
			isDialogOpen: false,
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

		userCount() {
			return this.$store.getters.getUserCount
		},

		settings() {
			return this.$store.getters.getServerData
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

.app-navigation-entry__settings {
	height: auto !important;
	// Prevent shrinking or growing
	flex: 0 0 auto;
}
</style>
