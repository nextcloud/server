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
	<div id="app-content" class="app-settings-content" :class="{ 'with-app-sidebar': app }">
		<div id="apps-list" class="installed" v-if="useListView">
			<app-item v-for="app in apps" :key="app.id" :app="app" :category="category" />
		</div>

		<div id="apps-list" class="installed" v-if="useBundleView">
			<template v-for="app in apps">
				<div class="apps-header" v-if="app.newCategory">
					<div class="app-image"></div>
					<h2>{{ app.categoryName }} <input class="enable" type="button" value="Alle aktivieren"></h2>
					<div class="app-version"></div>
					<div class="app-level"></div>
					<div class="app-groups"></div>
					<div class="actions">&nbsp;</div>
				</div>
				<app-item v-else :key="app.id" :app="app" :category="category"/>
			</template>
		</div>

		<div id="apps-list" class="store" v-if="useAppStoreView">
			<app-item v-for="app in apps" :key="app.id" :app="app" :category="category" :list-view="false" />
		</div>

		<div id="apps-list" class="installed" v-if="search !== ''">
			<div class="section">
				<div></div>
				<h2>{{ t('settings', 'Results from other categories') }}</h2>
			</div>
			<app-item v-for="app in searchApps" :key="app.id" :app="app" :category="category" :list-view="true" />
		</div>

		<div id="apps-list-empty" class="emptycontent emptycontent-search" v-if="apps.length == 0 && loading">
			<div id="app-list-empty-icon" class="icon-search"></div>
			<h2>{{ t('settings', 'No apps found for your versoin')}}</h2>
		</div>

		<div id="searchresults"></div>
	</div>
</template>

<script>
import appItem from './appList/appItem';
import Multiselect from 'vue-multiselect';
import prefix from './prefixMixin';

export default {
	name: 'appList',
	mixins: [prefix],
	props: ['category', 'app', 'search'],
	components: {
		Multiselect,
		appItem
	},
	data() {
		return {
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
			return this.$store.getters.getApps
				.filter(app => app.name.toLowerCase().search(this.search.toLowerCase()) !== -1)
		},
		searchApps() {
			return this.$store.getters.getAllApps
				.filter(app => {
					if (app.name.toLowerCase().search(this.search.toLowerCase()) !== -1) {
						return (!this.apps.find(_app => _app.id === app.id));
					}
					return false;

				});
		},
		groups() {
			return this.$store.getters.getGroups
				.filter(group => group.id !== 'disabled')
				.sort((a, b) => a.name.localeCompare(b.name));
		},
		useAppStoreView() {
			return !this.useListView && !this.useBundleView;
		},
		useListView() {
			return (this.category === 'installed' || this.category === 'enabled' || this.category === 'disabled' || this.category === 'updates');
		},
		useBundleView() {
			return (this.category === 'app-bundles');
		}
	},
	methods: {

	}
}
</script>
