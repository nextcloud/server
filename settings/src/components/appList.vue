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
	<div id="app-content" :class="{ 'with-app-sidebar': app }">
		<div id="apps-list" class="installed">
			<div class="apps-header" v-if="category === 'app-bundles'">
				<div class="app-image"></div>
				<h2>Firmen-Paket <input class="enable" type="submit" data-bundleid="EnterpriseBundle" data-active="true" value="Alle aktivieren"></h2>
				<div class="app-version"></div>
				<div class="app-level"></div>
				<div class="app-groups"></div>
				<div class="actions">&nbsp;</div>
			</div>

			<app-item v-for="app in apps" :key="app.id" :app="app" :category="category" />
		</div>
	</div>
</template>

<script>
import appItem from './appList/appItem';
import Multiselect from 'vue-multiselect';

export default {
	name: 'appList',
	props: ['category', 'app'],
	components: {
		Multiselect,
		appItem
	},
	data() {
		return {
			groupCheckedAppsData: [],
			loading: false,
			scrolled: false,
		};
	},
	watch: {

	},
	mounted() {
		//this.$store.dispatch('getApps', { category: this.category });
		this.$store.dispatch('getGroups');

	},
	computed: {
		apps() {
			return this.$store.getters.getApps;
		},
		groups() {
			console.log(this.$store.getters.getGroups);
			return this.$store.getters.getGroups;
				/*.filter(group => group.id !== 'disabled')
				.sort((a, b) => a.name.localeCompare(b.name));*/
		},
	},
	methods: {
		prefix(prefix, content) {
			return prefix + '_' + content;
		},
		isLimitedToGroups(app) {
			if (app.groups.length || this.groupCheckedAppsData.includes(app.id)) {
				return true;
			}
			return false;
		},
		canLimitToGroups(app) {
			if (app.types && app.types.includes('filesystem')
				|| app.types.includes('prelogin')
				|| app.types.includes('authentication')
				|| app.types.includes('logging')
				|| app.types.includes('prevent_group_restriction')) {
				return false;
			}
			return true;
		},
		addGroupLimitation(appId, group) {
			let currentGroups = this.$store.apps.find(app => app.id === appId).groups;
			currentGroups.push(group);
			this.$store.dispatch('enableApp', { appId: appId, groups: groups});
		},
		removeGroupLimitation(appId, group) {
			let currentGroups = this.$store.apps.find(app => app.id === appId).groups;
			currentGroups.push(group);
			let index = currentGroups.indexOf(group);
			if (index > -1) {
				currentGroups.splice(index, 1);
			}
			this.$store.dispatch('enableApp', { appId: appId, groups: groups});
		},
		enable(appId) {
			this.$store.dispatch('enableApp', { appId: appId })
				.catch((error) => { OC.Notification.show(error)});
		},
		disable(appId) {
			this.$store.dispatch('disableApp', { appId: appId })
				.catch((error) => { OC.Notification.show(error)});
		},
		remove() {},
		install() {},
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
