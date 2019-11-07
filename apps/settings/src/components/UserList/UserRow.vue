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
	<div v-if="Object.keys(user).length ===1" class="row" :data-id="user.id">
		<div class="avatar" :class="{'icon-loading-small': loading.delete || loading.disable || loading.wipe}">
			<img v-if="!loading.delete && !loading.disable && !loading.wipe"
				alt=""
				width="32"
				height="32"
				:src="generateAvatar(user.id, 32)"
				:srcset="generateAvatar(user.id, 64)+' 2x, '+generateAvatar(user.id, 128)+' 4x'">
		</div>
		<div class="name">
			{{ user.id }}
		</div>
		<div class="obfuscated">
			{{ t('settings','You do not have permissions to see the details of this user') }}
		</div>
	</div>

	<!-- User full data -->
	<div v-else
		class="row"
		:class="{'disabled': loading.delete || loading.disable}"
		:data-id="user.id">
		<div class="avatar" :class="{'icon-loading-small': loading.delete || loading.disable || loading.wipe}">
			<img v-if="!loading.delete && !loading.disable && !loading.wipe"
				alt=""
				width="32"
				height="32"
				:src="generateAvatar(user.id, 32)"
				:srcset="generateAvatar(user.id, 64)+' 2x, '+generateAvatar(user.id, 128)+' 4x'">
		</div>
		<!-- dirty hack to ellipsis on two lines -->
		<div class="name">
			{{ user.id }}
		</div>
		<form class="displayName" :class="{'icon-loading-small': loading.displayName}" @submit.prevent="updateDisplayName">
			<template v-if="user.backendCapabilities.setDisplayName">
				<input v-if="user.backendCapabilities.setDisplayName"
					:id="'displayName'+user.id+rand"
					ref="displayName"
					type="text"
					:disabled="loading.displayName||loading.all"
					:value="user.displayname"
					autocomplete="new-password"
					autocorrect="off"
					autocapitalize="off"
					spellcheck="false">
				<input v-if="user.backendCapabilities.setDisplayName"
					type="submit"
					class="icon-confirm"
					value="">
			</template>
			<div v-else v-tooltip.auto="t('settings', 'The backend does not support changing the display name')" class="name">
				{{ user.displayname }}
			</div>
		</form>
		<form v-if="settings.canChangePassword && user.backendCapabilities.setPassword"
			class="password"
			:class="{'icon-loading-small': loading.password}"
			@submit.prevent="updatePassword">
			<input :id="'password'+user.id+rand"
				ref="password"
				type="password"
				required
				:disabled="loading.password||loading.all"
				:minlength="minPasswordLength"
				value=""
				:placeholder="t('settings', 'New password')"
				autocomplete="new-password"
				autocorrect="off"
				autocapitalize="off"
				spellcheck="false">
			<input type="submit" class="icon-confirm" value="">
		</form>
		<div v-else />
		<form class="mailAddress" :class="{'icon-loading-small': loading.mailAddress}" @submit.prevent="updateEmail">
			<input :id="'mailAddress'+user.id+rand"
				ref="mailAddress"
				type="email"
				:disabled="loading.mailAddress||loading.all"
				:value="user.email"
				autocomplete="new-password"
				autocorrect="off"
				autocapitalize="off"
				spellcheck="false">
			<input type="submit" class="icon-confirm" value="">
		</form>
		<div class="groups" :class="{'icon-loading-small': loading.groups}">
			<Multiselect :value="userGroups"
				:options="availableGroups"
				:disabled="loading.groups||loading.all"
				tag-placeholder="create"
				:placeholder="t('settings', 'Add user in group')"
				label="name"
				track-by="id"
				class="multiselect-vue"
				:limit="2"
				:multiple="true"
				:taggable="settings.isAdmin"
				:close-on-select="false"
				:tag-width="60"
				@tag="createGroup"
				@select="addUserGroup"
				@remove="removeUserGroup">
				<span slot="limit" v-tooltip.auto="formatGroupsTitle(userGroups)" class="multiselect__limit">+{{ userGroups.length-2 }}</span>
				<span slot="noResult">{{ t('settings', 'No results') }}</span>
			</Multiselect>
		</div>
		<div v-if="subAdminsGroups.length>0 && settings.isAdmin" class="subadmins" :class="{'icon-loading-small': loading.subadmins}">
			<Multiselect :value="userSubAdminsGroups"
				:options="subAdminsGroups"
				:disabled="loading.subadmins||loading.all"
				:placeholder="t('settings', 'Set user as admin for')"
				label="name"
				track-by="id"
				class="multiselect-vue"
				:limit="2"
				:multiple="true"
				:close-on-select="false"
				:tag-width="60"
				@select="addUserSubAdmin"
				@remove="removeUserSubAdmin">
				<span slot="limit" v-tooltip.auto="formatGroupsTitle(userSubAdminsGroups)" class="multiselect__limit">+{{ userSubAdminsGroups.length-2 }}</span>
				<span slot="noResult">{{ t('settings', 'No results') }}</span>
			</Multiselect>
		</div>
		<div v-tooltip.auto="usedSpace" class="quota" :class="{'icon-loading-small': loading.quota}">
			<Multiselect :value="userQuota"
				:options="quotaOptions"
				:disabled="loading.quota||loading.all"
				tag-placeholder="create"
				:placeholder="t('settings', 'Select user quota')"
				label="label"
				track-by="id"
				class="multiselect-vue"
				:allow-empty="false"
				:taggable="true"
				@tag="validateQuota"
				@input="setUserQuota" />
			<progress class="quota-user-progress"
				:class="{'warn':usedQuota>80}"
				:value="usedQuota"
				max="100" />
		</div>
		<div v-if="showConfig.showLanguages"
			class="languages"
			:class="{'icon-loading-small': loading.languages}">
			<Multiselect :value="userLanguage"
				:options="languages"
				:disabled="loading.languages||loading.all"
				:placeholder="t('settings', 'No language set')"
				label="name"
				track-by="code"
				class="multiselect-vue"
				:allow-empty="false"
				group-values="languages"
				group-label="label"
				@input="setUserLanguage" />
		</div>
		<div v-if="showConfig.showStoragePath" class="storageLocation">
			{{ user.storageLocation }}
		</div>
		<div v-if="showConfig.showUserBackend" class="userBackend">
			{{ user.backend }}
		</div>
		<div v-if="showConfig.showLastLogin" v-tooltip.auto="user.lastLogin>0 ? OC.Util.formatDate(user.lastLogin) : ''" class="lastLogin">
			{{ user.lastLogin>0 ? OC.Util.relativeModifiedDate(user.lastLogin) : t('settings','Never') }}
		</div>
		<div class="userActions">
			<div v-if="OC.currentUser !== user.id && user.id !== 'admin' && !loading.all" class="toggleUserActions">
				<div v-click-outside="hideMenu" class="icon-more" @click="toggleMenu" />
				<div class="popovermenu" :class="{ 'open': openedMenu }">
					<PopoverMenu :menu="userActions" />
				</div>
			</div>
			<div class="feedback" :style="{opacity: feedbackMessage !== '' ? 1 : 0}">
				<div class="icon-checkmark" />
				{{ feedbackMessage }}
			</div>
		</div>
	</div>
</template>

<script>
import ClickOutside from 'vue-click-outside'
import Vue from 'vue'
import VTooltip from 'v-tooltip'
import { PopoverMenu, Multiselect } from 'nextcloud-vue'

Vue.use(VTooltip)

export default {
	name: 'UserRow',
	components: {
		PopoverMenu,
		Multiselect
	},
	directives: {
		ClickOutside
	},
	props: {
		user: {
			type: Object,
			required: true
		},
		settings: {
			type: Object,
			default: () => ({})
		},
		groups: {
			type: Array,
			default: () => []
		},
		subAdminsGroups: {
			type: Array,
			default: () => []
		},
		quotaOptions: {
			type: Array,
			default: () => []
		},
		showConfig: {
			type: Object,
			default: () => ({})
		},
		languages: {
			type: Array,
			required: true
		},
		externalActions: {
			type: Array,
			default: () => []
		}
	},
	data() {
		return {
			rand: parseInt(Math.random() * 1000),
			openedMenu: false,
			feedbackMessage: '',
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
				wipe: false
			}
		}
	},
	computed: {
		/* USER POPOVERMENU ACTIONS */
		userActions() {
			let actions = [
				{
					icon: 'icon-delete',
					text: t('settings', 'Delete user'),
					action: this.deleteUser
				},
				{
					icon: 'icon-delete',
					text: t('settings', 'Wipe all devices'),
					action: this.wipeUserDevices
				},
				{
					icon: this.user.enabled ? 'icon-close' : 'icon-add',
					text: this.user.enabled ? t('settings', 'Disable user') : t('settings', 'Enable user'),
					action: this.enableDisableUser
				}
			]
			if (this.user.email !== null && this.user.email !== '') {
				actions.push({
					icon: 'icon-mail',
					text: t('settings', 'Resend welcome email'),
					action: this.sendWelcomeMail
				})
			}
			return actions.concat(this.externalActions)
		},

		/* GROUPS MANAGEMENT */
		userGroups() {
			let userGroups = this.groups.filter(group => this.user.groups.includes(group.id))
			return userGroups
		},
		userSubAdminsGroups() {
			let userSubAdminsGroups = this.subAdminsGroups.filter(group => this.user.subadmin.includes(group.id))
			return userSubAdminsGroups
		},
		availableGroups() {
			return this.groups.map((group) => {
				// clone object because we don't want
				// to edit the original groups
				let groupClone = Object.assign({}, group)

				// two settings here:
				// 1. user NOT in group but no permission to add
				// 2. user is in group but no permission to remove
				groupClone.$isDisabled
					= (group.canAdd === false
						&& !this.user.groups.includes(group.id))
					|| (group.canRemove === false
						&& this.user.groups.includes(group.id))
				return groupClone
			})
		},

		/* QUOTA MANAGEMENT */
		usedSpace() {
			if (this.user.quota.used) {
				return t('settings', '{size} used', { size: OC.Util.humanFileSize(this.user.quota.used) })
			}
			return t('settings', '{size} used', { size: OC.Util.humanFileSize(0) })
		},
		usedQuota() {
			let quota = this.user.quota.quota
			if (quota > 0) {
				quota = Math.min(100, Math.round(this.user.quota.used / quota * 100))
			} else {
				var usedInGB = this.user.quota.used / (10 * Math.pow(2, 30))
				// asymptotic curve approaching 50% at 10GB to visualize used stace with infinite quota
				quota = 95 * (1 - (1 / (usedInGB + 1)))
			}
			return isNaN(quota) ? 0 : quota
		},
		// Mapping saved values to objects
		userQuota() {
			if (this.user.quota.quota >= 0) {
				// if value is valid, let's map the quotaOptions or return custom quota
				let humanQuota = OC.Util.humanFileSize(this.user.quota.quota)
				let userQuota = this.quotaOptions.find(quota => quota.id === humanQuota)
				return userQuota || { id: humanQuota, label: humanQuota }
			} else if (this.user.quota.quota === 'default') {
				// default quota is replaced by the proper value on load
				return this.quotaOptions[0]
			}
			return this.quotaOptions[1] // unlimited
		},

		/* PASSWORD POLICY? */
		minPasswordLength() {
			return this.$store.getters.getPasswordPolicyMinLength
		},

		/* LANGUAGE */
		userLanguage() {
			let availableLanguages = this.languages[0].languages.concat(this.languages[1].languages)
			let userLang = availableLanguages.find(lang => lang.code === this.user.language)
			if (typeof userLang !== 'object' && this.user.language !== '') {
				return {
					code: this.user.language,
					name: this.user.language
				}
			} else if (this.user.language === '') {
				return false
			}
			return userLang
		}
	},
	mounted() {
		// required if popup needs to stay opened after menu click
		// since we only have disable/delete actions, let's close it directly
		// this.popupItem = this.$el;
	},
	methods: {
		/* MENU HANDLING */
		toggleMenu() {
			this.openedMenu = !this.openedMenu
		},
		hideMenu() {
			this.openedMenu = false
		},

		/**
		 * Generate avatar url
		 *
		 * @param {string} user The user name
		 * @param {int} size Size integer, default 32
		 * @returns {string}
		 */
		generateAvatar(user, size = 32) {
			return OC.generateUrl(
				'/avatar/{user}/{size}?v={version}',
				{
					user: user,
					size: size,
					version: oc_userconfig.avatar.version
				}
			)
		},

		/**
		 * Format array of groups objects to a string for the popup
		 *
		 * @param {array} groups The groups
		 * @returns {string}
		 */
		formatGroupsTitle(groups) {
			let names = groups.map(group => group.name)
			return names.slice(2).join(', ')
		},

		wipeUserDevices() {
			let userid = this.user.id
			OC.dialogs.confirmDestructive(
				t('settings', 'In case of lost device or exiting the organization, this can remotely wipe the Nextcloud data from all devices associated with {userid}. Only works if the devices are connected to the internet.', { userid: userid }),
				t('settings', 'Remote wipe of devices'),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: t('settings', 'Wipe {userid}\'s devices', { userid: userid }),
					confirmClasses: 'error',
					cancel: t('settings', 'Cancel')
				},
				(result) => {
					if (result) {
						this.loading.wipe = true
						this.loading.all = true
						this.$store.dispatch('wipeUserDevices', userid)
							.then(() => {
								this.loading.wipe = false
								this.loading.all = false
							})
					}
				},
				true
			)
		},

		deleteUser() {
			let userid = this.user.id
			OC.dialogs.confirmDestructive(
				t('settings', 'Fully delete {userid}\'s account including all their personal files, app data, etc.', { userid: userid }),
				t('settings', 'Account deletion'),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: t('settings', 'Delete {userid}\'s account', { userid: userid }),
					confirmClasses: 'error',
					cancel: t('settings', 'Cancel')
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
				true
			)
		},

		enableDisableUser() {
			this.loading.delete = true
			this.loading.all = true
			let userid = this.user.id
			let enabled = !this.user.enabled
			return this.$store.dispatch('enableDisableUser', { userid, enabled })
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
			let displayName = this.$refs.displayName.value
			this.loading.displayName = true
			this.$store.dispatch('setUserData', {
				userid: this.user.id,
				key: 'displayname',
				value: displayName
			}).then(() => {
				this.loading.displayName = false
				this.$refs.displayName.value = displayName
			})
		},

		/**
		 * Set user password
		 *
		 * @param {string} password The email adress
		 */
		updatePassword() {
			let password = this.$refs.password.value
			this.loading.password = true
			this.$store.dispatch('setUserData', {
				userid: this.user.id,
				key: 'password',
				value: password
			}).then(() => {
				this.loading.password = false
				this.$refs.password.value = '' // empty & show placeholder
			})
		},

		/**
		 * Set user mailAddress
		 *
		 * @param {string} mailAddress The email adress
		 */
		updateEmail() {
			let mailAddress = this.$refs.mailAddress.value
			this.loading.mailAddress = true
			this.$store.dispatch('setUserData', {
				userid: this.user.id,
				key: 'email',
				value: mailAddress
			}).then(() => {
				this.loading.mailAddress = false
				this.$refs.mailAddress.value = mailAddress
			})
		},

		/**
		 * Create a new group and add user to it
		 *
		 * @param {string} gid Group id
		 */
		async createGroup(gid) {
			this.loading = { groups: true, subadmins: true }
			try {
				await this.$store.dispatch('addGroup', gid)
				let userid = this.user.id
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
			if (group.canAdd === false) {
				return false
			}
			this.loading.groups = true
			let userid = this.user.id
			let gid = group.id
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
			let userid = this.user.id
			let gid = group.id

			try {
				await this.$store.dispatch('removeUserGroup', { userid, gid })
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
			let userid = this.user.id
			let gid = group.id

			try {
				await this.$store.dispatch('addUserSubAdmin', { userid, gid })
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
			let userid = this.user.id
			let gid = group.id

			try {
				await this.$store.dispatch('removeUserSubAdmin', { userid, gid })
			} catch (error) {
				console.error(error)
			} finally {
				this.loading.subadmins = false
			}
		},

		/**
		 * Dispatch quota set request
		 *
		 * @param {string|Object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
		 * @returns {string}
		 */
		async setUserQuota(quota = 'none') {
			this.loading.quota = true
			// ensure we only send the preset id
			quota = quota.id ? quota.id : quota

			try {
				await this.$store.dispatch('setUserData', {
					userid: this.user.id,
					key: 'quota',
					value: quota
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
		 * @param {string} quota Quota in readable format '5 GB'
		 * @returns {Promise|boolean}
		 */
		validateQuota(quota) {
			// only used for new presets sent through @Tag
			let validQuota = OC.Util.computerFileSize(quota)
			if (validQuota !== null && validQuota >= 0) {
				// unify format output
				return this.setUserQuota(OC.Util.humanFileSize(OC.Util.computerFileSize(quota)))
			}
			// if no valid do not change
			return false
		},

		/**
		 * Dispatch language set request
		 *
		 * @param {Object} lang language object {code:'en', name:'English'}
		 * @returns {Object}
		 */
		async setUserLanguage(lang) {
			this.loading.languages = true
			// ensure we only send the preset id
			try {
				await this.$store.dispatch('setUserData', {
					userid: this.user.id,
					key: 'language',
					value: lang.code
				})
			} catch (error) {
				console.error(error)
			} finally {
				this.loading.languages = false
			}
			return lang
		},

		/**
		 * Dispatch new welcome mail request
		 */
		sendWelcomeMail() {
			this.loading.all = true
			this.$store.dispatch('sendWelcomeMail', this.user.id)
				.then(success => {
					if (success) {
						// Show feedback to indicate the success
						this.feedbackMessage = t('setting', 'Welcome mail sent!')
						setTimeout(() => {
							this.feedbackMessage = ''
						}, 2000)
					}
					this.loading.all = false
				})
		}

	}
}
</script>
