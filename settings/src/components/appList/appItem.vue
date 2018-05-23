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
	<div class="section">
		<div class="app-image app-image-icon" v-on:click="showAppDetails">
			<img :src="app.preview" v-if="!app.previewAsIcon" width="100%" />
			<svg v-else width="32" height="32" viewBox="0 0 32 32" v-if="app.previewAsIcon">
				<defs><filter id="invertIconApps-606"><feColorMatrix in="SourceGraphic" type="matrix" values="-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0 0 1 0"></feColorMatrix></filter></defs>
				<image x="0" y="0" width="32" height="32" preserveAspectRatio="xMinYMin meet" filter="url(#invertIconApps-606)" :xlink:href="app.preview" class="app-icon"></image>
			</svg>
		</div>
		<div class="app-name" v-on:click="showAppDetails">
			{{ app.name }}
		</div>
		<div class="app-version">{{ app.version }}</div>
		<div class="app-score" v-if="!listView"><app-score :score="app.score"></app-score> </div>
		<div class="app-summary" v-if="!listView">{{ app.summary }}</div>
		<div class="app-level">
			<span class="official icon-checkmark" v-if="app.level === 200">{{ t('settings', 'Official') }}</span>
			<a :href="appstoreUrl" v-if="!app.internal && listView">Im Store anzeigen ↗</a>
		</div>

		<div class="app-groups" v-if="listView">
			<div class="groups-enable" v-if="app.active && canLimitToGroups(app)">
				<input type="checkbox" :value="app.id" v-model="groupCheckedAppsData" v-on:change="setGroupLimit" class="groups-enable__checkbox checkbox" :id="prefix('groups_enable', app.id)">
				<label :for="prefix('groups_enable', app.id)">Auf Gruppen beschränken</label>
				<input type="hidden" class="group_select" title="Alle" value="">
				<multiselect v-if="isLimitedToGroups(app)" :options="groups" :value="appGroups" @select="addGroupLimitation" @remove="removeGroupLimitation"
							 :placeholder="t('settings', 'Limit app usage to groups')"
							 label="name" track-by="id" class="multiselect-vue"
							 :multiple="true" :close-on-select="false">
					<span slot="noResult">{{t('settings', 'No results')}}</span>
				</multiselect>
			</div>
		</div>

		<div class="actions">
			<div class="warning hidden"></div>
			<input v-if="app.update" class="update" type="button" :value="t('settings', 'Update to %s', app.update)" v-on:click="update(app.id)" />
			<input v-if="app.canUnInstall" class="uninstall" type="button" :value="t('settings', 'Remove')" v-on:click="remove(app.id)" />
			<input v-if="app.active" class="enable" type="button" :value="t('settings','Disable')" v-on:click="disable(app.id)" />
			<input v-if="!app.active" class="enable" type="button" :value="enableButtonText" v-on:click="enable(app.id)" :disabled="!app.canInstall" />
		</div>
	</div>
</template>

<script>
	import Multiselect from 'vue-multiselect';
	import AppScore from './appScore';

	export default {
		name: 'appItem',
		props: {
			app: {},
			category: {},
			listView: {
				type: Boolean,
				default: true,
			}
		},
		components: {
			Multiselect,
			AppScore,
		},
		data() {
			return {
				groupCheckedAppsData: false,
				loading: false,
				scrolled: false,
			};
		},
		mounted() {
			if (this.app.groups.length > 0) {
				this.groupCheckedAppsData = true;
			}
		},
		computed: {
			appstoreUrl() {
				return `https://apps.nextcloud.com/apps/${this.app.id}`;
			},
			appGroups() {
				return this.app.groups.map(group => {return {id: group, name: group}});
			},
			groups() {
				return this.$store.getters.getGroups
					.filter(group => group.id !== 'disabled')
					.sort((a, b) => a.name.localeCompare(b.name));
			},
			enableButtonText() {
				if (this.app.needsDownload) {
					return t('settings','Download and enable');
				}
				return t('settings','Enable');
			}
		},
		watchers: {

		},
		methods: {
			showAppDetails() {
				console.log(this.app.id);
				this.$router.push({
					name: 'apps-details',
					params: {category: this.category, id: this.app.id}
				});
			},
			prefix(prefix, content) {
				return prefix + '_' + content;
			},
			isLimitedToGroups(app) {
				if (this.app.groups.length || this.groupCheckedAppsData) {
					return true;
				}
				return false;
			},
			setGroupLimit: function() {
				if (!this.groupCheckedAppsData) {
					this.$store.dispatch('enableApp', {appId: this.app.id, groups: []});
				}
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
			addGroupLimitation(group) {
				let groups = this.app.groups.concat([]).concat([group.id]);
				this.$store.dispatch('enableApp', { appId: this.app.id, groups: groups});
			},
			removeGroupLimitation(group) {
				let currentGroups = this.app.groups.concat([]);
				let index = currentGroups.indexOf(group.id);
				if (index > -1) {
					currentGroups.splice(index, 1);
				}
				this.$store.dispatch('enableApp', { appId: this.app.id, groups: currentGroups});
			},
			enable(appId) {
				this.$store.dispatch('enableApp', { appId: appId, groups: [] })
					.catch((error) => { OC.Notification.show(error)});
			},
			disable(appId) {
				this.$store.dispatch('disableApp', { appId: appId })
					.catch((error) => { OC.Notification.show(error)});
			},
			remove(appId) {
				this.$store.dispatch('uninstallApp', { appId: appId })
					.catch((error) => { OC.Notification.show(error)});
			},
			install(appId) {
				this.$store.dispatch('installApp', { appId: appId })
					.catch((error) => { OC.Notification.show(error)});
			},
			update(appId) {
				this.$store.dispatch('updateApp', { appId: appId })
					.catch((error) => { OC.Notification.show(error)});
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
