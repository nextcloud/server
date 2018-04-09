<template>
	<div id="app">
		<app-navigation :menu="menu">
			<template slot="settings-content">
				<div>
					<input type="checkbox" id="showLanguages" class="checkbox"
						   :checked="showLanguages" v-model="showLanguages">
					<label for="showLanguages">{{t('settings', 'Show Languages')}}</label>
				</div>
				<div>
					<input type="checkbox" id="showLastLogin" class="checkbox"
						   :checked="showLastLogin" v-model="showLastLogin">
					<label for="showLastLogin">{{t('settings', 'Show last login')}}</label>
				</div>
				<div>
					<input type="checkbox" id="showUserBackend" class="checkbox"
						   :checked="showUserBackend" v-model="showUserBackend">
					<label for="showUserBackend">{{t('settings', 'Show user backend')}}</label>
				</div>
				<div>
					<input type="checkbox" id="showStoragePath" class="checkbox"
						   :checked="showStoragePath" v-model="showStoragePath">
					<label for="showStoragePath">{{t('settings', 'Show storage path')}}</label>
				</div>
			</template>
		</app-navigation>
		<user-list :users="users" :showConfig="showConfig" />
	</div>
</template>

<script>
import appNavigation from '../components/appNavigation';
import userList from '../components/userList';
import Vue from 'vue';
import VueLocalStorage from 'vue-localstorage'
Vue.use(VueLocalStorage)

export default {
	name: 'Users',
	components: {
		appNavigation,
		userList
	},
	beforeMount() {
		this.$store.commit('initGroups', {
			groups: this.$store.getters.getServerData.groups,
			orderBy: this.$store.getters.getServerData.sortGroups,
			userCount: this.$store.getters.getServerData.userCount
		});
		this.$store.dispatch('getPasswordPolicyMinLength');
	},
	data() {
		return {
			showConfig: {
				showStoragePath: false,
				showUserBackend: false,
				showLastLogin: false,
				showNewUserForm: false,
				showLanguages: false
			}
		}
	},
	methods: {
		getLocalstorage(key) {
			// force initialization
			this.showConfig[key] = this.$localStorage.get(key) === 'true';
			return this.showConfig[key];
		},
		setLocalStorage(key, status) {
			this.showConfig[key] = status;
			this.$localStorage.set(key, status);
			return status;
		}
	},
	computed: {
		route() {
			return this.$store.getters.getRoute;
		},
		users() {
			return this.$store.getters.getUsers;
		},
		loading() {
			return Object.keys(this.users).length === 0;
		},
		usersOffset() {
			return this.$store.getters.getUsersOffset;
		},
		usersLimit() {
			return this.$store.getters.getUsersLimit;
		},

		// Local settings
		showLanguages: {
			get: function() {return this.getLocalstorage('showLanguages')},
			set: function(status) {
				this.setLocalStorage('showLanguages', status);
			}
		},
		showLastLogin: {
			get: function() {return this.getLocalstorage('showLastLogin')},
			set: function(status) {
				this.setLocalStorage('showLastLogin', status);
			}
		},
		showUserBackend: {
			get: function() {return this.getLocalstorage('showUserBackend')},
			set: function(status) {
				this.setLocalStorage('showUserBackend', status);
			}
		},
		showStoragePath: {
			get: function() {return this.getLocalstorage('showStoragePath')},
			set: function(status) {
				this.setLocalStorage('showStoragePath', status);
			}
		},


		userCount() {
			return this.$store.getters.getUserCount;
		},
		menu() {
			let self = this;
			// Data provided php side
			let groups = this.$store.getters.getGroups;
			groups = Array.isArray(groups) ? groups : [];

			// Map groups
			groups = groups.map(group => {
				let item = {};
				item.id = group.id.replace(' ', '_');
				item.classes = [];
				item.href = '#group'+group.id.replace(' ', '_');
				item.text = group.name;
				item.utils = {counter: group.usercount};
				return item;
			});

			// Adjust data
			let adminGroup = groups.find(group => group.id == 'admin');
	   		let disabledGroup = groups.find(group => group.id == '_disabled');
			if (adminGroup.text) {
				adminGroup.text = t('settings', 'Admins');}			// rename admin group
			if (disabledGroup.text) {
				disabledGroup.text = t('settings', 'Disabled users');	// rename disabled group
				if (disabledGroup.utils.counter === 0) {
					groups.splice(groups.findIndex(group => group.id == '_disabled'), 1);							// remove disabled if empty
				}
			}

			// Add everyone group
			groups.unshift({
				id: '_everyone',
				classes: [],
				href:'#group_everyone',
				text: t('settings', 'Everyone'),
				utils: {counter: this.userCount}
			});

			// Set current group as active
			let activeGroup = groups.findIndex(group => group.href === this.$route.hash);
			if (activeGroup >= 0) {
				groups[activeGroup].classes.push('active');
			} else {
				groups[0].classes.push('active');
			}

			// Return
			return {
				id: 'usergrouplist',
				new: {
					id:'new-user-button',
					text: t('settings','New user'),
					icon: 'icon-add',
					action: function(){self.showConfig.showNewUserForm=!self.showConfig.showNewUserForm}
				},
				items: groups
			}
		}
	}
}
</script>
