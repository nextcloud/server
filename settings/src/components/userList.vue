<template>
	<div id="app-content" class="user-list-grid" v-on:scroll.passive="onScroll">
		<div class="row" id="grid-header" :class="{'sticky': scrolled && !showConfig.showNewUserForm}">
			<div id="headerAvatar" class="avatar"></div>
			<div id="headerName" class="name">{{ t('settings', 'Username') }}</div>
			<div id="headerDisplayName" class="displayName">{{ t('settings',  'Full name') }}</div>
			<div id="headerPassword" class="password">{{ t('settings',  'Password') }}</div>
			<div id="headerAddress" class="mailAddress">{{ t('settings',  'Email') }}</div>
			<div id="headerGroups" class="groups">{{ t('settings',  'Groups') }}</div>
			<div id="headerSubAdmins" class="subadmins"
				 v-if="subAdminsGroups.length>0">{{ t('settings', 'Group admin for') }}</div>
			<div id="headerQuota" class="quota">{{ t('settings', 'Quota') }}</div>
			<div id="headerLanguages" class="languages"
				 v-if="showConfig.showLanguages">{{ t('settings', 'Languages') }}</div>
			<div class="headerStorageLocation storageLocation"
				 v-if="showConfig.showStoragePath">{{ t('settings', 'Storage location') }}</div>
			<div class="headerUserBackend userBackend"
				 v-if="showConfig.showUserBackend">{{ t('settings', 'User backend') }}</div>
			<div class="headerLastLogin lastLogin" 
				 v-if="showConfig.showLastLogin">{{ t('settings', 'Last login') }}</div>
			<div class="userActions"></div>
		</div>

		<form class="row" id="new-user" v-show="showConfig.showNewUserForm"
			  v-on:submit.prevent="createUser" :disabled="loading"
			  :class="{'sticky': scrolled && showConfig.showNewUserForm}">
			<div :class="loading?'icon-loading-small':'icon-add'"></div>
			<div class="name">
				<input id="newusername" type="text" required v-model="newUser.id"
					   :placeholder="t('settings', 'User name')" name="username"
					   autocomplete="off" autocapitalize="none" autocorrect="off"
					   pattern="[a-zA-Z0-9 _\.@\-']+">
			</div>
			<div class="displayName">
				<input id="newdisplayname" type="text" v-model="newUser.displayName"
					   :placeholder="t('settings', 'Display name')" name="displayname"
					   autocomplete="off" autocapitalize="none" autocorrect="off">
			</div>
			<div class="password">
				<input id="newuserpassword" type="password" v-model="newUser.password"
					   :required="newUser.mailAddress===''"
					   :placeholder="t('settings', 'Password')" name="password"
					   autocomplete="new-password" autocapitalize="none" autocorrect="off"
					   :minlength="minPasswordLength">
			</div>
			<div class="mailAddress">
				<input id="newemail" type="email" v-model="newUser.mailAddress"
					   :required="newUser.password===''"
					   :placeholder="t('settings', 'Mail address')" name="email"
					   autocomplete="off" autocapitalize="none" autocorrect="off">
			</div>
			<div class="groups">
				<multiselect :options="groups" v-model="newUser.groups"
							 :placeholder="t('settings', 'Add user in group')"
							 label="name" track-by="id" class="multiselect-vue"
							 :multiple="true" :close-on-select="false">
					<span slot="noResult">{{t('settings','No result')}}</span>
				</multiselect>
			</div>
			<div class="subadmins" v-if="subAdminsGroups.length>0">
				<multiselect :options="subAdminsGroups" v-model="newUser.subAdminsGroups"
							 :placeholder="t('settings', 'Set user as admin for')"
							 label="name" track-by="id" class="multiselect-vue"
							 :multiple="true" :close-on-select="false">
					<span slot="noResult">{{t('settings','No result')}}</span>
			</multiselect>
			</div>
			<div class="quota">
				<multiselect :options="quotaOptions" v-model="newUser.quota"
							 :placeholder="t('settings', 'Select user quota')"
							 label="label" track-by="id" class="multiselect-vue"
							 :allowEmpty="false" :taggable="true"
						 	 @tag="validateQuota" >
				</multiselect>
			</div>
			<div class="languages" v-if="showConfig.showLanguages"></div>
			<div class="storageLocation" v-if="showConfig.showStoragePath"></div>
			<div class="userBackend" v-if="showConfig.showUserBackend"></div>
			<div class="lastLogin" v-if="showConfig.showLastLogin"></div>
			<div class="userActions">
				<input type="submit" id="newsubmit" class="button primary icon-checkmark-white has-tooltip"
					   value="" :title="t('settings', 'Add a new user')">
				<input type="reset" id="newreset" class="button icon-close has-tooltip" @click="resetForm"
					   value="" :title="t('settings', 'Cancel and reset the form')">
			</div>
		</form>

		<user-row v-for="(user, key) in filteredUsers" :user="user" :key="key" :settings="settings" :showConfig="showConfig"
				  :groups="groups" :subAdminsGroups="subAdminsGroups" :quotaOptions="quotaOptions" />
		<infinite-loading @infinite="infiniteHandler" ref="infiniteLoading">
			<div slot="spinner"><div class="users-icon-loading icon-loading"></div></div>
			<div slot="no-more"><div class="users-list-end">— {{t('settings', 'no more results')}} —</div></div>
			<div slot="no-results">
				<div id="emptycontent">
					<div class="icon-contacts-dark"></div>
					<h2>{{t('settings', 'No users in here')}}</h2>
				</div>
			</div>
		</infinite-loading>
	</div>
</template>

<script>
import userRow from './userList/userRow';
import Multiselect from 'vue-multiselect';
import InfiniteLoading from 'vue-infinite-loading';

export default {
	name: 'userList',
	props: ['users', 'showConfig'],
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
				quota: defaultQuota
			}
		};
	},
	mounted() {
		if (!this.settings.canChangePassword) {
			OC.Notification.showTemporary(t('settings','Password change is disabled because the master key is disabled'));
		}
	},
	computed: {
		settings() {
			return this.$store.getters.getServerData;
		},
		filteredUsers() {
			if (this.$route.hash === '#group_disabled') {
				let disabledUsers = this.users.filter(user => user.enabled !== true);
				if (disabledUsers.length===0 && this.$refs.infiniteLoading && this.$refs.infiniteLoading.isComplete) {
					// disabled group is empty, redirection to all users
					window.location.hash = '#group_everyone';
					this.$refs.infiniteLoading.$emit('$InfiniteLoading:reset');
				}
				return disabledUsers;
			}
			return this.users.filter(user => user.enabled === true);
		},
		groups() {
			// data provided php side + remove the disabled group
			return this.$store.getters.getGroups.filter(group => group.id !== '_disabled');
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
		// get selected hash
		selectedGroup() {
			let hash = this.$route.hash;
			if (typeof hash === 'string' && hash.length > 0) {
				// we have a valid hash: groupXXXX
				// group_XXXX are reserved groups
				let split = hash.split('group');
				if (split.length === 2 && split[1].charAt(0) !== '_') {
					return hash.split('group')[1];
				}
			}
			return '';
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
			this.$store.dispatch('getUsers', {offset:this.usersOffset, limit:this.usersLimit, group:this.selectedGroup})
				.then((response) => {response?$state.loaded():$state.complete()});
		},

		resetForm () {
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
				quota: this.newUser.quota.id
			}).then(() => this.resetForm());
		}
	}
}
</script>
