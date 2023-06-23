<!--
  - @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
  - @copyright Copyright (c) 2019 Gary Kim <gary@garykim.dev>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  - @author Gary Kim <gary@garykim.dev>
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
	<!-- Obfuscated user: Logged in user does not have permissions to see all of the data -->
	<div v-if="Object.keys(user).length ===1" :data-id="user.id" class="row">
		<div :class="{'icon-loading-small': loading.delete || loading.disable || loading.wipe}"
			class="avatar">
			<img v-if="!loading.delete && !loading.disable && !loading.wipe"
				:src="generateAvatar(user.id, isDarkTheme)"
				alt=""
				height="32"
				width="32">
		</div>
		<div class="name">
			{{ user.id }}
		</div>
		<div class="obfuscated">
			{{ t('settings','You do not have permissions to see the details of this user') }}
		</div>
	</div>

	<!-- User full data -->
	<UserRowSimple v-else-if="!editing"
		:editing.sync="editing"
		:groups="groups"
		:languages="languages"
		:loading="loading"
		:opened-menu.sync="openedMenu"
		:settings="settings"
		:show-config="showConfig"
		:sub-admins-groups="subAdminsGroups"
		:user-actions="userActions"
		:user="user"
		:is-dark-theme="isDarkTheme"
		:class="{'row--menu-opened': openedMenu}" />
	<div v-else
		:class="{
			'disabled': loading.delete || loading.disable,
			'row--menu-opened': openedMenu
		}"
		:data-id="user.id"
		class="row row--editable">
		<div :class="{'icon-loading-small': loading.delete || loading.disable || loading.wipe}"
			class="avatar">
			<img v-if="!loading.delete && !loading.disable && !loading.wipe"
				:src="generateAvatar(user.id, isDarkTheme)"
				alt=""
				height="32"
				width="32">
		</div>
		<!-- dirty hack to ellipsis on two lines -->
		<div v-if="user.backendCapabilities.setDisplayName" class="displayName">
			<label class="hidden-visually" :for="'displayName'+user.id+rand">{{ t('settings', 'Edit display name') }}</label>
			<NcTextField :id="'displayName'+user.id+rand"
				:show-trailing-button="true"
				class="user-row-text-field"
				:class="{'icon-loading-small': loading.displayName}"
				:disabled="loading.displayName||loading.all"
				trailing-button-icon="arrowRight"
				:value.sync="editedDisplayName"
				autocapitalize="off"
				autocomplete="off"
				autocorrect="off"
				spellcheck="false"
				type="text"
				@trailing-button-click="updateDisplayName" />
		</div>
		<div v-else class="name">
			{{ user.id }}
			<div class="displayName subtitle">
				<div :title="user.displayname.length > 20 ? user.displayname : ''" class="cellText">
					{{ user.displayname }}
				</div>
			</div>
		</div>
		<div v-if="settings.canChangePassword && user.backendCapabilities.setPassword" class="password">
			<label class="hidden-visually" :for="'password'+user.id+rand">{{ t('settings', 'Add new password') }}</label>
			<NcTextField :id="'password'+user.id+rand"
				:show-trailing-button="true"
				class="user-row-text-field"
				:class="{'icon-loading-small': loading.password}"
				:disabled="loading.password || loading.all"
				:minlength="minPasswordLength"
				maxlength="469"
				:placeholder="t('settings', 'Add new password')"
				trailing-button-icon="arrowRight"
				:value.sync="editedPassword"
				autocapitalize="off"
				autocomplete="new-password"
				autocorrect="off"
				required
				spellcheck="false"
				type="password"
				@trailing-button-click="updatePassword" />
		</div>

		<div v-else />

		<div class="mailAddress">
			<label class="hidden-visually" :for="'mailAddress'+user.id+rand">{{ t('settings', 'Add new email address') }}</label>
			<NcTextField :id="'mailAddress'+user.id+rand"
				:show-trailing-button="true"
				class="user-row-text-field"
				:class="{'icon-loading-small': loading.mailAddress}"
				:disabled="loading.mailAddress||loading.all"
				:placeholder="t('settings', 'Add new email address')"
				trailing-button-icon="arrowRight"
				:value.sync="editedMail"
				autocapitalize="off"
				autocomplete="new-password"
				autocorrect="off"
				spellcheck="false"
				type="email"
				@trailing-button-click="updateEmail" />
		</div>
		<div :class="{'icon-loading-small': loading.groups}" class="groups">
			<label class="hidden-visually" :for="'groups'+user.id+rand">{{ t('settings', 'Add user to group') }}</label>
			<NcSelect :input-id="'groups'+user.id+rand"
				:close-on-select="false"
				:disabled="loading.groups||loading.all"
				:multiple="true"
				:options="availableGroups"
				:placeholder="t('settings', 'Add user to group')"
				:taggable="settings.isAdmin"
				:value="userGroups"
				class="select-vue"
				label="name"
				:no-wrap="true"
				:selectable="() => userGroups.length < 2"
				:create-option="(value) => ({ name: value, isCreating: true })"
				@option:created="createGroup"
				@option:selected="options => addUserGroup(options.at(-1))"
				@option:deselected="removeUserGroup" />
		</div>
		<div v-if="subAdminsGroups.length>0 && settings.isAdmin"
			:class="{'icon-loading-small': loading.subadmins}"
			class="subadmins">
			<label class="hidden-visually" :for="'subadmins'+user.id+rand">{{ t('settings', 'Set user as admin for') }}</label>
			<NcSelect :id="'subadmins'+user.id+rand"
				:close-on-select="false"
				:disabled="loading.subadmins||loading.all"
				label="name"
				:multiple="true"
				:no-wrap="true"
				:selectable="() => userSubAdminsGroups.length < 2"
				:options="subAdminsGroups"
				:placeholder="t('settings', 'Set user as admin for')"
				:value="userSubAdminsGroups"
				class="select-vue"
				@option:deselected="removeUserSubAdmin"
				@option:selected="options => addUserSubAdmin(options.at(-1))" />
		</div>
		<div :title="usedSpace"
			:class="{'icon-loading-small': loading.quota}"
			class="quota">
			<label class="hidden-visually" :for="'quota'+user.id+rand">{{ t('settings', 'Select user quota') }}</label>
			<NcSelect v-model="userQuota"
				:close-on-select="true"
				:create-option="validateQuota"
				:disabled="loading.quota||loading.all"
				:input-id="'quota'+user.id+rand"
				class="select-vue"
				:options="quotaOptions"
				:placeholder="t('settings', 'Select user quota')"
				:taggable="true"
				@option:selected="setUserQuota" />
		</div>
		<div v-if="showConfig.showLanguages"
			:class="{'icon-loading-small': loading.languages}"
			class="languages">
			<label class="hidden-visually" :for="'language'+user.id+rand">{{ t('settings', 'Set the language') }}</label>
			<NcSelect :id="'language'+user.id+rand"
				:allow-empty="false"
				:disabled="loading.languages||loading.all"
				:options="availableLanguages"
				:placeholder="t('settings', 'No language set')"
				:value="userLanguage"
				label="name"
				class="select-vue"
				@input="setUserLanguage" />
		</div>

		<div v-if="showConfig.showStoragePath || showConfig.showUserBackend"
			class="storageLocation" />
		<div v-if="showConfig.showLastLogin" />

		<div :class="{'icon-loading-small': loading.manager}" class="managers">
			<label class="hidden-visually" :for="'manager'+user.id+rand">{{ t('settings', 'Set the language') }}</label>
			<NcSelect v-model="currentManager"
				:input-id="'manager'+user.id+rand"
				:close-on-select="true"
				label="displayname"
				:options="possibleManagers"
				:placeholder="t('settings', 'Select manager')"
				class="select-vue"
				@search="searchUserManager"
				@option:selected="updateUserManager"
				@input="updateUserManager" />
		</div>

		<div class="userActions">
<<<<<<< HEAD
			<UserRowActions v-if="!loading.all"
				:actions="userActions"
				:edit="true"
				@update:edit="toggleEdit" />
=======
			<div v-if="!loading.all"
				class="toggleUserActions">
				<NcActions :inline="2">
					<NcActionButton icon="icon-checkmark"
						:title="t('settings', 'Done')"
						:aria-label="t('settings', 'Done')"
						@click="editing = false" />
					<NcActionButton icon="icon-close"
						:title="t('settings', 'Cancel')"
						:aria-label="t('settings', 'Cancel')"
						@click="editing = false" />
				</NcActions>
				<div v-click-outside="hideMenu" class="userPopoverMenuWrapper">
					<button class="icon-more"
						:aria-expanded="openedMenu"
						:aria-label="t('settings', 'Toggle user actions menu')"
						@click.prevent="toggleMenu" />
					<div :class="{ 'open': openedMenu }" class="popovermenu">
						<NcPopoverMenu :menu="userActions" />
					</div>
				</div>
			</div>
			<div :style="{opacity: feedbackMessage !== '' ? 1 : 0}"
				class="feedback">
				<div class="icon-checkmark" />
				{{ feedbackMessage }}
			</div>
>>>>>>> 7a2938b01f3 (Add cancel button for when user entry is being edited)
		</div>
	</div>
</template>

<script>
import { showSuccess, showError } from '@nextcloud/dialogs'

import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import ClickOutside from 'vue-click-outside'

import UserRowActions from './UserRowActions.vue'
import UserRowSimple from './UserRowSimple.vue'
import UserRowMixin from '../../mixins/UserRowMixin.js'

export default {
	name: 'UserRow',

	components: {
		NcSelect,
		NcTextField,
		UserRowActions,
		UserRowSimple,
	},

	directives: {
		ClickOutside,
	},

	mixins: [UserRowMixin],

	props: {
		users: {
			type: Array,
			required: true,
		},
		user: {
			type: Object,
			required: true,
		},
		settings: {
			type: Object,
			default: () => ({}),
		},
		groups: {
			type: Array,
			default: () => [],
		},
		subAdminsGroups: {
			type: Array,
			default: () => [],
		},
		quotaOptions: {
			type: Array,
			default: () => [],
		},
		showConfig: {
			type: Object,
			default: () => ({}),
		},
		languages: {
			type: Array,
			required: true,
		},
		externalActions: {
			type: Array,
			default: () => [],
		},
		isDarkTheme: {
			type: Boolean,
			required: true,
		},
	},
	data() {
		return {
			// default quota is set to unlimited
			unlimitedQuota: { id: 'none', label: t('settings', 'Unlimited') },
			// temporary value used for multiselect change
			selectedQuota: false,
			rand: parseInt(Math.random() * 1000),
			openedMenu: false,
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
		/* USER POPOVERMENU ACTIONS */
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
		userQuota: {
			get() {
				if (this.selectedQuota !== false) {
					return this.selectedQuota
				}
				if (this.settings.defaultQuota !== this.unlimitedQuota.id && OC.Util.computerFileSize(this.settings.defaultQuota) >= 0) {
					// if value is valid, let's map the quotaOptions or return custom quota
					return { id: this.settings.defaultQuota, label: this.settings.defaultQuota }
				}
				return this.unlimitedQuota // unlimited
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
		await this.searchUserManager()

		if (this.user.manager) {
			await this.initManager(this.user.manager)
		}
	},

	methods: {
		/* MENU HANDLING */
		toggleMenu() {
			this.openedMenu = !this.openedMenu
		},
		hideMenu() {
			this.openedMenu = false
		},

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
		async searchUserManager(query) {
			await this.$store.dispatch('searchUsers', { offset: 0, limit: 10, search: query }).then(response => {
				const users = response?.data ? this.filterManagers(Object.values(response?.data.ocs.data.users)) : []
				if (users.length > 0) {
					this.possibleManagers = users
				}
			})
		},

		updateUserManager(manager) {
			if (manager === null) {
				this.currentManager = ''
			}
			this.loading.manager = true
			try {
				this.$store.dispatch('setUserData', {
					userid: this.user.id,
					key: 'manager',
					value: this.currentManager ? this.currentManager.id : '',
				})
			} catch (error) {
				showError(t('setting', 'Update of user manager was failed'))
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
				quota = this.unlimitedQuota
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
				return this.unlimitedQuota
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

		toggleEdit() {
			this.editing = false
			if (this.editedDisplayName !== this.user.displayname) {
				this.editedDisplayName = this.user.displayname
			} else if (this.editedMail !== this.user.email) {
				this.editedMail = this.user.email
			}
		},
	},
}
</script>
<style scoped lang="scss">
	// Force menu to be above other rows
	.row--menu-opened {
		z-index: 1 !important;
	}

	.row :deep() {
		.v-select.select {
			// reset min width to 100% instead of X px
			min-width: 100%;
		}

		.mailAddress,
		.password,
		.displayName {
			.input-field,
			.input-field__input {
				height: 48px!important;
			}
			.button-vue--icon-only {
				height: 44px!important;
			}
		}
  }

</style>
