<!--
  - @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
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
	<div id="app-sidebar">
		<h2>{{ app.name }}</h2>
	</div>
</template>

<script>
import userRow from './userList/userRow';
import Multiselect from 'vue-multiselect';
import InfiniteLoading from 'vue-infinite-loading';
import Vue from 'vue';

export default {
	name: 'userList',
	props: ['users', 'showConfig', 'selectedGroup'],
	components: {
		userRow,
		Multiselect,
		InfiniteLoading
	},
	data() {
		let unlimitedQuota = {id:'none', label:t('settings', 'Unlimited')},
			defaultQuota = {id:'default', label:t('settings', 'Default quota')};
		return {
			unlimitedQuota: unlimitedQuota,
			defaultQuota: defaultQuota,
			loading: false,
			scrolled: false,
			newUser: {
				id:'',
				displayName:'',
				password:'',
				mailAddress:'',
				groups: [],
				subAdminsGroups: [],
				quota: defaultQuota,
				language: {code: 'en', name: t('settings', 'Default language')}
			}
		};
	},
	mounted() {
		if (!this.settings.canChangePassword) {
			OC.Notification.showTemporary(t('settings', 'Password change is disabled because the master key is disabled'));
		}
		/** 
		 * Init default language from server data. The use of this.settings
		 * requires a computed variable,vwhich break the v-model binding of the form,
		 * this is a much easier solution than getter and setter
		 */
		Vue.set(this.newUser.language, 'code', this.settings.defaultLanguage);
	},
	computed: {
		settings() {
			return this.$store.getters.getServerData;
		},
		filteredUsers() {
			if (this.selectedGroup === 'disabled') {
				let disabledUsers = this.users.filter(user => user.enabled !== true);
				if (disabledUsers.length===0 && this.$refs.infiniteLoading && this.$refs.infiniteLoading.isComplete) {
					// disabled group is empty, redirection to all users
					this.$router.push('users');
					this.$refs.infiniteLoading.$emit('$InfiniteLoading:reset');
				}
				return disabledUsers;
			}
			return this.users.filter(user => user.enabled === true);
		},
		groups() {
			// data provided php side + remove the disabled group
			return this.$store.getters.getGroups.filter(group => group.id !== 'disabled');
		},
		subAdminsGroups() {
			// data provided php side
			return this.$store.getters.getServerData.subadmingroups;
		},
		quotaOptions() {
			// convert the preset array into objects
			let quotaPreset = this.settings.quotaPreset.reduce((acc, cur) => acc.concat({id:cur, label:cur}), []);
			// add default presets
			quotaPreset.unshift(this.unlimitedQuota);
			quotaPreset.unshift(this.defaultQuota);
			return quotaPreset;
		},
		minPasswordLength() {
			return this.$store.getters.getPasswordPolicyMinLength;
		},
		usersOffset() {
			return this.$store.getters.getUsersOffset;
		},
		usersLimit() {
			return this.$store.getters.getUsersLimit;
		},

		/* LANGUAGES */
		languages() {
			return Array(
				{
					label: t('settings', 'Common languages'),
					languages: this.settings.languages.commonlanguages
				},
				{
					label: t('settings', 'All languages'),
					languages: this.settings.languages.languages
				}
			);
		}
	},
	watch: {
		// watch url change and group select
		selectedGroup: function (val, old) {
			this.$store.commit('resetUsers');
			this.$refs.infiniteLoading.$emit('$InfiniteLoading:reset');
		}
	},
	methods: {
		onScroll(event) {
			this.scrolled = event.target.scrollTop>0;
		},

		/**
		 * Validate quota string to make sure it's a valid human file size
		 * 
		 * @param {string} quota Quota in readable format '5 GB'
		 * @returns {Object}
		 */
		validateQuota(quota) {
			// only used for new presets sent through @Tag
			let validQuota = OC.Util.computerFileSize(quota);
			if (validQuota !== null && validQuota > 0) {
				// unify format output
				quota = OC.Util.humanFileSize(OC.Util.computerFileSize(quota));
				return this.newUser.quota = {id: quota, label: quota};
			}
			// Default is unlimited
			return this.newUser.quota = this.quotaOptions[0];
		},

		infiniteHandler($state) {
			this.$store.dispatch('getUsers', {
				offset: this.usersOffset,
				limit: this.usersLimit,
				group: this.selectedGroup !== 'disabled' ? this.selectedGroup : ''})
				.then((response) => {response?$state.loaded():$state.complete()});
		},

		resetForm() {
			// revert form to original state
			Object.assign(this.newUser, this.$options.data.call(this).newUser);
			this.loading = false;
		},
		createUser() {
			this.loading = true;
			this.$store.dispatch('addUser', {
				userid: this.newUser.id,
				password: this.newUser.password,
				email: this.newUser.mailAddress,
				groups: this.newUser.groups.map(group => group.id),
				subadmin: this.newUser.subAdminsGroups.map(group => group.id),
				quota: this.newUser.quota.id,
				language: this.newUser.language.code,
			}).then(() => this.resetForm());
		}
	}
}
</script>
