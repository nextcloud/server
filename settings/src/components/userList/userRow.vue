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
	<!-- Obfuscated user: Logged in user does not have permissions to see all of the data -->
	<div class="row" v-if="Object.keys(user).length ===1" :data-id="user.id">
		<div class="avatar" :class="{'icon-loading-small': loading.delete || loading.disable}">
			<img alt="" width="32" height="32" :src="generateAvatar(user.id, 32)"
				 :srcset="generateAvatar(user.id, 64)+' 2x, '+generateAvatar(user.id, 128)+' 4x'"
				 v-if="!loading.delete && !loading.disable">
		</div>
		<div class="name">{{user.id}}</div>
		<div class="obfuscated">{{t('settings','You do not have permissions to see the details of this user')}}</div>
	</div>

	<!-- User full data -->
	<div class="row" v-else :class="{'disabled': loading.delete || loading.disable}" :data-id="user.id">
		<div class="avatar" :class="{'icon-loading-small': loading.delete || loading.disable}">
			<img alt="" width="32" height="32" :src="generateAvatar(user.id, 32)"
				 :srcset="generateAvatar(user.id, 64)+' 2x, '+generateAvatar(user.id, 128)+' 4x'"
				 v-if="!loading.delete && !loading.disable">
		</div>
		<!-- dirty hack to ellipsis on two lines -->
		<div class="name">{{user.id}}</div>
		<form class="displayName" :class="{'icon-loading-small': loading.displayName}" v-on:submit.prevent="updateDisplayName">
			<input :id="'displayName'+user.id+rand" type="text"
					:disabled="loading.displayName||loading.all"
					:value="user.displayname" ref="displayName"
					autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" />
			<input type="submit" class="icon-confirm" value="" />
		</form>
		<form class="password" v-if="settings.canChangePassword" :class="{'icon-loading-small': loading.password}"
			  v-on:submit.prevent="updatePassword">
			<input :id="'password'+user.id+rand" type="password" required
					:disabled="loading.password||loading.all" :minlength="minPasswordLength"
					value="" :placeholder="t('settings', 'New password')" ref="password"
					autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" />
			<input type="submit" class="icon-confirm" value="" />
		</form>
		<div v-else></div>
		<form class="mailAddress" :class="{'icon-loading-small': loading.mailAddress}" v-on:submit.prevent="updateEmail">
			<input :id="'mailAddress'+user.id+rand" type="email"
					:disabled="loading.mailAddress||loading.all"
					:value="user.email" ref="mailAddress"
					autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" />
			<input type="submit" class="icon-confirm" value="" />
		</form>
		<div class="groups" :class="{'icon-loading-small': loading.groups}">
			<multiselect :value="userGroups" :options="availableGroups" :disabled="loading.groups||loading.all"
						 tag-placeholder="create" :placeholder="t('settings', 'Add user in group')"
						 label="name" track-by="id" class="multiselect-vue" :limit="2"
						 :multiple="true" :taggable="settings.isAdmin" :closeOnSelect="false"
						 @tag="createGroup" @select="addUserGroup" @remove="removeUserGroup">
				<span slot="limit" class="multiselect__limit" v-tooltip.auto="formatGroupsTitle(userGroups)">+{{userGroups.length-2}}</span>
				<span slot="noResult">{{t('settings', 'No results')}}</span>
			</multiselect>
		</div>
		<div class="subadmins" v-if="subAdminsGroups.length>0 && settings.isAdmin" :class="{'icon-loading-small': loading.subadmins}">
			<multiselect :value="userSubAdminsGroups" :options="subAdminsGroups" :disabled="loading.subadmins||loading.all"
						 :placeholder="t('settings', 'Set user as admin for')"
						 label="name" track-by="id" class="multiselect-vue" :limit="2"
						 :multiple="true" :closeOnSelect="false"
						 @select="addUserSubAdmin" @remove="removeUserSubAdmin">
				<span slot="limit" class="multiselect__limit" v-tooltip.auto="formatGroupsTitle(userSubAdminsGroups)">+{{userSubAdminsGroups.length-2}}</span>
				<span slot="noResult">{{t('settings', 'No results')}}</span>
			</multiselect>
		</div>
		<div class="quota" :class="{'icon-loading-small': loading.quota}" v-tooltip.auto="usedSpace">
			<multiselect :value="userQuota" :options="quotaOptions" :disabled="loading.quota||loading.all"
						 tag-placeholder="create" :placeholder="t('settings', 'Select user quota')"
						 label="label" track-by="id" class="multiselect-vue"
						 :allowEmpty="false" :taggable="true"
						 @tag="validateQuota" @input="setUserQuota">
			</multiselect>
			<progress class="quota-user-progress" :class="{'warn':usedQuota>80}" :value="usedQuota" max="100"></progress>
		</div>
		<div class="languages" :class="{'icon-loading-small': loading.languages}"
			 v-if="showConfig.showLanguages">
			<multiselect :value="userLanguage" :options="languages" :disabled="loading.languages||loading.all"
						 :placeholder="t('settings', 'No language set')"
						 label="name" track-by="code" class="multiselect-vue"
						 :allowEmpty="false" group-values="languages" group-label="label"
						 @input="setUserLanguage">
			</multiselect>
		</div>
		<div class="storageLocation" v-if="showConfig.showStoragePath">{{user.storageLocation}}</div>
		<div class="userBackend" v-if="showConfig.showUserBackend">{{user.backend}}</div>
		<div class="lastLogin" v-if="showConfig.showLastLogin" v-tooltip.auto="user.lastLogin>0 ? OC.Util.formatDate(user.lastLogin) : ''">
			{{user.lastLogin>0 ? OC.Util.relativeModifiedDate(user.lastLogin) : t('settings','Never')}}
		</div>
		<div class="userActions">
			<div class="toggleUserActions" v-if="OC.currentUser !== user.id && user.id !== 'admin' && !loading.all">
				<div class="icon-more" v-click-outside="hideMenu" @click="toggleMenu"></div>
				<div class="popovermenu" :class="{ 'open': openedMenu }">
					<popover-menu :menu="userActions" />
				</div>
			</div>
			<div class="feedback" :style="{opacity: feedbackMessage !== '' ? 1 : 0}">
				<div class="icon-checkmark"></div>
				{{feedbackMessage}}
			</div>
		</div>
		</div>
</template>

<script>
import popoverMenu from '../popoverMenu';
import ClickOutside from 'vue-click-outside';
import Multiselect from 'vue-multiselect';
import Vue from 'vue'

export default {
	name: 'userRow',
	props: ['user', 'settings', 'groups', 'subAdminsGroups', 'quotaOptions', 'showConfig', 'languages', 'externalActions'],
	components: {
		popoverMenu,
		Multiselect
	},
	directives: {
		ClickOutside
	},
	mounted() {
		// required if popup needs to stay opened after menu click
		// since we only have disable/delete actions, let's close it directly
		// this.popupItem = this.$el;
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
				languages: false
			}
		}
	},
	computed: {
		/* USER POPOVERMENU ACTIONS */
		userActions() {
			let actions = [{
				icon: 'icon-delete',
				text: t('settings','Delete user'),
				action: this.deleteUser
			},{
				icon: this.user.enabled ? 'icon-close' : 'icon-add',
				text: this.user.enabled ? t('settings','Disable user') : t('settings','Enable user'),
				action: this.enableDisableUser
			}];
			if (this.user.email !== null && this.user.email !== '') {
				actions.push({
					icon: 'icon-mail',
					text: t('settings','Resend welcome email'),
					action: this.sendWelcomeMail
				})
			}
			return actions.concat(this.externalActions);
		},

		/* GROUPS MANAGEMENT */
		userGroups() {
			let userGroups = this.groups.filter(group => this.user.groups.includes(group.id));
			return userGroups;
		},
		userSubAdminsGroups() {
			let userSubAdminsGroups = this.subAdminsGroups.filter(group => this.user.subadmin.includes(group.id));
			return userSubAdminsGroups;
		},
		availableGroups() {
			return this.groups.map((group) => {
				// clone object because we don't want
				// to edit the original groups
				let groupClone = Object.assign({}, group);

				// two settings here:
				// 1. user NOT in group but no permission to add
				// 2. user is in group but no permission to remove
				groupClone.$isDisabled =
					(group.canAdd === false &&
						!this.user.groups.includes(group.id)) ||
					(group.canRemove === false &&
						this.user.groups.includes(group.id));
				return groupClone;
			});
		},

		/* QUOTA MANAGEMENT */
		usedSpace() {
			if (this.user.quota.used) {
				return t('settings', '{size} used', {size: OC.Util.humanFileSize(this.user.quota.used)});
			}
			return t('settings', '{size} used', {size: OC.Util.humanFileSize(0)});
		},
		usedQuota() {
			let quota = this.user.quota.quota;
			if (quota > 0) {
				quota = Math.min(100, Math.round(this.user.quota.used / quota * 100));
			} else {
				var usedInGB = this.user.quota.used / (10 * Math.pow(2, 30));
				//asymptotic curve approaching 50% at 10GB to visualize used stace with infinite quota
				quota = 95 * (1 - (1 / (usedInGB + 1)));
			}
			return isNaN(quota) ? 0 : quota;
		},
		// Mapping saved values to objects
		userQuota() {
			if (this.user.quota.quota >= 0) {
				// if value is valid, let's map the quotaOptions or return custom quota
				let humanQuota = OC.Util.humanFileSize(this.user.quota.quota);
				let userQuota = this.quotaOptions.find(quota => quota.id === humanQuota);
				return userQuota ? userQuota : {id:humanQuota, label:humanQuota};
			} else if (this.user.quota.quota === 'default') {
				// default quota is replaced by the proper value on load
				return this.quotaOptions[0];
			}
			return this.quotaOptions[1]; // unlimited
		},

		/* PASSWORD POLICY? */
		minPasswordLength() {
			return this.$store.getters.getPasswordPolicyMinLength;
		},

		/* LANGUAGE */
		userLanguage() {
			let availableLanguages = this.languages[0].languages.concat(this.languages[1].languages);
			let userLang = availableLanguages.find(lang => lang.code === this.user.language);
			if (typeof userLang !== 'object' && this.user.language !== '') {
				return {
					code: this.user.language,
					name: this.user.language
				}
			} else if(this.user.language === '') {
				return false;
			}
			return userLang;
		}
	},
	methods: {
		/* MENU HANDLING */
		toggleMenu() {
			this.openedMenu = !this.openedMenu;
		},
		hideMenu() {
			this.openedMenu = false;
		},

		/**
		 * Generate avatar url
		 * 
		 * @param {string} user The user name
		 * @param {int} size Size integer, default 32
		 * @returns {string}
		 */
		generateAvatar(user, size=32) {
			return OC.generateUrl(
				'/avatar/{user}/{size}?v={version}',
				{
					user: user,
					size: size,
					version: oc_userconfig.avatar.version
				}
			);
		},

		/**
		 * Format array of groups objects to a string for the popup
		 * 
		 * @param {array} groups The groups
		 * @returns {string}
		 */
		formatGroupsTitle(groups) {
			let names = groups.map(group => group.name);
			return names.slice(2,).join(', ');
		},

		deleteUser() {
			this.loading.delete = true;
			this.loading.all = true;
			let userid = this.user.id;
			return this.$store.dispatch('deleteUser', userid)
				.then(() => {
					this.loading.delete = false
					this.loading.all = false
				});
		},

		enableDisableUser() {
			this.loading.delete = true;
			this.loading.all = true;
			let userid = this.user.id;
			let enabled = !this.user.enabled;
			return this.$store.dispatch('enableDisableUser', {userid, enabled})
				.then(() => {
					this.loading.delete = false
					this.loading.all = false
				});
		},

		/**
		 * Set user displayName
		 * 
		 * @param {string} displayName The display name
		 * @returns {Promise}
		 */
		updateDisplayName() {
			let displayName = this.$refs.displayName.value;
			this.loading.displayName = true;
			this.$store.dispatch('setUserData', {
				userid: this.user.id, 
				key: 'displayname',
				value: displayName
			}).then(() => {
				this.loading.displayName = false;
				this.$refs.displayName.value = displayName;
			});
		},

		/**
		 * Set user password
		 * 
		 * @param {string} password The email adress
		 * @returns {Promise}
		 */
		updatePassword() {
			let password = this.$refs.password.value;
			this.loading.password = true;
			this.$store.dispatch('setUserData', {
				userid: this.user.id,
				key: 'password',
				value: password
			}).then(() => {
				this.loading.password = false;
				this.$refs.password.value = ''; // empty & show placeholder 
			});
		},

		/**
		 * Set user mailAddress
		 * 
		 * @param {string} mailAddress The email adress
		 * @returns {Promise}
		 */
		updateEmail() {
			let mailAddress = this.$refs.mailAddress.value;
			this.loading.mailAddress = true;
			this.$store.dispatch('setUserData', {
				userid: this.user.id,
				key: 'email',
				value: mailAddress
			}).then(() => {
				this.loading.mailAddress = false;
				this.$refs.mailAddress.value = mailAddress;
			});
		},

		/**
		 * Create a new group and add user to it
		 * 
		 * @param {string} groups Group id
		 * @returns {Promise}
		 */
		createGroup(gid) {
			this.loading = {groups:true, subadmins:true}
			this.$store.dispatch('addGroup', gid)
				.then(() => {
					this.loading = {groups:false, subadmins:false};
					let userid = this.user.id;
					this.$store.dispatch('addUserGroup', {userid, gid});
				})
				.catch(() => {
					this.loading = {groups:false, subadmins:false};
				});
			return this.$store.getters.getGroups[this.groups.length];
		},

		/**
		 * Add user to group
		 * 
		 * @param {object} group Group object
		 * @returns {Promise}
		 */
		addUserGroup(group) {
			if (group.canAdd === false) {
				return false;
			}
			this.loading.groups = true;
			let userid = this.user.id;
			let gid = group.id;
			return this.$store.dispatch('addUserGroup', {userid, gid})
				.then(() => this.loading.groups = false);
		},

		/**
		 * Remove user from group
		 * 
		 * @param {object} group Group object
		 * @returns {Promise}
		 */
		removeUserGroup(group) {
			if (group.canRemove === false) {
				return false;
			}
			this.loading.groups = true;
			let userid = this.user.id;
			let gid = group.id;
			return this.$store.dispatch('removeUserGroup', {userid, gid})
				.then(() => {
					this.loading.groups = false
					// remove user from current list if current list is the removed group
					if (this.$route.params.selectedGroup === gid) {
						this.$store.commit('deleteUser', userid);
					}
				})
				.catch(() => {
					this.loading.groups = false
				});
		},

		/**
		 * Add user to group
		 * 
		 * @param {object} group Group object
		 * @returns {Promise}
		 */
		addUserSubAdmin(group) {
			this.loading.subadmins = true;
			let userid = this.user.id;
			let gid = group.id;
			return this.$store.dispatch('addUserSubAdmin', {userid, gid})
				.then(() => this.loading.subadmins = false);
		},

		/**
		 * Remove user from group
		 * 
		 * @param {object} group Group object
		 * @returns {Promise}
		 */
		removeUserSubAdmin(group) {
			this.loading.subadmins = true;
			let userid = this.user.id;
			let gid = group.id;
			return this.$store.dispatch('removeUserSubAdmin', {userid, gid})
				.then(() => this.loading.subadmins = false);
		},

		/**
		 * Dispatch quota set request
		 * 
		 * @param {string|Object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
		 * @returns {string}
		 */
		setUserQuota(quota = 'none') {
			this.loading.quota = true;
			// ensure we only send the preset id
			quota = quota.id ? quota.id : quota;
			this.$store.dispatch('setUserData', {
				userid: this.user.id, 
				key: 'quota',
				value: quota
			}).then(() => this.loading.quota = false);
			return quota;
		},

		/**
		 * Validate quota string to make sure it's a valid human file size
		 * 
		 * @param {string} quota Quota in readable format '5 GB'
		 * @returns {Promise|boolean}
		 */
		validateQuota(quota) {
			// only used for new presets sent through @Tag
			let validQuota = OC.Util.computerFileSize(quota);
			if (validQuota !== null && validQuota >= 0) {
				// unify format output
				return this.setUserQuota(OC.Util.humanFileSize(OC.Util.computerFileSize(quota)));
			}
			// if no valid do not change
			return false;
		},

		/**
		 * Dispatch language set request
		 * 
		 * @param {Object} lang language object {code:'en', name:'English'}
		 * @returns {Object}
		 */
		setUserLanguage(lang) {
			this.loading.languages = true;
			// ensure we only send the preset id
			this.$store.dispatch('setUserData', {
				userid: this.user.id, 
				key: 'language',
				value: lang.code
			}).then(() => this.loading.languages = false);
			return lang;
		},

		/**
		 * Dispatch new welcome mail request
		 */
		sendWelcomeMail() {
			this.loading.all = true;
			this.$store.dispatch('sendWelcomeMail', this.user.id)
				.then(success => {
					if (success) {
						// Show feedback to indicate the success
						this.feedbackMessage = t('setting', 'Welcome mail sent!');
						setTimeout(() => {
							this.feedbackMessage = '';
						}, 2000);
					}
					this.loading.all = false;
				});
		}

	}
}
</script>
