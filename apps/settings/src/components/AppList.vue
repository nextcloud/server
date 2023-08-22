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
		<div id="apps-list" class="apps-list" :class="{installed: (useBundleView || useListView), store: useAppStoreView}">
			<template v-if="useListView">
				<div v-if="showUpdateAll" class="toolbar">
					{{ n('settings', '%n app has an update available', '%n apps have an update available', counter) }}
					<NcButton v-if="showUpdateAll"
						id="app-list-update-all"
						type="primary"
						@click="updateAll">
						{{ n('settings', 'Update', 'Update all', counter) }}
					</NcButton>
				</div>

				<div v-if="!showUpdateAll" class="toolbar">
					{{ t('settings', 'All apps are up-to-date.') }}
				</div>

				<transition-group name="app-list" tag="table" class="apps-list-container">
					<tr key="app-list-view-header" class="apps-header">
						<th class="app-image">
							<span class="hidden-visually">{{ t('settings', 'Icon') }}</span>
						</th>
						<th class="app-name">
							<span class="hidden-visually">{{ t('settings', 'Name') }}</span>
						</th>
						<th class="app-version">
							<span class="hidden-visually">{{ t('settings', 'Version') }}</span>
						</th>
						<th class="app-level">
							<span class="hidden-visually">{{ t('settings', 'Level') }}</span>
						</th>
						<th class="actions">
							<span class="hidden-visually">{{ t('settings', 'Actions') }}</span>
						</th>
					</tr>
					<AppItem v-for="app in apps"
						:key="app.id"
						:app="app"
						:category="category" />
				</transition-group>
			</template>

			<table v-if="useBundleView"
				class="apps-list-container">
				<tr key="app-list-view-header" class="apps-header">
					<th id="app-table-col-icon" class="app-image">
						<span class="hidden-visually">{{ t('settings', 'Icon') }}</span>
					</th>
					<th id="app-table-col-name" class="app-name">
						<span class="hidden-visually">{{ t('settings', 'Name') }}</span>
					</th>
					<th id="app-table-col-version" class="app-version">
						<span class="hidden-visually">{{ t('settings', 'Version') }}</span>
					</th>
					<th id="app-table-col-level" class="app-level">
						<span class="hidden-visually">{{ t('settings', 'Level') }}</span>
					</th>
					<th id="app-table-col-actions" class="actions">
						<span class="hidden-visually">{{ t('settings', 'Actions') }}</span>
					</th>
				</tr>
				<template v-for="bundle in bundles">
					<tr :key="bundle.id">
						<th :id="`app-table-rowgroup-${bundle.id}`" colspan="5" scope="rowgroup">
							<div class="app-bundle-heading">
								<span class="app-bundle-header">
									{{ bundle.name }}
								</span>
								<NcButton type="secondary" @click="toggleBundle(bundle.id)">
									{{ t('settings', bundleToggleText(bundle.id)) }}
								</NcButton>
							</div>
						</th>
					</tr>
					<AppItem v-for="app in bundleApps(bundle.id)"
						:key="bundle.id + app.id"
						:use-bundle-view="true"
						:headers="`app-table-rowgroup-${bundle.id}`"
						:app="app"
						:category="category" />
				</template>
			</table>
			<template v-if="useAppStoreView">
				<AppItem v-for="app in apps"
					:key="app.id"
					:app="app"
					:category="category"
					:list-view="false" />
			</template>
		</div>

		<div id="apps-list-search" class="apps-list installed">
			<div class="apps-list-container">
				<template v-if="search !== '' && searchApps.length > 0">
					<div class="section">
						<div />
						<td colspan="5">
							<h2>{{ t('settings', 'Results from other categories') }}</h2>
						</td>
					</div>
					<AppItem v-for="app in searchApps"
						:key="app.id"
						:app="app"
						:category="category" />
				</template>
			</div>
		</div>

		<div v-if="search !== '' && !loading && searchApps.length === 0 && apps.length === 0" id="apps-list-empty" class="emptycontent emptycontent-search">
			<div id="app-list-empty-icon" class="icon-settings-dark" />
			<h2>{{ t('settings', 'No apps found for your version') }}</h2>
		</div>

		<div id="searchresults" />
	</div>
</template>

<script>
import AppItem from './AppList/AppItem.vue'
import PrefixMixin from './PrefixMixin.vue'
import pLimit from 'p-limit'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'

export default {
	name: 'AppList',
	components: {
		AppItem,
		NcButton,
	},
	mixins: [PrefixMixin],
	props: ['category', 'app', 'search'],
	computed: {
		counter() {
			return this.apps.filter(app => app.update).length
		},
		loading() {
			return this.$store.getters.loading('list')
		},
		hasPendingUpdate() {
			return this.apps.filter(app => app.update).length > 0
		},
		showUpdateAll() {
			return this.hasPendingUpdate && this.useListView
		},
		apps() {
			const apps = this.$store.getters.getAllApps
				.filter(app => app.name.toLowerCase().search(this.search.toLowerCase()) !== -1)
				.sort(function(a, b) {
					const sortStringA = '' + (a.active ? 0 : 1) + (a.update ? 0 : 1) + a.name
					const sortStringB = '' + (b.active ? 0 : 1) + (b.update ? 0 : 1) + b.name
					return OC.Util.naturalSortCompare(sortStringA, sortStringB)
				})

			if (this.category === 'installed') {
				return apps.filter(app => app.installed)
			}
			if (this.category === 'enabled') {
				return apps.filter(app => app.active && app.installed)
			}
			if (this.category === 'disabled') {
				return apps.filter(app => !app.active && app.installed)
			}
			if (this.category === 'app-bundles') {
				return apps.filter(app => app.bundles)
			}
			if (this.category === 'updates') {
				return apps.filter(app => app.update)
			}
			if (this.category === 'supported') {
				// For customers of the Nextcloud GmbH the app level will be set to `300` for apps that are supported in their subscription
				return apps.filter(app => app.level === 300)
			}
			if (this.category === 'featured') {
				// An app level of `200` will be set for apps featured on the app store
				return apps.filter(app => app.level === 200)
			}
			// filter app store categories
			return apps.filter(app => {
				return app.appstore && app.category !== undefined
					&& (app.category === this.category || app.category.indexOf(this.category) > -1)
			})
		},
		bundles() {
			return this.$store.getters.getServerData.bundles.filter(bundle => this.bundleApps(bundle.id).length > 0)
		},
		bundleApps() {
			return function(bundle) {
				return this.$store.getters.getAllApps
					.filter(app => {
						return app.bundleIds !== undefined && app.bundleIds.includes(bundle)
					})
			}
		},
		searchApps() {
			if (this.search === '') {
				return []
			}
			return this.$store.getters.getAllApps
				.filter(app => {
					if (app.name.toLowerCase().search(this.search.toLowerCase()) !== -1) {
						return (!this.apps.find(_app => _app.id === app.id))
					}
					return false
				})
		},
		useAppStoreView() {
			return !this.useListView && !this.useBundleView
		},
		useListView() {
			return (this.category === 'installed' || this.category === 'enabled' || this.category === 'disabled' || this.category === 'updates' || this.category === 'featured' || this.category === 'supported')
		},
		useBundleView() {
			return (this.category === 'app-bundles')
		},
		allBundlesEnabled() {
			return (id) => {
				return this.bundleApps(id).filter(app => !app.active).length === 0
			}
		},
		bundleToggleText() {
			return (id) => {
				if (this.allBundlesEnabled(id)) {
					return t('settings', 'Disable all')
				}
				return t('settings', 'Download and enable all')
			}
		},
	},
	methods: {
		toggleBundle(id) {
			if (this.allBundlesEnabled(id)) {
				return this.disableBundle(id)
			}
			return this.enableBundle(id)
		},
		enableBundle(id) {
			const apps = this.bundleApps(id).map(app => app.id)
			this.$store.dispatch('enableApp', { appId: apps, groups: [] })
				.catch((error) => {
					console.error(error)
					OC.Notification.show(error)
				})
		},
		disableBundle(id) {
			const apps = this.bundleApps(id).map(app => app.id)
			this.$store.dispatch('disableApp', { appId: apps, groups: [] })
				.catch((error) => {
					OC.Notification.show(error)
				})
		},
		updateAll() {
			const limit = pLimit(1)
			this.apps
				.filter(app => app.update)
				.map(app => limit(() => this.$store.dispatch('updateApp', { appId: app.id })),
				)
		},
	},
}
</script>

<style lang="scss" scoped>
	.app-bundle-heading {
		display: flex;
		align-items: center;
		margin: 20px 10px 20px 0;
	}
	.app-bundle-header {
		margin: 0 10px 0 50px;
		font-weight: bold;
		font-size: 20px;
		line-height: 30px;
		color: var(--color-text-light);
	}
</style>
