<!--
  - @copyright Copyright (c) 2019 Gary Kim <gary@garykim.dev>
  - @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
  -
	- @author Christopher Ng <chrng8@gmail.com>
  - @author Gary Kim <gary@garykim.dev>
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
	<tr class="user-list__row"
		:data-cy-user-row="user.id">
		<td class="row__cell row__cell--avatar" data-cy-user-list-cell-avatar>
			<NcLoadingIcon v-if="isLoadingUser"
				:name="t('settings', 'Loading user …')"
				:size="32" />
			<NcAvatar v-else-if="visible"
				disable-menu
				:show-user-status="false"
				:user="user.id" />
		</td>

		<td class="row__cell row__cell--displayname" data-cy-user-list-cell-displayname>
			<template v-if="editing && user.backendCapabilities.setDisplayName">
				<NcTextField ref="displayNameField"
					class="user-row-text-field"
					data-cy-user-list-input-displayname
					:data-loading="loading.displayName || undefined"
					:trailing-button-label="t('settings', 'Submit')"
					:class="{ 'icon-loading-small': loading.displayName }"
					:show-trailing-button="true"
					:disabled="loading.displayName || isLoadingField"
					:label="t('settings', 'Change display name')"
					trailing-button-icon="arrowRight"
					:value.sync="editedDisplayName"
					autocapitalize="off"
					autocomplete="off"
					spellcheck="false"
					@trailing-button-click="updateDisplayName" />
			</template>
			<template v-else>
				<strong v-if="!isObfuscated"
					:title="user.displayname?.length > 20 ? user.displayname : null">
					{{ user.displayname }}
				</strong>
				<span class="row__subtitle">{{ user.id }}</span>
			</template>
		</td>

		<td data-cy-user-list-cell-password
			class="row__cell"
			:class="{ 'row__cell--obfuscated': hasObfuscated }">
			<template v-if="editing && settings.canChangePassword && user.backendCapabilities.setPassword">
				<NcTextField class="user-row-text-field"
					data-cy-user-list-input-password
					:data-loading="loading.password || undefined"
					:trailing-button-label="t('settings', 'Submit')"
					:class="{'icon-loading-small': loading.password}"
					:show-trailing-button="true"
					:disabled="loading.password || isLoadingField"
					:minlength="minPasswordLength"
					maxlength="469"
					:label="t('settings', 'Set new password')"
					trailing-button-icon="arrowRight"
					:value.sync="editedPassword"
					autocapitalize="off"
					autocomplete="new-password"
					required
					spellcheck="false"
					type="password"
					@trailing-button-click="updatePassword" />
			</template>
			<span v-else-if="isObfuscated">
				{{ t('settings', 'You do not have permissions to see the details of this user') }}
			</span>
		</td>

		<td class="row__cell" data-cy-user-list-cell-email>
			<template v-if="editing">
				<NcTextField class="user-row-text-field"
					:class="{'icon-loading-small': loading.mailAddress}"
					data-cy-user-list-input-email
					:data-loading="loading.mailAddress || undefined"
					:show-trailing-button="true"
					:trailing-button-label="t('settings', 'Submit')"
					:label="t('settings', 'Set new email address')"
					:disabled="loading.mailAddress || isLoadingField"
					trailing-button-icon="arrowRight"
					:value.sync="editedMail"
					autocapitalize="off"
					autocomplete="email"
					spellcheck="false"
					type="email"
					@trailing-button-click="updateEmail" />
			</template>
			<span v-else-if="!isObfuscated"
				:title="user.email?.length > 20 ? user.email : null">
				{{ user.email }}
			</span>
		</td>

		<td class="row__cell row__cell--large row__cell--multiline" data-cy-user-list-cell-groups>
			<template v-if="editing">
				<label class="hidden-visually"
					:for="'groups' + uniqueId">
					{{ t('settings', 'Add user to group') }}
				</label>
				<NcSelect data-cy-user-list-input-groups
					:data-loading="loading.groups || undefined"
					:input-id="'groups' + uniqueId"
					:close-on-select="false"
					:disabled="isLoadingField"
					:loading="loading.groups"
					:multiple="true"
					:append-to-body="false"
					:options="availableGroups"
					:placeholder="t('settings', 'Add user to group')"
					:taggable="settings.isAdmin"
					:value="userGroups"
					label="name"
					:no-wrap="true"
					:create-option="(value) => ({ name: value, isCreating: true })"
					@option:created="createGroup"
					@option:selected="options => addUserGroup(options.at(-1))"
					@option:deselected="removeUserGroup" />
			</template>
			<span v-else-if="!isObfuscated"
				:title="userGroupsLabels?.length > 40 ? userGroupsLabels : null">
				{{ userGroupsLabels }}
			</span>
		</td>

		<td v-if="subAdminsGroups.length > 0 && settings.isAdmin"
			data-cy-user-list-cell-subadmins
			class="row__cell row__cell--large row__cell--multiline">
			<template v-if="editing && settings.isAdmin && subAdminsGroups.length > 0">
				<label class="hidden-visually"
					:for="'subadmins' + uniqueId">
					{{ t('settings', 'Set user as admin for') }}
				</label>
				<NcSelect data-cy-user-list-input-subadmins
					:data-loading="loading.subadmins || undefined"
					:input-id="'subadmins' + uniqueId"
					:close-on-select="false"
					:disabled="isLoadingField"
					:loading="loading.subadmins"
					label="name"
					:append-to-body="false"
					:multiple="true"
					:no-wrap="true"
					:options="subAdminsGroups"
					:placeholder="t('settings', 'Set user as admin for')"
					:value="userSubAdminsGroups"
					@option:deselected="removeUserSubAdmin"
					@option:selected="options => addUserSubAdmin(options.at(-1))" />
			</template>
			<span v-else-if="!isObfuscated"
				:title="userSubAdminsGroupsLabels?.length > 40 ? userSubAdminsGroupsLabels : null">
				{{ userSubAdminsGroupsLabels }}
			</span>
		</td>

		<td class="row__cell" data-cy-user-list-cell-quota>
			<template v-if="editing">
				<label class="hidden-visually"
					:for="'quota' + uniqueId">
					{{ t('settings', 'Select user quota') }}
				</label>
				<NcSelect v-model="editedUserQuota"
					:close-on-select="true"
					:create-option="validateQuota"
					data-cy-user-list-input-quota
					:data-loading="loading.quota || undefined"
					:disabled="isLoadingField"
					:loading="loading.quota"
					:append-to-body="false"
					:clearable="false"
					:input-id="'quota' + uniqueId"
					:options="quotaOptions"
					:placeholder="t('settings', 'Select user quota')"
					:taggable="true"
					@option:selected="setUserQuota" />
			</template>
			<template v-else-if="!isObfuscated">
				<span :id="'quota-progress' + uniqueId">{{ userQuota }} ({{ usedSpace }})</span>
				<NcProgressBar :aria-labelledby="'quota-progress' + uniqueId"
					class="row__progress"
					:class="{
						'row__progress--warn': usedQuota > 80,
					}"
					:value="usedQuota" />
			</template>
		</td>

		<td v-if="showConfig.showLanguages"
			class="row__cell row__cell--large"
			data-cy-user-list-cell-language>
			<template v-if="editing">
				<label class="hidden-visually"
					:for="'language' + uniqueId">
					{{ t('settings', 'Set the language') }}
				</label>
				<NcSelect :id="'language' + uniqueId"
					data-cy-user-list-input-language
					:data-loading="loading.languages || undefined"
					:allow-empty="false"
					:disabled="isLoadingField"
					:loading="loading.languages"
					:clearable="false"
					:append-to-body="false"
					:options="availableLanguages"
					:placeholder="t('settings', 'No language set')"
					:value="userLanguage"
					label="name"
					@input="setUserLanguage" />
			</template>
			<span v-else-if="!isObfuscated">
				{{ userLanguage.name }}
			</span>
		</td>

		<td v-if="showConfig.showUserBackend || showConfig.showStoragePath"
			data-cy-user-list-cell-storage-location
			class="row__cell row__cell--large">
			<template v-if="!isObfuscated">
				<span v-if="showConfig.showUserBackend">{{ user.backend }}</span>
				<span v-if="showConfig.showStoragePath"
					:title="user.storageLocation"
					class="row__subtitle">
					{{ user.storageLocation }}
				</span>
			</template>
		</td>

		<td v-if="showConfig.showLastLogin"
			:title="userLastLoginTooltip"
			class="row__cell"
			data-cy-user-list-cell-last-login>
			<span v-if="!isObfuscated">{{ userLastLogin }}</span>
		</td>

		<td class="row__cell row__cell--large row__cell--fill" data-cy-user-list-cell-manager>
			<template v-if="editing">
				<label class="hidden-visually"
					:for="'manager' + uniqueId">
					{{ managerLabel }}
				</label>
				<NcSelect v-model="currentManager"
					class="select--fill"
					data-cy-user-list-input-manager
					:data-loading="loading.manager || undefined"
					:input-id="'manager' + uniqueId"
					:close-on-select="true"
					:disabled="isLoadingField"
					:append-to-body="false"
					:loading="loadingPossibleManagers || loading.manager"
					label="displayname"
					:options="possibleManagers"
					:placeholder="managerLabel"
					@open="searchInitialUserManager"
					@search="searchUserManager"
					@option:selected="updateUserManager"
					@input="updateUserManager" />
			</template>
			<span v-else-if="!isObfuscated">
				{{ user.manager }}
			</span>
		</td>

		<td class="row__cell row__cell--actions" data-cy-user-list-cell-actions>
			<UserRowActions v-if="visible && !isObfuscated && canEdit && !loading.all"
				:actions="userActions"
				:disabled="isLoadingField"
				:edit="editing"
				:user="user"
				@update:edit="toggleEdit" />
		</td>
	</tr>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { showSuccess, showError } from '@nextcloud/dialogs'

import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcProgressBar from '@nextcloud/vue/dist/Components/NcProgressBar.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import UserRowActions from './UserRowActions.vue'

import UserRowMixin from '../../mixins/UserRowMixin.js'
import { isObfuscated, unlimitedQuota } from '../../utils/userUtils.ts'

export default {
	name: 'UserRow',

	components: {
		NcAvatar,
		NcLoadingIcon,
		NcProgressBar,
		NcSelect,
		NcTextField,
		UserRowActions,
	},

	mixins: [
		UserRowMixin,
	],

	props: {
		user: {
			type: Object,
			required: true,
		},
		visible: {
			type: Boolean,
			required: true,
		},
		users: {
			type: Array,
			required: true,
		},
		hasObfuscated: {
			type: Boolean,
			required: true,
		},
		groups: {
			type: Array,
			default: () => [],
		},
		subAdminsGroups: {
			type: Array,
			required: true,
		},
		quotaOptions: {
			type: Array,
			required: true,
		},
		languages: {
			type: Array,
			required: true,
		},
		settings: {
			type: Object,
			required: true,
		},
		externalActions: {
			type: Array,
			default: () => [],
		},
	},

	data() {
		return {
			selectedQuota: false,
			rand: Math.random().toString(36).substring(2),
			loadingPossibleManagers: false,
			possibleManagers: [],
			currentManager: '',
			editing: false,
			loading: {
				all: false,
				displayName: false,
				password: false,
				mailAddress: false,
				groups: false,
				subadmins: false,
				quota: false,
				delete: false,
				disable: false,
				languages: false,
				wipe: false,
				manager: false,
			},
			editedDisplayName: this.user.displayname,
			editedPassword: '',
			editedMail: this.user.email ?? '',
		}
	},

	computed: {
		managerLabel() {
			// TRANSLATORS This string describes a manager in the context of an organization
			return t('settings', 'Set user manager')
		},

		isObfuscated() {
			return isObfuscated(this.user)
		},

		showConfig() {
			return this.$store.getters.getShowConfig
		},

		isLoadingUser() {
			return this.loading.delete || this.loading.disable || this.loading.wipe
		},

		isLoadingField() {
			return this.loading.delete || this.loading.disable || this.loading.all
		},

		uniqueId() {
			return encodeURIComponent(this.user.id + this.rand)
		},

		userGroupsLabels() {
			return this.userGroups
				.map(group => group.name)
				.join(', ')
		},

		userSubAdminsGroupsLabels() {
			return this.userSubAdminsGroups
				.map(group => group.name)
				.join(', ')
		},

		usedSpace() {
			if (this.user.quota?.used) {
				return t('settings', '{size} used', { size: OC.Util.humanFileSize(this.user.quota?.used) })
			}
			return t('settings', '{size} used', { size: OC.Util.humanFileSize(0) })
		},

		canEdit() {
			return getCurrentUser().uid !== this.user.id || this.settings.isAdmin
		},

		userQuota() {
			let quota = this.user.quota?.quota

			if (quota === 'default') {
				quota = this.settings.defaultQuota
				if (quota !== 'none') {
					// convert to numeric value to match what the server would usually return
					quota = OC.Util.computerFileSize(quota)
				}
			}

			// when the default quota is unlimited, the server returns -3 here, map it to "none"
			if (quota === 'none' || quota === -3) {
				return t('settings', 'Unlimited')
			} else if (quota >= 0) {
				return OC.Util.humanFileSize(quota)
			}
			return OC.Util.humanFileSize(0)
		},

		userActions() {
			const actions = [
				{
					icon: 'icon-delete',
					text: t('settings', 'Delete user'),
					action: this.deleteUser,
				},
				{
					icon: 'icon-delete',
					text: t('settings', 'Wipe all devices'),
					action: this.wipeUserDevices,
				},
				{
					icon: this.user.enabled ? 'icon-close' : 'icon-add',
					text: this.user.enabled ? t('settings', 'Disable user') : t('settings', 'Enable user'),
					action: this.enableDisableUser,
				},
			]
			if (this.user.email !== null && this.user.email !== '') {
				actions.push({
					icon: 'icon-mail',
					text: t('settings', 'Resend welcome email'),
					action: this.sendWelcomeMail,
				})
			}
			return actions.concat(this.externalActions)
		},

		// mapping saved values to objects
		editedUserQuota: {
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

		availableLanguages() {
			return this.languages[0].languages.concat(this.languages[1].languages)
		},
	},

	async beforeMount() {
		if (this.user.manager) {
			await this.initManager(this.user.manager)
		}
	},

	methods: {
		wipeUserDevices() {
			const userid = this.user.id
			OC.dialogs.confirmDestructive(
				t('settings', 'In case of lost device or exiting the organization, this can remotely wipe the Nextcloud data from all devices associated with {userid}. Only works if the devices are connected to the internet.', { userid }),
				t('settings', 'Remote wipe of devices'),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: t('settings', 'Wipe {userid}\'s devices', { userid }),
					confirmClasses: 'error',
					cancel: t('settings', 'Cancel'),
				},
				(result) => {
					if (result) {
						this.loading.wipe = true
						this.loading.all = true
						this.$store.dispatch('wipeUserDevices', userid)
							.then(() => showSuccess(t('settings', 'Wiped {userid}\'s devices', { userid })), { timeout: 2000 })
							.finally(() => {
								this.loading.wipe = false
								this.loading.all = false
							})
					}
				},
				true,
			)
		},

		filterManagers(managers) {
			return managers.filter((manager) => manager.id !== this.user.id)
		},

		async initManager(userId) {
			await this.$store.dispatch('getUser', userId).then(response => {
				this.currentManager = response?.data.ocs.data
			})
		},

		async searchInitialUserManager() {
			this.loadingPossibleManagers = true
			await this.searchUserManager()
			this.loadingPossibleManagers = false
		},

		async searchUserManager(query) {
			await this.$store.dispatch('searchUsers', { offset: 0, limit: 10, search: query }).then(response => {
				const users = response?.data ? this.filterManagers(Object.values(response?.data.ocs.data.users)) : []
				if (users.length > 0) {
					this.possibleManagers = users
				}
			})
		},

		async updateUserManager(manager) {
			if (manager === null) {
				this.currentManager = ''
			}
			this.loading.manager = true
			try {
				await this.$store.dispatch('setUserData', {
					userid: this.user.id,
					key: 'manager',
					value: this.currentManager ? this.currentManager.id : '',
				})
			} catch (error) {
				// TRANSLATORS This string describes a manager in the context of an organization
				showError(t('setting', 'Failed to update user manager'))
				console.error(error)
			} finally {
				this.loading.manager = false
			}
		},

		deleteUser() {
			const userid = this.user.id
			OC.dialogs.confirmDestructive(
				t('settings', 'Fully delete {userid}\'s account including all their personal files, app data, etc.', { userid }),
				t('settings', 'Account deletion'),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: t('settings', 'Delete {userid}\'s account', { userid }),
					confirmClasses: 'error',
					cancel: t('settings', 'Cancel'),
				},
				(result) => {
					if (result) {
						this.loading.delete = true
						this.loading.all = true
						return this.$store.dispatch('deleteUser', userid)
							.then(() => {
								this.loading.delete = false
								this.loading.all = false
							})
					}
				},
				true,
			)
		},

		enableDisableUser() {
			this.loading.delete = true
			this.loading.all = true
			const userid = this.user.id
			const enabled = !this.user.enabled
			return this.$store.dispatch('enableDisableUser', {
				userid,
				enabled,
			})
				.then(() => {
					this.loading.delete = false
					this.loading.all = false
				})
		},

		/**
		 * Set user displayName
		 *
		 * @param {string} displayName The display name
		 */
		updateDisplayName() {
			this.loading.displayName = true
			this.$store.dispatch('setUserData', {
				userid: this.user.id,
				key: 'displayname',
				value: this.editedDisplayName,
			}).then(() => {
				this.loading.displayName = false
				if (this.editedDisplayName === this.user.displayname) {
					showSuccess(t('setting', 'Display name was successfully changed'))
				}
			})
		},

		/**
		 * Set user password
		 *
		 * @param {string} password The email address
		 */
		updatePassword() {
			this.loading.password = true
			if (this.editedPassword.length === 0) {
				showError(t('setting', "Password can't be empty"))
				this.loading.password = false
			} else {
				this.$store.dispatch('setUserData', {
					userid: this.user.id,
					key: 'password',
					value: this.editedPassword,
				}).then(() => {
					this.loading.password = false
					this.editedPassword = ''
					showSuccess(t('setting', 'Password was successfully changed'))
				})
			}
		},

		/**
		 * Set user mailAddress
		 *
		 * @param {string} mailAddress The email address
		 */
		updateEmail() {
			this.loading.mailAddress = true
			if (this.editedMail === '') {
				showError(t('setting', "Email can't be empty"))
				this.loading.mailAddress = false
				this.editedMail = this.user.email
			} else {
				this.$store.dispatch('setUserData', {
					userid: this.user.id,
					key: 'email',
					value: this.editedMail,
				}).then(() => {
					this.loading.mailAddress = false
					if (this.editedMail === this.user.email) {
						showSuccess(t('setting', 'Email was successfully changed'))
					}
				})
			}
		},

		/**
		 * Create a new group and add user to it
		 *
		 * @param {string} gid Group id
		 */
		async createGroup({ name: gid }) {
			this.loading = { groups: true, subadmins: true }
			try {
				await this.$store.dispatch('addGroup', gid)
				const userid = this.user.id
				await this.$store.dispatch('addUserGroup', { userid, gid })
			} catch (error) {
				console.error(error)
			} finally {
				this.loading = { groups: false, subadmins: false }
			}
			return this.$store.getters.getGroups[this.groups.length]
		},

		/**
		 * Add user to group
		 *
		 * @param {object} group Group object
		 */
		async addUserGroup(group) {
			if (group.isCreating) {
				// This is NcSelect's internal value for a new inputted group name
				// Ignore
				return
			}
			this.loading.groups = true
			const userid = this.user.id
			const gid = group.id
			if (group.canAdd === false) {
				return false
			}
			try {
				await this.$store.dispatch('addUserGroup', { userid, gid })
			} catch (error) {
				console.error(error)
			} finally {
				this.loading.groups = false
			}
		},

		/**
		 * Remove user from group
		 *
		 * @param {object} group Group object
		 */
		async removeUserGroup(group) {
			if (group.canRemove === false) {
				return false
			}
			this.loading.groups = true
			const userid = this.user.id
			const gid = group.id
			try {
				await this.$store.dispatch('removeUserGroup', {
					userid,
					gid,
				})
				this.loading.groups = false
				// remove user from current list if current list is the removed group
				if (this.$route.params.selectedGroup === gid) {
					this.$store.commit('deleteUser', userid)
				}
			} catch {
				this.loading.groups = false
			}
		},

		/**
		 * Add user to group
		 *
		 * @param {object} group Group object
		 */
		async addUserSubAdmin(group) {
			this.loading.subadmins = true
			const userid = this.user.id
			const gid = group.id
			try {
				await this.$store.dispatch('addUserSubAdmin', {
					userid,
					gid,
				})
				this.loading.subadmins = false
			} catch (error) {
				console.error(error)
			}
		},

		/**
		 * Remove user from group
		 *
		 * @param {object} group Group object
		 */
		async removeUserSubAdmin(group) {
			this.loading.subadmins = true
			const userid = this.user.id
			const gid = group.id

			try {
				await this.$store.dispatch('removeUserSubAdmin', {
					userid,
					gid,
				})
			} catch (error) {
				console.error(error)
			} finally {
				this.loading.subadmins = false
			}
		},

		/**
		 * Dispatch quota set request
		 *
		 * @param {string | object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
		 * @return {string}
		 */
		async setUserQuota(quota = 'none') {
			// Make sure correct label is set for unlimited quota
			if (quota === 'none') {
				quota = unlimitedQuota
			}
			this.loading.quota = true
			// ensure we only send the preset id
			quota = quota.id ? quota.id : quota

			try {
				await this.$store.dispatch('setUserData', {
					userid: this.user.id,
					key: 'quota',
					value: quota,
				})
			} catch (error) {
				console.error(error)
			} finally {
				this.loading.quota = false
			}
			return quota
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
		 * Dispatch language set request
		 *
		 * @param {object} lang language object {code:'en', name:'English'}
		 * @return {object}
		 */
		async setUserLanguage(lang) {
			this.loading.languages = true
			// ensure we only send the preset id
			try {
				await this.$store.dispatch('setUserData', {
					userid: this.user.id,
					key: 'language',
					value: lang.code,
				})
				this.loading.languages = false
			} catch (error) {
				console.error(error)
			}
			return lang
		},

		/**
		 * Dispatch new welcome mail request
		 */
		sendWelcomeMail() {
			this.loading.all = true
			this.$store.dispatch('sendWelcomeMail', this.user.id)
				.then(() => showSuccess(t('setting', 'Welcome mail sent!'), { timeout: 2000 }))
				.finally(() => {
					this.loading.all = false
				})
		},

		async toggleEdit() {
			this.editing = !this.editing
			if (this.editing) {
				await this.$nextTick()
				this.$refs.displayNameField?.$refs?.inputField?.$refs?.input?.focus()
			}
			if (this.editedDisplayName !== this.user.displayname) {
				this.editedDisplayName = this.user.displayname
			} else if (this.editedMail !== this.user.email) {
				this.editedMail = this.user.email ?? ''
			}
		},
	},
}
</script>

<style lang="scss" scoped>
@import './shared/styles.scss';

.user-list__row {
	@include row;

	&:hover {
		background-color: var(--color-background-hover);

		.row__cell:not(.row__cell--actions) {
			background-color: var(--color-background-hover);
		}
	}

	// Limit width of select in fill cell
	.select--fill {
		max-width: calc(var(--cell-width-large) - (2 * var(--cell-padding)));
	}
}

.row {
	@include cell;

	&__cell {
		border-bottom: 1px solid var(--color-border);

		:deep {
			.input-field,
			.input-field__main-wrapper,
			.input-field__input {
				height: 48px !important;
			}

			.input-field__input {
				&:placeholder-shown:not(:focus) + .input-field__label {
					inset-block-start: 16px !important;
				}
			}

			.button-vue--icon-only {
				height: 44px !important;
			}

			.v-select.select {
				min-width: var(--cell-min-width);
			}
		}
	}

	&__progress {
		margin-top: 4px;

		&--warn {
			&::-moz-progress-bar {
				background: var(--color-warning) !important;
			}
			&::-webkit-progress-value {
				background: var(--color-warning) !important;
			}
		}
	}
}
</style>
