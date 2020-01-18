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
	<Content app-name="settings"
		:class="{ 'with-app-sidebar': app}"
		:content-class="{ 'icon-loading': loadingList }"
		:navigation-class="{ 'icon-loading': loading }">
		<AppNavigation>
			<template #list>
				<AppNavigationItem
					id="app-category-your-apps"
					:to="{ name: 'apps' }"
					:exact="true"
					icon="icon-category-installed"
					:title="t('settings', 'Your apps')" />
				<AppNavigationItem
					id="app-category-enabled"
					:to="{ name: 'apps-category', params: { category: 'enabled' } }"
					icon="icon-category-enabled"
					:title="t('settings', 'Active apps')" />
				<AppNavigationItem
					id="app-category-disabled"
					:to="{ name: 'apps-category', params: { category: 'disabled' } }"
					icon="icon-category-disabled"
					:title="t('settings', 'Disabled apps')" />
				<AppNavigationItem
					v-if="updateCount > 0"
					id="app-category-updates"
					:to="{ name: 'apps-category', params: { category: 'updates' } }"
					icon="icon-download"
					:title="t('settings', 'Updates')">
					<AppNavigationCounter slot="counter">
						{{ updateCount }}
					</AppNavigationCounter>
				</AppNavigationItem>
				<AppNavigationItem
					id="app-category-your-bundles"
					:to="{ name: 'apps-category', params: { category: 'app-bundles' } }"
					icon="icon-category-app-bundles"
					:title="t('settings', 'App bundles')" />

				<AppNavigationSpacer />

				<!-- App store categories -->
				<template v-if="settings.appstoreEnabled">
					<AppNavigationItem
						id="app-category-featured"
						:to="{ name: 'apps-category', params: { category: 'featured' } }"
						icon="icon-favorite"
						:title="t('settings', 'Featured apps')" />

					<AppNavigationItem
						v-for="cat in categories"
						:key="'icon-category-' + cat.ident"
						:icon="'icon-category-' + cat.ident"
						:to="{
							name: 'apps-category',
							params: { category: cat.ident },
						}"
						:title="cat.displayName" />
				</template>

				<AppNavigationItem
					id="app-developer-docs"
					href="settings.developerDocumentation"
					:title="t('settings', 'Developer documentation') + ' ↗'" />
			</template>
		</AppNavigation>
		<AppContent class="app-settings-content" :class="{ 'icon-loading': loadingList }">
			<AppList :category="category" :app="app" :search="searchQuery" />
		</AppContent>
		<AppSidebar
			v-if="id && app"
			v-bind="appSidebar"
			:class="{'app-sidebar--without-background': !appSidebar.background}"
			@close="hideAppDetails">
			<template v-if="!appSidebar.background" #header>
				<div class="app-sidebar-header__figure--default-app-icon icon-settings-dark" />
			</template>
			<template #primary-actions>
				<div v-if="app.level === 300 || app.level === 200 || hasRating" class="app-level">
					<span v-if="app.level === 300"
						v-tooltip.auto="t('settings', 'This app is supported via your current Nextcloud subscription.')"
						class="supported icon-checkmark-color">
						{{ t('settings', 'Supported') }}</span>
					<span v-if="app.level === 200"
						v-tooltip.auto="t('settings', 'Featured apps are developed by and within the community. They offer central functionality and are ready for production use.')"
						class="official icon-checkmark">
						{{ t('settings', 'Featured') }}</span>
					<AppScore v-if="hasRating" :score="app.appstoreData.ratingOverall" />
				</div>
			</template>
			<template #secondary-actions>
				<ActionButton v-if="app.update"
					:disabled="installing || isLoading"
					icon="icon-download"
					@click="update(app.id)">
					{{ t('settings', 'Update to {version}', {version: app.update}) }}
				</ActionButton>
				<ActionButton v-if="app.canUnInstall"
					:disabled="installing || isLoading"
					icon="icon-delete"
					@click="remove(app.id)">
					{{ t('settings', 'Remove') }}
				</ActionButton>
				<ActionButton v-if="app.active"
					:disabled="installing || isLoading"
					icon="icon-close"
					@click="disable(app.id)">
					{{ t('settings','Disable') }}
				</ActionButton>
				<ActionButton v-if="!app.active && (app.canInstall || app.isCompatible)"
					v-tooltip.auto="enableButtonTooltip"
					:disabled="!app.canInstall || installing || isLoading"
					icon="icon-checkmark"
					@click="enable(app.id)">
					{{ enableButtonText }}
				</ActionButton>
				<ActionButton v-else-if="!app.active"
					v-tooltip.auto="forceEnableButtonTooltip"
					:disabled="installing || isLoading"
					icon="icon-checkmark"
					@click="forceEnable(app.id)">
					{{ forceEnableButtonText }}
				</ActionButton>
			</template>
			<AppDetails :category="category" :app="app" />
		</AppSidebar>
	</Content>
</template>

<script>
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationCounter from '@nextcloud/vue/dist/Components/AppNavigationCounter'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationSpacer from '@nextcloud/vue/dist/Components/AppNavigationSpacer'
import AppSidebar from '@nextcloud/vue/dist/Components/AppSidebar'
import Content from '@nextcloud/vue/dist/Components/Content'
import Vue from 'vue'
import VueLocalStorage from 'vue-localstorage'

import AppList from '../components/AppList'
import AppDetails from '../components/AppDetails'
import AppManagement from '../mixins/AppManagement'

Vue.use(VueLocalStorage)

export default {
	name: 'Apps',
	components: {
		ActionButton,
		AppContent,
		AppDetails,
		AppList,
		AppNavigation,
		AppNavigationCounter,
		AppNavigationItem,
		AppNavigationSpacer,
		AppSidebar,
		Content,
	},
	mixins: [AppManagement],
	props: {
		category: {
			type: String,
			default: 'installed',
		},
		id: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			searchQuery: '',
		}
	},
	computed: {
		loading() {
			return this.$store.getters.loading('categories')
		},
		loadingList() {
			return this.$store.getters.loading('list')
		},
		app() {
			return this.apps.find(app => app.id === this.id)
		},
		categories() {
			return this.$store.getters.getCategories
		},
		apps() {
			return this.$store.getters.getAllApps
		},
		updateCount() {
			return this.$store.getters.getUpdateCount
		},
		settings() {
			return this.$store.getters.getServerData
		},

		// sidebar app binding
		appSidebar() {
			const author = Array.isArray(this.app.author)
				? this.app.author[0]['@value']
					? this.app.author.map(author => author['@value']).join(', ')
					: this.app.author.join(', ')
				: this.app.author['@value']
					? this.app.author['@value']
					: this.app.author
			const license = t('settings', '{license}-licensed', { license: ('' + this.app.licence).toUpperCase() })

			const subtitle = t('settings', 'by {author}\n{license}', { author, license })

			return {
				subtitle,
				background: this.app.screenshot
					? this.app.screenshot
					: this.app.preview,
				compact: !this.app.screenshot,
				title: this.app.name,

			}
		},
	},
	watch: {
		category(val, old) {
			this.setSearch('')
		},
	},
	beforeMount() {
		this.$store.dispatch('getCategories')
		this.$store.dispatch('getAllApps')
		this.$store.dispatch('getGroups', { offset: 0, limit: 5 })
		this.$store.commit('setUpdateCount', this.$store.getters.getServerData.updateCount)
	},
	mounted() {
		/**
		 * Register search
		 */
		this.appSearch = new OCA.Search(this.setSearch, this.resetSearch)
	},
	methods: {
		setSearch(query) {
			this.searchQuery = query
		},
		resetSearch() {
			this.setSearch('')
		},
		hideAppDetails() {
			this.$router.push({
				name: 'apps-category',
				params: { category: this.category },
			})
		},
	},
}
</script>

<style lang="scss" scoped>

#app-sidebar::v-deep {
	&:not(.app-sidebar--without-background) {
		// with full screenshot, let's fill the figure
		:not(.app-sidebar-header--compact) .app-sidebar-header__figure {
			background-size: cover
		}
		// revert sidebar app icon so it is black
		.app-sidebar-header--compact .app-sidebar-header__figure {
			filter: invert(1);
			background-size: 32px;
		}
	}

	// default icon slot styling
	&.app-sidebar--without-background {
		.app-sidebar-header__figure {
			display: flex;
			align-items: center;
			justify-content: center;
			&--default-app-icon {
				height: 32px;
				width: 32px;
				background-size: 32px;
			}
		}
	}

	// allow multi line subtitle for the license
	.app-sidebar-header__subtitle {
		white-space: pre-line !important;
		line-height: 16px;
		overflow: visible !important;
		height: 22px;
	}
}

</style>
