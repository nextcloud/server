<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<tr class="user-list__row"
		:data-cy-user-row="user.id">
		<td class="row__cell row__cell--avatar" data-cy-user-list-cell-avatar>
			<NcLoadingIcon v-if="isLoadingUser"
				:name="t('settings', 'Loading account …')"
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
			<strong v-else-if="!isObfuscated"
				:title="user.displayname?.length > 20 ? user.displayname : null">
				{{ user.displayname }}
			</strong>
		</td>

		<td class="row__cell row__cell--username" data-cy-user-list-cell-username>
			<span class="row__subtitle">{{ user.id }}</span>
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
				{{ t('settings', 'You do not have permissions to see the details of this account') }}
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
					{{ t('settings', 'Add account to group') }}
				</label>
				<NcSelect data-cy-user-list-input-groups
					:data-loading="loading.groups || undefined"
					:input-id="'groups' + uniqueId"
					:close-on-select="false"
					:disabled="isLoadingField || loading.groupsDetails"
					:loading="loading.groups"
					:multiple="true"
					:append-to-body="false"
					:options="availableGroups"
					:placeholder="t('settings', 'Add account to group')"
					:taggable="settings.isAdmin || settings.isDelegatedAdmin"
					:value="userGroups"
					label="name"
					:no-wrap="true"
					:create-option="(value) => ({ id: value, name: value, isCreating: true })"
					@search="searchGroups"
					@option:created="createGroup"
					@option:selected="options => addUserGroup(options.at(-1))"
					@option:deselected="removeUserGroup" />
			</template>
			<span v-else-if="!isObfuscated"
				:title="userGroupsLabels?.length > 40 ? userGroupsLabels : null">
				{{ userGroupsLabels }}
			</span>
		</td>

		<td v-if="settings.isAdmin || settings.isDelegatedAdmin"
			data-cy-user-list-cell-subadmins
			class="row__cell row__cell--large row__cell--multiline">
			<template v-if="editing && (settings.isAdmin || settings.isDelegatedAdmin)">
				<label class="hidden-visually"
					:for="'subadmins' + uniqueId">
					{{ t('settings', 'Set account as admin for') }}
				</label>
				<NcSelect data-cy-user-list-input-subadmins
					:data-loading="loading.subadmins || undefined"
					:input-id="'subadmins' + uniqueId"
					:close-on-select="false"
					:disabled="isLoadingField || loading.subAdminGroupsDetails"
					:loading="loading.subadmins"
					label="name"
					:append-to-body="false"
					:multiple="true"
					:no-wrap="true"
					:options="availableSubAdminGroups"
					:placeholder="t('settings', 'Set account as admin for')"
					:value="userSubAdminGroups"
					@search="searchGroups"
					@option:deselected="removeUserSubAdmin"
					@option:selected="options => addUserSubAdmin(options.at(-1))" />
			</template>
			<span v-else-if="!isObfuscated"
				:title="userSubAdminGroupsLabels?.length > 40 ? userSubAdminGroupsLabels : null">
				{{ userSubAdminGroupsLabels }}
			</span>
		</td>

		<td class="row__cell" data-cy-user-list-cell-quota>
			<template v-if="editing">
				<label class="hidden-visually"
					:for="'quota' + uniqueId">
					{{ t('settings', 'Select account quota') }}
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
					:placeholder="t('settings', 'Select account quota')"
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

		<td v-if="showConfig.showFirstLogin"
			class="row__cell"
			data-cy-user-list-cell-first-login>
			<span v-if="!isObfuscated">{{ userFirstLogin }}</span>
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
					:disabled="isLoadingField"
					:loading="loadingPossibleManagers || loading.manager"
					:options="possibleManagers"
					:placeholder="managerLabel"
					label="displayname"
					:filterable="false"
					:internal-search="false"
					:clearable="true"
					@open="searchInitialUserManager"
					@search="searchUserManager"
					@update:model-value="updateUserManager" />
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
import { formatFileSize, parseFileSize } from '@nextcloud/files'
import { getCurrentUser } from '@nextcloud/auth'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'

import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcProgressBar from '@nextcloud/vue/components/NcProgressBar'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcTextField from '@nextcloud/vue/components/NcTextField'

import UserRowActions from './UserRowActions.vue'

import UserRowMixin from '../../mixins/UserRowMixin.js'
import { isObfuscated, unlimitedQuota } from '../../utils/userUtils.ts'
import { searchGroups, loadUserGroups, loadUserSubAdminGroups } from '../../service/groups.ts'
import logger from '../../logger.ts'

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
				groupsDetails: false,
				subAdminGroupsDetails: false,
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
			// Cancelable promise for search groups request
			promise: null,
		}
	},

	computed: {
		managerLabel() {
			// TRANSLATORS This string describes a person's manager in the context of an organization
			return t('settings', 'Set line manager')
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

		availableGroups() {
			const groups = (this.settings.isAdmin || this.settings.isDelegatedAdmin)
				? this.$store.getters.getSortedGroups
				: this.$store.getters.getSubAdminGroups

			return groups.filter(group => group.id !== '__nc_internal_recent' && group.id !== 'disabled')
		},

		availableSubAdminGroups() {
			return this.availableGroups.filter(group => group.id !== 'admin')
		},

		userGroupsLabels() {
			return this.userGroups
				.map(group => {
					// Try to match with more extensive group data
					const availableGroup = this.availableGroups.find(g => g.id === group.id)
					return availableGroup?.name ?? group.name ?? group.id
				})
				.join(', ')
		},

		userSubAdminGroupsLabels() {
			return this.userSubAdminGroups
				.map(group => {
					// Try to match with more extensive group data
					const availableGroup = this.availableSubAdminGroups.find(g => g.id === group.id)
					return availableGroup?.name ?? group.name ?? group.id
				})
				.join(', ')
		},

		usedSpace() {
			if (this.user.quota?.used) {
				return t('settings', '{size} used', { size: formatFileSize(this.user.quota?.used) })
			}
			return t('settings', '{size} used', { size: formatFileSize(0) })
		},

		canEdit() {
			return getCurrentUser().uid !== this.user.id || this.settings.isAdmin || this.settings.isDelegatedAdmin
		},

		userQuota() {
			let quota = this.user.quota?.quota

			if (quota === 'default') {
				quota = this.settings.defaultQuota
				if (quota !== 'none') {
					// convert to numeric value to match what the server would usually return
					quota = parseFileSize(quota, true)
				}
			}

			// when the default quota is unlimited, the server returns -3 here, map it to "none"
			if (quota === 'none' || quota === -3) {
				return t('settings', 'Unlimited')
			} else if (quota >= 0) {
				return formatFileSize(quota)
			}
			return formatFileSize(0)
		},

		userActions() {
			const actions = [
				{
					icon: 'icon-delete',
					text: t('settings', 'Delete account'),
					action: this.deleteUser,
				},
				{
					icon: 'icon-delete',
					text: t('settings', 'Disconnect all devices and delete local data'),
					action: this.wipeUserDevices,
				},
				{
					icon: this.user.enabled ? 'icon-close' : 'icon-add',
					text: this.user.enabled ? t('settings', 'Disable account') : t('settings', 'Enable account'),
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
				if (this.settings.defaultQuota !== unlimitedQuota.id && parseFileSize(this.settings.defaultQuota, true) >= 0) {
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
		async wipeUserDevices() {
			const userid = this.user.id
			await confirmPassword()
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

		async loadGroupsDetails() {
			this.loading.groups = true
			this.loading.groupsDetails = true
			try {
				const groups = await loadUserGroups({ userId: this.user.id })
				// Populate store from server request
				for (const group of groups) {
					this.$store.commit('addGroup', group)
				}
				this.selectedGroups = this.selectedGroups.map(selectedGroup => groups.find(group => group.id === selectedGroup.id) ?? selectedGroup)
			} catch (error) {
				logger.error(t('settings', 'Failed to load groups with details'), { error })
			}
			this.loading.groups = false
			this.loading.groupsDetails = false
		},

		async loadSubAdminGroupsDetails() {
			this.loading.subadmins = true
			this.loading.subAdminGroupsDetails = true
			try {
				const groups = await loadUserSubAdminGroups({ userId: this.user.id })
				// Populate store from server request
				for (const group of groups) {
					this.$store.commit('addGroup', group)
				}
				this.selectedSubAdminGroups = this.selectedSubAdminGroups.map(selectedGroup => groups.find(group => group.id === selectedGroup.id) ?? selectedGroup)
			} catch (error) {
				logger.error(t('settings', 'Failed to load sub admin groups with details'), { error })
			}
			this.loading.subadmins = false
			this.loading.subAdminGroupsDetails = false
		},

		async searchGroups(query, toggleLoading) {
			if (query === '') {
				return // Prevent unexpected search behaviour e.g. on option:created
			}
			if (this.promise) {
				this.promise.cancel()
			}
			toggleLoading(true)
			try {
				this.promise = await searchGroups({
					search: query,
					offset: 0,
					limit: 25,
				})
				const groups = await this.promise
				// Populate store from server request
				for (const group of groups) {
					this.$store.commit('addGroup', group)
				}
			} catch (error) {
				logger.error(t('settings', 'Failed to search groups'), { error })
			}
			this.promise = null
			toggleLoading(false)
		},

		async searchUserManager(query) {
			await this.$store.dispatch('searchUsers', { offset: 0, limit: 10, search: query }).then(response => {
				const users = response?.data ? this.filterManagers(Object.values(response?.data.ocs.data.users)) : []
				if (users.length > 0) {
					this.possibleManagers = users
				}
			})
		},

		async updateUserManager() {
			this.loading.manager = true

			// Store the current manager before making changes
			const previousManager = this.user.manager

			try {
				await this.$store.dispatch('setUserData', {
					userid: this.user.id,
					key: 'manager',
					value: this.currentManager ? this.currentManager.id : '',
				})
			} catch (error) {
				// TRANSLATORS This string describes a line manager in the context of an organization
				showError(t('settings', 'Failed to update line manager'))
				logger.error('Failed to update manager:', { error })

				// Revert to the previous manager in the UI on error
				this.currentManager = previousManager
			} finally {
				this.loading.manager = false
			}
		},

		async deleteUser() {
			const userid = this.user.id
			await confirmPassword()
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
		 */
		async updateDisplayName() {
			this.loading.displayName = true
			try {
				await this.$store.dispatch('setUserData', {
					userid: this.user.id,
					key: 'displayname',
					value: this.editedDisplayName,
				})

				if (this.editedDisplayName === this.user.displayname) {
					showSuccess(t('settings', 'Display name was successfully changed'))
				}
			} finally {
				this.loading.displayName = false
			}
		},

		/**
		 * Set user password
		 */
		async updatePassword() {
			this.loading.password = true
			if (this.editedPassword.length === 0) {
				showError(t('settings', "Password can't be empty"))
				this.loading.password = false
			} else {
				try {
					await this.$store.dispatch('setUserData', {
						userid: this.user.id,
						key: 'password',
						value: this.editedPassword,
					})
					this.editedPassword = ''
					showSuccess(t('settings', 'Password was successfully changed'))
				} finally {
					this.loading.password = false
				}
			}
		},

		/**
		 * Set user mailAddress
		 */
		async updateEmail() {
			this.loading.mailAddress = true
			if (this.editedMail === '') {
				showError(t('settings', "Email can't be empty"))
				this.loading.mailAddress = false
				this.editedMail = this.user.email
			} else {
				try {
					await this.$store.dispatch('setUserData', {
						userid: this.user.id,
						key: 'email',
						value: this.editedMail,
					})

					if (this.editedMail === this.user.email) {
						showSuccess(t('settings', 'Email was successfully changed'))
					}
				} finally {
					this.loading.mailAddress = false
				}
			}
		},

		/**
		 * Create a new group and add user to it
		 *
		 * @param {string} gid Group id
		 */
		async createGroup({ name: gid }) {
			this.loading.groups = true
			try {
				await this.$store.dispatch('addGroup', gid)
				const userid = this.user.id
				await this.$store.dispatch('addUserGroup', { userid, gid })
				this.userGroups.push({ id: gid, name: gid })
			} catch (error) {
				logger.error(t('settings', 'Failed to create group'), { error })
			}
			this.loading.groups = false
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
			const userid = this.user.id
			const gid = group.id
			if (group.canAdd === false) {
				return
			}
			this.loading.groups = true
			try {
				await this.$store.dispatch('addUserGroup', { userid, gid })
				this.userGroups.push(group)
			} catch (error) {
				console.error(error)
			}
			this.loading.groups = false
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
				this.userGroups = this.userGroups.filter(group => group.id !== gid)
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
				this.userSubAdminGroups.push(group)
			} catch (error) {
				console.error(error)
			}
			this.loading.subadmins = false
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
				this.userSubAdminGroups = this.userSubAdminGroups.filter(group => group.id !== gid)
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
				// If human readable format, convert to raw float format
				// Else just send the raw string
				const value = (parseFileSize(quota, true) || quota).toString()
				await this.$store.dispatch('setUserData', {
					userid: this.user.id,
					key: 'quota',
					value,
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
			const validQuota = parseFileSize(quota, true)
			if (validQuota === null) {
				return unlimitedQuota
			} else {
				// unify format output
				quota = formatFileSize(parseFileSize(quota, true))
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
				.then(() => showSuccess(t('settings', 'Welcome mail sent!'), { timeout: 2000 }))
				.finally(() => {
					this.loading.all = false
				})
		},

		async toggleEdit() {
			this.editing = !this.editing
			if (this.editing) {
				await this.$nextTick()
				this.$refs.displayNameField?.$refs?.inputField?.$refs?.input?.focus()
				this.loadGroupsDetails()
				this.loadSubAdminGroupsDetails()
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
@use './shared/styles';

.user-list__row {
	@include styles.row;

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
	@include styles.cell;

	&__cell {
		border-bottom: 1px solid var(--color-border);

		:deep {
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
