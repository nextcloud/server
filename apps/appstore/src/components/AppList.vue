<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div id="app-content-inner">
		<div
			id="apps-list"
			class="apps-list"
			:class="{
				'apps-list--list-view': (useBundleView || useListView),
				'apps-list--store-view': useAppStoreView,
			}">
			<template v-if="useListView">
				<div v-if="showUpdateAll" class="apps-list__toolbar">
					{{ n('settings', '%n app has an update available', '%n apps have an update available', counter) }}
					<NcButton
						v-if="showUpdateAll"
						id="app-list-update-all"
						variant="primary"
						@click="updateAll">
						{{ n('settings', 'Update', 'Update all', counter) }}
					</NcButton>
				</div>

				<div v-if="!showUpdateAll" class="apps-list__toolbar">
					{{ t('settings', 'All apps are up-to-date.') }}
				</div>

				<TransitionGroup name="apps-list" tag="table" class="apps-list__list-container">
					<tr key="app-list-view-header">
						<th>
							<span class="hidden-visually">{{ t('settings', 'Icon') }}</span>
						</th>
						<th>
							<span class="hidden-visually">{{ t('settings', 'Name') }}</span>
						</th>
						<th>
							<span class="hidden-visually">{{ t('settings', 'Version') }}</span>
						</th>
						<th>
							<span class="hidden-visually">{{ t('settings', 'Level') }}</span>
						</th>
						<th>
							<span class="hidden-visually">{{ t('settings', 'Actions') }}</span>
						</th>
					</tr>
					<AppItem
						v-for="app in apps"
						:key="app.id"
						:app="app"
						:category="category" />
				</TransitionGroup>
			</template>

			<table
				v-if="useBundleView"
				class="apps-list__list-container">
				<tr key="app-list-view-header">
					<th id="app-table-col-icon">
						<span class="hidden-visually">{{ t('settings', 'Icon') }}</span>
					</th>
					<th id="app-table-col-name">
						<span class="hidden-visually">{{ t('settings', 'Name') }}</span>
					</th>
					<th id="app-table-col-version">
						<span class="hidden-visually">{{ t('settings', 'Version') }}</span>
					</th>
					<th id="app-table-col-level">
						<span class="hidden-visually">{{ t('settings', 'Level') }}</span>
					</th>
					<th id="app-table-col-actions">
						<span class="hidden-visually">{{ t('settings', 'Actions') }}</span>
					</th>
				</tr>
				<template v-for="bundle in bundles">
					<tr :key="bundle.id">
						<th :id="`app-table-rowgroup-${bundle.id}`" colspan="5" scope="rowgroup">
							<div class="apps-list__bundle-heading">
								<span class="apps-list__bundle-header">
									{{ bundle.name }}
								</span>
								<NcButton variant="secondary" @click="toggleBundle(bundle.id)">
									{{ t('settings', bundleToggleText(bundle.id)) }}
								</NcButton>
							</div>
						</th>
					</tr>
					<AppItem
						v-for="app in bundleApps(bundle.id)"
						:key="bundle.id + app.id"
						:use-bundle-view="true"
						:headers="`app-table-rowgroup-${bundle.id}`"
						:app="app"
						:category="category" />
				</template>
			</table>
			<ul v-if="useAppStoreView" class="apps-list__store-container">
				<AppItem
					v-for="app in apps"
					:key="app.id"
					:app="app"
					:category="category"
					:list-view="false" />
			</ul>
		</div>

		<div id="apps-list-search" class="apps-list apps-list--list-view">
			<div class="apps-list__list-container">
				<table v-if="search !== '' && searchApps.length > 0" class="apps-list__list-container">
					<caption class="apps-list__bundle-header">
						{{ t('settings', 'Results from other categories') }}
					</caption>
					<tr key="app-list-view-header">
						<th>
							<span class="hidden-visually">{{ t('settings', 'Icon') }}</span>
						</th>
						<th>
							<span class="hidden-visually">{{ t('settings', 'Name') }}</span>
						</th>
						<th>
							<span class="hidden-visually">{{ t('settings', 'Version') }}</span>
						</th>
						<th>
							<span class="hidden-visually">{{ t('settings', 'Level') }}</span>
						</th>
						<th>
							<span class="hidden-visually">{{ t('settings', 'Actions') }}</span>
						</th>
					</tr>
					<AppItem
						v-for="app in searchApps"
						:key="app.id"
						:app="app"
						:category="category" />
				</table>
			</div>
		</div>

		<div v-if="search !== '' && !loading && searchApps.length === 0 && apps.length === 0" id="apps-list-empty" class="emptycontent emptycontent-search">
			<div id="app-list-empty-icon" class="icon-settings-dark" />
			<h2>{{ t('settings', 'No apps found for your version') }}</h2>
		</div>
	</div>
</template>

<script>
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import pLimit from 'p-limit'
import NcButton from '@nextcloud/vue/components/NcButton'
import AppItem from './AppList/AppItem.vue'
import logger from '../utils/logger.ts'
import AppManagement from '../mixins/AppManagement.js'
import { useAppApiStore } from '../store/app-api-store.ts'
import { useAppsStore } from '../store/apps-store.ts'

export default {
	name: 'AppList',
	components: {
		AppItem,
		NcButton,
	},

	mixins: [AppManagement],

	props: {
		category: {
			type: String,
			required: true,
		},
	},

	setup() {
		const appApiStore = useAppApiStore()
		const store = useAppsStore()

		return {
			appApiStore,
			store,
		}
	},

	data() {
		return {
			search: '',
		}
	},

	computed: {
		counter() {
			return this.apps.filter((app) => app.update).length
		},

		loading() {
			if (!this.$store.getters['appApiApps/isAppApiEnabled']) {
				return this.$store.getters.loading('list')
			}
			return this.$store.getters.loading('list') || this.appApiStore.getLoading('list')
		},

		hasPendingUpdate() {
			return this.apps.filter((app) => app.update).length > 0
		},

		showUpdateAll() {
			return this.hasPendingUpdate && this.useListView
		},

		apps() {
			// Exclude ExApps from the list if AppAPI is disabled
			const exApps = this.$store.getters.isAppApiEnabled ? this.appApiStore.getAllApps : []
			const apps = [...this.$store.getters.getAllApps, ...exApps]
				.filter((app) => app.name.toLowerCase().search(this.search.toLowerCase()) !== -1)
				.sort(function(a, b) {
					const natSortDiff = OC.Util.naturalSortCompare(a, b)
					if (natSortDiff === 0) {
						const sortStringA = '' + (a.active ? 0 : 1) + (a.update ? 0 : 1)
						const sortStringB = '' + (b.active ? 0 : 1) + (b.update ? 0 : 1)
						return Number(sortStringA) - Number(sortStringB)
					}
					return natSortDiff
				})

			if (this.category === 'installed') {
				return apps.filter((app) => app.installed)
			}
			if (this.category === 'enabled') {
				return apps.filter((app) => app.active && app.installed)
			}
			if (this.category === 'disabled') {
				return apps.filter((app) => !app.active && app.installed)
			}
			if (this.category === 'app-bundles') {
				return apps.filter((app) => app.bundles)
			}
			if (this.category === 'updates') {
				return apps.filter((app) => app.update)
			}
			if (this.category === 'supported') {
				// For customers of the Nextcloud GmbH the app level will be set to `300` for apps that are supported in their subscription
				return apps.filter((app) => app.level === 300)
			}
			if (this.category === 'featured') {
				// An app level of `200` will be set for apps featured on the app store
				return apps.filter((app) => app.level === 200)
			}

			// filter app store categories
			return apps.filter((app) => {
				return app.appstore && app.category !== undefined
					&& (app.category === this.category || app.category.indexOf(this.category) > -1)
			})
		},

		bundles() {
			return this.$store.getters.getAppBundles.filter((bundle) => this.bundleApps(bundle.id).length > 0)
		},

		bundleApps() {
			return function(bundle) {
				return this.$store.getters.getAllApps
					.filter((app) => {
						return app.bundleIds !== undefined && app.bundleIds.includes(bundle)
					})
			}
		},

		searchApps() {
			if (this.search === '') {
				return []
			}
			const exApps = this.$store.getters.isAppApiEnabled ? this.appApiStore.getAllApps : []
			return [...this.$store.getters.getAllApps, ...exApps]
				.filter((app) => {
					if (app.name.toLowerCase().search(this.search.toLowerCase()) !== -1) {
						return (!this.apps.find((_app) => _app.id === app.id))
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
				return this.bundleApps(id).filter((app) => !app.active).length === 0
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

	beforeDestroy() {
		unsubscribe('nextcloud:unified-search.search', this.setSearch)
		unsubscribe('nextcloud:unified-search.reset', this.resetSearch)
	},

	mounted() {
		subscribe('nextcloud:unified-search.search', this.setSearch)
		subscribe('nextcloud:unified-search.reset', this.resetSearch)
	},

	methods: {
		setSearch({ query }) {
			this.search = query
		},

		resetSearch() {
			this.search = ''
		},

		toggleBundle(id) {
			if (this.allBundlesEnabled(id)) {
				return this.disableBundle(id)
			}
			return this.enableBundle(id)
		},

		enableBundle(id) {
			const apps = this.bundleApps(id).map((app) => app.id)
			this.$store.dispatch('enableApp', { appId: apps, groups: [] })
				.catch((error) => {
					logger.error(error)
					OC.Notification.show(error)
				})
		},

		disableBundle(id) {
			const apps = this.bundleApps(id).map((app) => app.id)
			this.$store.dispatch('disableApp', { appId: apps, groups: [] })
				.catch((error) => {
					OC.Notification.show(error)
				})
		},

		updateAll() {
			const limit = pLimit(1)
			this.apps
				.filter((app) => app.update)
				.map((app) => limit(() => {
					this.update(app.id)
				}))
		},
	},
}
</script>

<style lang="scss" scoped>
$toolbar-padding: 8px;
$toolbar-height: 44px + $toolbar-padding * 2;

.apps-list {
	display: flex;
	flex-wrap: wrap;
	align-content: flex-start;

	// For transition group
	&--move {
		transition: transform 1s;
	}

	#app-list-update-all {
		margin-inline-start: 10px;
	}

	&__toolbar {
		height: $toolbar-height;
		padding: $toolbar-padding;
		// Leave room for app-navigation-toggle
		padding-inline-start: $toolbar-height;
		width: 100%;
		background-color: var(--color-main-background);
		position: sticky;
		top: 0;
		z-index: 1;
		display: flex;
		align-items: center;
	}

	&--list-view {
		margin-bottom: 100px;
		// For positioning link overlay on rows
		position: relative;
	}

	&__list-container {
		width: 100%;
	}

	&__store-container {
		display: flex;
		flex-wrap: wrap;
	}

	&__bundle-heading {
		display: flex;
		align-items: center;
		margin-block: 20px;
		margin-inline: 0 10px;
	}

	&__bundle-header {
		color: var(--color-main-text);
		margin-block: 0;
		margin-inline: 50px 10px;
		font-weight: bold;
		font-size: 20px;
		line-height: 30px;
	}
}

#apps-list-search {
	.app-item {
		h2 {
			margin-bottom: 0;
		}
	}
}
</style>
