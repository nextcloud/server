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
	<div class="section" v-bind:class="{ selected: isSelected }">
		<div class="app-image app-image-icon" v-on:click="showAppDetails">
			<div v-if="!app.preview" class="icon-settings-dark"></div>
			<img v-if="!app.previewAsIcon && app.preview" :src="app.preview"  width="100%" />
			<svg v-if="app.previewAsIcon && app.preview" width="32" height="32" viewBox="0 0 32 32">
				<defs><filter :id="filterId"><feColorMatrix in="SourceGraphic" type="matrix" values="-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0 0 1 0"></feColorMatrix></filter></defs>
				<image x="0" y="0" width="32" height="32" preserveAspectRatio="xMinYMin meet" :filter="filterUrl" :xlink:href="app.preview" class="app-icon"></image>
			</svg>
		</div>
		<div class="app-name" v-on:click="showAppDetails">
			{{ app.name }}
		</div>
		<div class="app-summary" v-if="!listView">{{ app.summary }}</div>
		<div class="app-version" v-if="listView">{{ app.version }}</div>

		<div class="app-level">
			<span class="official icon-checkmark" v-if="app.level === 200">{{ t('settings', 'Official') }}</span>
			<app-score v-if="!listView" :score="app.score"></app-score>
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
	import AppManagement from '../appManagement';

	export default {
		name: 'appItem',
		mixins: [AppManagement],
		props: {
			app: {},
			category: {},
			listView: {
				type: Boolean,
				default: true,
			}
		},
		watch: {
			'$route.params.id': function (id) {
				this.isSelected = (this.app.id === id);
			}
		},
		components: {
			Multiselect,
			AppScore,
		},
		data() {
			return {
				isSelected: false,
				groupCheckedAppsData: false,
				loading: false,
				scrolled: false,
				filterId: '',
			};
		},
		mounted() {
			if (this.app.groups.length > 0) {
				this.groupCheckedAppsData = true;
			}
			this.isSelected = (this.app.id === this.$route.params.id);
			this.filterId = 'invertIconApps' + Math.floor((Math.random() * 100 )) + new Date().getSeconds() + new Date().getMilliseconds();
		},
		computed: {
			filterUrl() {
				return `url(#${this.filterId})`;
			},
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
				this.$router.push({
					name: 'apps-details',
					params: {category: this.category, id: this.app.id}
				});
			},
			prefix(prefix, content) {
				return prefix + '_' + content;
			},
		}
	}
</script>
