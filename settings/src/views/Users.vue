<template>
	<div id="app">
		<app-navigation :menu="menu">
			<template slot="settings-content">
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
			orderBy: this.$store.getters.getServerData.sortGroups
		});
		this.$store.dispatch('getPasswordPolicyMinLength');
	},
	data() {
		return {
			showConfig: {
				showStoragePath: false,
				showUserBackend: false,
				showLastLogin: false,
				showNewUserForm: false
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
			if (groups[0].id === 'admin') {
				groups[0].text = t('settings', 'Admins');}			// rename admin group
			if (groups[1].id === '_disabled') {
				groups[1].text = t('settings', 'Disabled users');	// rename disabled group
				if (groups[1].utils.counter === 0) {
					groups.splice(1, 1);							// remove disabled if empty
				}
			}

			// Add everyone group
			groups.unshift({
				id: '_everyone',
				classes: ['active'],
				href:'#group_everyone',
				text: t('settings', 'Everyone'),
				utils: {counter: this.users.length}
			});

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

<style lang="scss">
</style>
