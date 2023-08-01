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
	<Fragment>
		<td class="row__cell row__cell--avatar">
			<NcLoadingIcon v-if="isLoadingUser"
				:title="t('settings', 'Loading user …')"
				:size="32" />
			<NcAvatar v-else
				:key="user.id"
				disable-menu
				:show-user-status="false"
				:user="user.id" />
		</td>

		<td class="row__cell row__cell--displayname"
			:data-test="user.id">
			<template v-if="idState.editing && user.backendCapabilities.setDisplayName">
				<label class="hidden-visually"
					:for="'displayName' + uniqueId">
					{{ t('settings', 'Edit display name') }}
				</label>
				<NcTextField :id="'displayName' + uniqueId"
					data-test="displayNameField"
					ref="displayNameField"
					:show-trailing-button="true"
					class="user-row-text-field"
					:class="{ 'icon-loading-small': idState.loading.displayName }"
					:disabled="idState.loading.displayName || isLoadingField"
					trailing-button-icon="arrowRight"
					:value.sync="idState.editedDisplayName"
					autocapitalize="off"
					autocomplete="off"
					autocorrect="off"
					spellcheck="false"
					type="text"
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

		<td class="row__cell"
			:class="{ 'row__cell--obfuscated': hasObfuscated }">
			<template v-if="idState.editing && settings.canChangePassword && user.backendCapabilities.setPassword">
				<label class="hidden-visually"
					:for="'password' + uniqueId">
					{{ t('settings', 'Add new password') }}
				</label>
				<NcTextField :id="'password' + uniqueId"
					:show-trailing-button="true"
					class="user-row-text-field"
					:class="{'icon-loading-small': idState.loading.password}"
					:disabled="idState.loading.password || isLoadingField"
					:minlength="minPasswordLength"
					maxlength="469"
					:placeholder="t('settings', 'Add new password')"
					trailing-button-icon="arrowRight"
					:value.sync="idState.editedPassword"
					autocapitalize="off"
					autocomplete="new-password"
					autocorrect="off"
					required
					spellcheck="false"
					type="password"
					@trailing-button-click="updatePassword" />
			</template>
			<span v-else-if="isObfuscated">
				{{ t('settings', 'You do not have permissions to see the details of this user') }}
			</span>
		</td>

		<td class="row__cell">
			<template v-if="idState.editing">
				<label class="hidden-visually"
					:for="'mailAddress' + uniqueId">
					{{ t('settings', 'Add new email address') }}
				</label>
				<NcTextField :id="'mailAddress' + uniqueId"
					:show-trailing-button="true"
					class="user-row-text-field"
					:class="{'icon-loading-small': idState.loading.mailAddress}"
					:disabled="idState.loading.mailAddress || isLoadingField"
					:placeholder="t('settings', 'Add new email address')"
					trailing-button-icon="arrowRight"
					:value.sync="idState.editedMail"
					autocapitalize="off"
					autocomplete="new-password"
					autocorrect="off"
					spellcheck="false"
					type="email"
					@trailing-button-click="updateEmail" />
			</template>
			<span v-else-if="!isObfuscated"
				:title="user.email?.length > 20 ? user.email : null">
				{{ user.email }}
			</span>
		</td>

		<td class="row__cell row__cell--large row__cell--multiline">
			<template v-if="idState.editing">
				<label class="hidden-visually"
					:for="'groups' + uniqueId">
					{{ t('settings', 'Add user to group') }}
				</label>
				<NcSelect :input-id="'groups' + uniqueId"
					:close-on-select="false"
					:disabled="idState.loading.groups || isLoadingField"
					:loading="idState.loading.groups"
					:multiple="true"
					:options="availableGroups"
					:placeholder="t('settings', 'Add user to group')"
					:taggable="settings.isAdmin"
					:value="userGroups"
					class="select-vue"
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
			class="row__cell row__cell--large row__cell--multiline">
			<template v-if="idState.editing && settings.isAdmin && subAdminsGroups.length > 0">
				<label class="hidden-visually"
					:for="'subadmins' + uniqueId">
					{{ t('settings', 'Set user as admin for') }}
				</label>
				<NcSelect :id="'subadmins' + uniqueId"
					:close-on-select="false"
					:disabled="idState.loading.subadmins || isLoadingField"
					:loading="idState.loading.subadmins"
					label="name"
					:multiple="true"
					:no-wrap="true"
					:options="subAdminsGroups"
					:placeholder="t('settings', 'Set user as admin for')"
					:value="userSubAdminsGroups"
					class="select-vue"
					@option:deselected="removeUserSubAdmin"
					@option:selected="options => addUserSubAdmin(options.at(-1))" />
			</template>
			<span v-else-if="!isObfuscated"
				:title="userSubAdminsGroupsLabels?.length > 40 ? userSubAdminsGroupsLabels : null">
				{{ userSubAdminsGroupsLabels }}
			</span>
		</td>

		<td class="row__cell">
			<template v-if="idState.editing">
				<label class="hidden-visually"
					:for="'quota' + uniqueId">
					{{ t('settings', 'Select user quota') }}
				</label>
				<NcSelect v-model="editedUserQuota"
					:close-on-select="true"
					:create-option="validateQuota"
					:disabled="idState.loading.quota || isLoadingField"
					:loading="idState.loading.quota"
					:clearable="false"
					:input-id="'quota' + uniqueId"
					class="select-vue"
					:options="quotaOptions"
					:placeholder="t('settings', 'Select user quota')"
					:taggable="true"
					@option:selected="setUserQuota" />
			</template>
			<template v-else-if="!isObfuscated">
				<label :for="'quota-progress' + uniqueId">{{ userQuota }} ({{ usedSpace }})</label>
				<NcProgressBar class="row__progress"
					:id="'quota-progress' + uniqueId"
					:class="{
						'row__progress--warn': usedQuota > 80,
					}"
					:value="usedQuota" />
			</template>
		</td>

		<td v-if="showConfig.showLanguages"
			class="row__cell row__cell--large"
			data-test="language">
			<template v-if="idState.editing">
				<label class="hidden-visually"
					:for="'language' + uniqueId">
					{{ t('settings', 'Set the language') }}
				</label>
				<NcSelect :id="'language' + uniqueId"
					:allow-empty="false"
					:disabled="idState.loading.languages || isLoadingField"
					:loading="idState.loading.languages"
					:clearable="false"
					:options="availableLanguages"
					:placeholder="t('settings', 'No language set')"
					:value="userLanguage"
					label="name"
					class="select-vue"
					@input="setUserLanguage" />
			</template>
			<span v-else-if="!isObfuscated">
				{{ userLanguage.name }}
			</span>
		</td>

		<td v-if="showConfig.showUserBackend || showConfig.showStoragePath"
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
			data-test="lastLogin">
			<span v-if="!isObfuscated">{{ userLastLogin }}</span>
		</td>

		<td class="row__cell row__cell--large">
			<template v-if="idState.editing">
				<label class="hidden-visually"
					:for="'manager' + uniqueId">
					{{ managerLabel }}
				</label>
				<NcSelect v-model="idState.currentManager"
					:input-id="'manager' + uniqueId"
					:close-on-select="true"
					:disabled="idState.loading.manager || isLoadingField"
					:loading="idState.loading.manager"
					label="displayname"
					:options="idState.possibleManagers"
					:placeholder="managerLabel"
					class="select-vue"
					@search="searchUserManager"
					@option:selected="updateUserManager"
					@input="updateUserManager" />
			</template>
			<span v-else-if="!isObfuscated">
				{{ user.manager }}
			</span>
		</td>

		<td class="row__cell row__cell--actions">
			<UserRowActions v-if="!isObfuscated && canEdit && !idState.loading.all"
				:actions="userActions"
				:disabled="isLoadingField"
				:edit="idState.editing"
				@update:edit="toggleEdit" />
		</td>
	</Fragment>
</template>

<script>
import { Fragment } from 'vue-frag'
import { IdState } from 'vue-virtual-scroller'
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
		Fragment,
		NcAvatar,
		NcLoadingIcon,
		NcProgressBar,
		NcSelect,
		NcTextField,
		UserRowActions,
	},

	mixins: [
		/**
		 * Use scoped `idState` instead of `data` which is reused between rows
		 *
		 * See https://github.com/Akryum/vue-virtual-scroller/tree/v1/packages/vue-virtual-scroller#why-is-this-useful
		 */
		IdState({
			idProp: vm => vm.user.id,
		}),
		UserRowMixin,
	],

	props: {
		user: {
			type: Object,
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

	idState() {
		return {
			selectedQuota: false,
			rand: Math.random().toString(36).substring(2),
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
			// TRANSLATORS This string describes a manager in the context of an organization
			managerLabel: t('settings', 'Set user manager'),
		}
	},

	computed: {
		isObfuscated() {
			return isObfuscated(this.user)
		},

		showConfig() {
			return this.$store.getters.getShowConfig
		},

		isLoadingUser() {
			return this.idState.loading.delete || this.idState.loading.disable || this.idState.loading.wipe
		},

		isLoadingField() {
			return this.idState.loading.delete || this.idState.loading.disable || this.idState.loading.all
		},

		uniqueId() {
			return this.user.id + this.idState.rand
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
				if (this.idState.selectedQuota !== false) {
					return this.idState.selectedQuota
				}
				if (this.settings.defaultQuota !== unlimitedQuota.id && OC.Util.computerFileSize(this.settings.defaultQuota) >= 0) {
					// if value is valid, let's map the quotaOptions or return custom quota
					return { id: this.settings.defaultQuota, label: this.settings.defaultQuota }
				}
				return unlimitedQuota // unlimited
			},
			set(quota) {
				this.idState.selectedQuota = quota
			},
		},

		availableLanguages() {
			return this.languages[0].languages.concat(this.languages[1].languages)
		},
	},

	async beforeMount() {
		await this.searchUserManager()

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
						this.idState.loading.wipe = true
						this.idState.loading.all = true
						this.$store.dispatch('wipeUserDevices', userid)
							.then(() => showSuccess(t('settings', 'Wiped {userid}\'s devices', { userid })), { timeout: 2000 })
							.finally(() => {
								this.idState.loading.wipe = false
								this.idState.loading.all = false
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
				this.idState.currentManager = response?.data.ocs.data
			})
		},

		async searchUserManager(query) {
			await this.$store.dispatch('searchUsers', { offset: 0, limit: 10, search: query }).then(response => {
				const users = response?.data ? this.filterManagers(Object.values(response?.data.ocs.data.users)) : []
				if (users.length > 0) {
					this.idState.possibleManagers = users
				}
			})
		},

		updateUserManager(manager) {
			if (manager === null) {
				this.idState.currentManager = ''
			}
			this.idState.loading.manager = true
			try {
				this.$store.dispatch('setUserData', {
					userid: this.user.id,
					key: 'manager',
					value: this.idState.currentManager ? this.idState.currentManager.id : '',
				})
			} catch (error) {
				// TRANSLATORS This string describes a manager in the context of an organization
				showError(t('setting', 'Failed to update user manager'))
				console.error(error)
			} finally {
				this.idState.loading.manager = false
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
						this.idState.loading.delete = true
						this.idState.loading.all = true
						return this.$store.dispatch('deleteUser', userid)
							.then(() => {
								this.idState.loading.delete = false
								this.idState.loading.all = false
							})
					}
				},
				true,
			)
		},

		enableDisableUser() {
			this.idState.loading.delete = true
			this.idState.loading.all = true
			const userid = this.user.id
			const enabled = !this.user.enabled
			return this.$store.dispatch('enableDisableUser', {
				userid,
				enabled,
			})
				.then(() => {
					this.idState.loading.delete = false
					this.idState.loading.all = false
				})
		},

		/**
		 * Set user displayName
		 *
		 * @param {string} displayName The display name
		 */
		updateDisplayName() {
			this.idState.loading.displayName = true
			this.$store.dispatch('setUserData', {
				userid: this.user.id,
				key: 'displayname',
				value: this.idState.editedDisplayName,
			}).then(() => {
				this.idState.loading.displayName = false
				if (this.idState.editedDisplayName === this.user.displayname) {
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
			this.idState.loading.password = true
			if (this.idState.editedPassword.length === 0) {
				showError(t('setting', "Password can't be empty"))
				this.idState.loading.password = false
			} else {
				this.$store.dispatch('setUserData', {
					userid: this.user.id,
					key: 'password',
					value: this.idState.editedPassword,
				}).then(() => {
					this.idState.loading.password = false
					this.idState.editedPassword = ''
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
			this.idState.loading.mailAddress = true
			if (this.idState.editedMail === '') {
				showError(t('setting', "Email can't be empty"))
				this.idState.loading.mailAddress = false
				this.idState.editedMail = this.user.email
			} else {
				this.$store.dispatch('setUserData', {
					userid: this.user.id,
					key: 'email',
					value: this.idState.editedMail,
				}).then(() => {
					this.idState.loading.mailAddress = false
					if (this.idState.editedMail === this.user.email) {
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
			this.idState.loading = { groups: true, subadmins: true }
			try {
				await this.$store.dispatch('addGroup', gid)
				const userid = this.user.id
				await this.$store.dispatch('addUserGroup', { userid, gid })
			} catch (error) {
				console.error(error)
			} finally {
				this.idState.loading = { groups: false, subadmins: false }
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
			this.idState.loading.groups = true
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
				this.idState.loading.groups = false
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
			this.idState.loading.groups = true
			const userid = this.user.id
			const gid = group.id
			try {
				await this.$store.dispatch('removeUserGroup', {
					userid,
					gid,
				})
				this.idState.loading.groups = false
				// remove user from current list if current list is the removed group
				if (this.$route.params.selectedGroup === gid) {
					this.$store.commit('deleteUser', userid)
				}
			} catch {
				this.idState.loading.groups = false
			}
		},

		/**
		 * Add user to group
		 *
		 * @param {object} group Group object
		 */
		async addUserSubAdmin(group) {
			this.idState.loading.subadmins = true
			const userid = this.user.id
			const gid = group.id
			try {
				await this.$store.dispatch('addUserSubAdmin', {
					userid,
					gid,
				})
				this.idState.loading.subadmins = false
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
			this.idState.loading.subadmins = true
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
				this.idState.loading.subadmins = false
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
			this.idState.loading.quota = true
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
				this.idState.loading.quota = false
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
			this.idState.loading.languages = true
			// ensure we only send the preset id
			try {
				await this.$store.dispatch('setUserData', {
					userid: this.user.id,
					key: 'language',
					value: lang.code,
				})
				this.idState.loading.languages = false
			} catch (error) {
				console.error(error)
			}
			return lang
		},

		/**
		 * Dispatch new welcome mail request
		 */
		sendWelcomeMail() {
			this.idState.loading.all = true
			this.$store.dispatch('sendWelcomeMail', this.user.id)
				.then(() => showSuccess(t('setting', 'Welcome mail sent!'), { timeout: 2000 }))
				.finally(() => {
					this.idState.loading.all = false
				})
		},

		async toggleEdit() {
			this.idState.editing = !this.idState.editing
			if (this.idState.editing) {
				await this.$nextTick()
				this.$refs.displayNameField?.$refs?.inputField?.$refs?.input?.focus()
			}
			if (this.idState.editedDisplayName !== this.user.displayname) {
				this.idState.editedDisplayName = this.user.displayname
			} else if (this.idState.editedMail !== this.user.email) {
				this.idState.editedMail = this.user.email ?? ''
			}
		},
	},
}
</script>

<style lang="scss" scoped>
@import './shared/styles.scss';

.row {
	@include cell;

	&__cell {
		:deep {
			.input-field,
			.input-field__main-wrapper,
			.input-field__input {
				height: 48px !important;
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
