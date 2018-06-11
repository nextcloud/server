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
	<div id="app-content-inner">
		<div id="apps-list" :class="{installed: (useBundleView || useListView), store: useAppStoreView}">
			<template v-if="useListView">
				<app-item v-for="app in apps" :key="app.id" :app="app" :category="category" />
			</template>
			<template v-for="bundle in bundles" v-if="useBundleView && bundleApps(bundle.id).length > 0">
				<div class="apps-header" :key="bundle.id">
					<div class="app-image"></div>
					<h2>{{ bundle.name }} <input type="button" :value="bundleToggleText(bundle.id)" v-on:click="toggleBundle(bundle.id)"></h2>
					<div class="app-version"></div>
					<div class="app-level"></div>
					<div class="app-groups"></div>
					<div class="actions">&nbsp;</div>
				</div>
				<app-item v-for="app in bundleApps(bundle.id)" :key="bundle.id + app.id" :app="app" :category="category"/>
			</template>
			<template v-if="useAppStoreView">
				<app-item v-for="app in apps" :key="app.id" :app="app" :category="category" :list-view="false" />
			</template>

		</div>

		<div id="apps-list-search" class="installed">
			<template v-if="search !== '' && searchApps.length > 0">
				<div class="section">
					<div></div>
					<h2>{{ t('settings', 'Results from other categories') }}</h2>
				</div>
				<app-item v-for="app in searchApps" :key="app.id" :app="app" :category="category" :list-view="true" />
			</template>
		</div>

		<div id="apps-list-empty" class="emptycontent emptycontent-search" v-if="!loading && searchApps.length === 0 && apps.length === 0">
			<div id="app-list-empty-icon" class="icon-settings-dark"></div>
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
	computed: {
		loading() {
			return this.$store.getters.loading('list');
		},
		apps() {
			let apps = this.$store.getters.getAllApps
				.filter(app => app.name.toLowerCase().search(this.search.toLowerCase()) !== -1)
				.sort(function (a, b) {
					if (a.active !== b.active) {
						return (a.active ? -1 : 1)
					}
					if (a.update !== b.update) {
						return (a.update ? -1 : 1)
					}
					return OC.Util.naturalSortCompare(a.name, b.name);
				});

			if (this.category === 'installed') {
				return apps.filter(app => app.installed);
			}
			if (this.category === 'enabled') {
				return apps.filter(app => app.active);
			}
			if (this.category === 'disabled') {
				return apps.filter(app => !app.active && app.installed);
			}
			if (this.category === 'app-bundles') {
				return apps.filter(app => app.bundles);
			}
			if (this.category === 'updates') {
				return apps.filter(app => app.update);
			}
			// filter app store categories
			return apps.filter(app => {
				return app.appstore && app.category !== undefined &&
					(app.category === this.category || app.category.indexOf(this.category) > -1);
			});
		},
		bundles() {
			return this.$store.getters.getServerData.bundles;
		},
		bundleApps() {
			return function(bundle) {
				return this.$store.getters.getAllApps
					.filter(app => app.bundleId === bundle);
			}
		},
		searchApps() {
			if (this.search === '') {
				return [];
			}
			return this.$store.getters.getAllApps
				.filter(app => {
					if (app.name.toLowerCase().search(this.search.toLowerCase()) !== -1) {
						return (!this.apps.find(_app => _app.id === app.id));
					}
					return false;
				});
		},
		useAppStoreView() {
			return !this.useListView && !this.useBundleView;
		},
		useListView() {
			return (this.category === 'installed' || this.category === 'enabled' || this.category === 'disabled' || this.category === 'updates');
		},
		useBundleView() {
			return (this.category === 'app-bundles');
		},
		allBundlesEnabled() {
			let self = this;
			return function(id) {
				return self.bundleApps(id).filter(app => !app.active).length === 0;
			}
		},
		bundleToggleText() {
			let self = this;
			return function(id) {
				if (self.allBundlesEnabled(id)) {
					return t('settings', 'Disable all');
				}
				return t('settings', 'Enable all');
			}
		}
	},
	methods: {
		toggleBundle(id) {
			if (this.allBundlesEnabled(id)) {
				return this.disableBundle(id);
			}
			return this.enableBundle(id);
		},
		enableBundle(id) {
			let apps = this.bundleApps(id).map(app => app.id);
			this.$store.dispatch('enableApp', { appId: apps, groups: [] })
				.catch((error) => { console.log(error); OC.Notification.show(error)});
		},
		disableBundle(id) {
			let apps = this.bundleApps(id).map(app => app.id);
			this.$store.dispatch('disableApp', { appId: apps, groups: [] })
				.catch((error) => { OC.Notification.show(error)});
		}
	},
}
</script>
