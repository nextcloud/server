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
	<component :is="listView ? `tr` : `li`"
		class="app-item"
		:class="{
			'app-item--list-view': listView,
			'app-item--store-view': !listView,
			'app-item--selected': isSelected,
			'app-item--with-sidebar': withSidebar,
		}">
		<component :is="dataItemTag"
			class="app-image app-image-icon"
			:headers="getDataItemHeaders(`app-table-col-icon`)">
			<div v-if="(listView && !app.preview) || (!listView && !screenshotLoaded)" class="icon-settings-dark" />

			<svg v-else-if="listView && app.preview"
				width="32"
				height="32"
				viewBox="0 0 32 32">
				<image x="0"
					y="0"
					width="32"
					height="32"
					preserveAspectRatio="xMinYMin meet"
					:xlink:href="app.preview"
					class="app-icon" />
			</svg>

			<img v-if="!listView && app.screenshot && screenshotLoaded" :src="app.screenshot" alt="">
		</component>
		<component :is="dataItemTag"
			class="app-name"
			:headers="getDataItemHeaders(`app-table-col-name`)">
			<router-link class="app-name--link"
				:to="{
					name: 'apps-details',
					params: {
						category: category,
						id: app.id
					},
				}"
				:aria-label="t('settings', 'Show details for {appName} app', { appName:app.name })">
				{{ app.name }}
			</router-link>
		</component>
		<component :is="dataItemTag"
			v-if="!listView"
			class="app-summary"
			:headers="getDataItemHeaders(`app-version`)">
			{{ app.summary }}
		</component>
		<component :is="dataItemTag"
			v-if="listView"
			class="app-version"
			:headers="getDataItemHeaders(`app-table-col-version`)">
			<span v-if="app.version">{{ app.version }}</span>
			<span v-else-if="app.appstoreData.releases[0].version">{{ app.appstoreData.releases[0].version }}</span>
		</component>

		<component :is="dataItemTag" :headers="getDataItemHeaders(`app-table-col-level`)" class="app-level">
			<AppLevelBadge :level="app.level" />
			<AppScore v-if="hasRating && !listView" :score="app.score" />
		</component>
		<component :is="dataItemTag" :headers="getDataItemHeaders(`app-table-col-actions`)" class="app-actions">
			<div v-if="app.error" class="warning">
				{{ app.error }}
			</div>
			<div v-if="isLoading" class="icon icon-loading-small" />
			<NcButton v-if="app.update"
				type="primary"
				:disabled="installing || isLoading"
				@click.stop="update(app.id)">
				{{ t('settings', 'Update to {update}', {update:app.update}) }}
			</NcButton>
			<NcButton v-if="app.canUnInstall"
				class="uninstall"
				type="tertiary"
				:disabled="installing || isLoading"
				@click.stop="remove(app.id)">
				{{ t('settings', 'Remove') }}
			</NcButton>
			<NcButton v-if="app.active"
				:disabled="installing || isLoading"
				@click.stop="disable(app.id)">
				{{ t('settings','Disable') }}
			</NcButton>
			<NcButton v-if="!app.active && (app.canInstall || app.isCompatible)"
				:title="enableButtonTooltip"
				:aria-label="enableButtonTooltip"
				type="primary"
				:disabled="!app.canInstall || installing || isLoading"
				@click.stop="enable(app.id)">
				{{ enableButtonText }}
			</NcButton>
			<NcButton v-else-if="!app.active"
				:title="forceEnableButtonTooltip"
				:aria-label="forceEnableButtonTooltip"
				type="secondary"
				:disabled="installing || isLoading"
				@click.stop="forceEnable(app.id)">
				{{ forceEnableButtonText }}
			</NcButton>
		</component>
	</component>
</template>

<script>
import AppScore from './AppScore.vue'
import AppLevelBadge from './AppLevelBadge.vue'
import AppManagement from '../../mixins/AppManagement.js'
import SvgFilterMixin from '../SvgFilterMixin.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'

export default {
	name: 'AppItem',
	components: {
		AppLevelBadge,
		AppScore,
		NcButton,
	},
	mixins: [AppManagement, SvgFilterMixin],
	props: {
		app: {
			type: Object,
			required: true,
		},
		category: {},
		listView: {
			type: Boolean,
			default: true,
		},
		useBundleView: {
			type: Boolean,
			default: false,
		},
		headers: {
			type: String,
			default: null,
		},
	},
	data() {
		return {
			isSelected: false,
			scrolled: false,
			screenshotLoaded: false,
		}
	},
	computed: {
		hasRating() {
			return this.app.appstoreData && this.app.appstoreData.ratingNumOverall > 5
		},
		dataItemTag() {
			return this.listView ? 'td' : 'div'
		},
		withSidebar() {
			return !!this.$route.params.id
		},
	},
	watch: {
		'$route.params.id'(id) {
			this.isSelected = (this.app.id === id)
		},
	},
	mounted() {
		this.isSelected = (this.app.id === this.$route.params.id)
		if (this.app.releases && this.app.screenshot) {
			const image = new Image()
			image.onload = () => {
				this.screenshotLoaded = true
			}
			image.src = this.app.screenshot
		}
	},
	watchers: {

	},
	methods: {
		prefix(prefix, content) {
			return prefix + '_' + content
		},

		getDataItemHeaders(columnName) {
			return this.useBundleView ? [this.headers, columnName].join(' ') : null
		},
	},
}
</script>

<style scoped lang="scss">
@use '../../../../../core/css/variables.scss' as variables;

.app-item {
	position: relative;

	&:hover {
		background-color: var(--color-background-dark);
	}

	&--list-view {
		&.app-item--selected {
			background-color: var(--color-background-dark);
		}

		> * {
			vertical-align: middle;
			border-bottom: 1px solid var(--color-border);
			padding: 6px;
		}

		.app-image {
			width: 44px;
			height: auto;
			text-align: right;
		}

		.app-image-icon svg,
		.app-image-icon .icon-settings-dark {
			margin-top: 5px;
			width: 20px;
			height: 20px;
			opacity: .5;
			background-size: cover;
			display: inline-block;
		}

		.app-actions {
			display: flex;
			gap: 8px;
			flex-wrap: wrap;
			justify-content: end;

			.icon-loading-small {
				display: inline-block;
				top: 4px;
				margin-right: 10px;
			}
		}

		/* hide app version and level on narrower screens */
		@media only screen and (max-width: 900px) {
			.app-version,
			.app-level {
				display: none !important;
			}
		}
	}

	&--store-view {
		padding: 30px;

		.app-image-icon .icon-settings-dark {
			width: 100%;
			height: 150px;
			background-size: 45px;
			opacity: 0.5;
		}

		.app-image-icon svg {
			position: absolute;
			bottom: 43px;
			/* position halfway vertically */
			width: 64px;
			height: 64px;
			opacity: .1;
		}

		.app-name {
			margin: 5px 0;
		}

		.app-actions {
			margin: 10px 0;
		}

		@media only screen and (min-width: 1601px) {
			width: 25%;

			&.app-item--with-sidebar {
				width: 33%;
			}
		}

		@media only screen and (max-width: 1600px) {
			width: 25%;

			&.app-item--with-sidebar {
				width: 33%;
			}
		}

		@media only screen and (max-width: 1400px) {
			width: 33%;

			&.app-item--with-sidebar {
				width: 50%;
			}
		}

		@media only screen and (max-width: 900px) {
			width: 50%;

			&.app-item--with-sidebar {
				width: 100%;
			}
		}

		@media only screen and (max-width: variables.$breakpoint-mobile) {
			width: 50%;
		}

		@media only screen and (max-width: 480px) {
			width: 100%;
		}
	}
}

.app-icon {
	filter: var(--background-invert-if-bright);
}

.app-image {
	position: relative;
	height: 150px;
	opacity: 1;
	overflow: hidden;

	img {
		width: 100%;
	}
}

.app-name--link::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.app-version {
	color: var(--color-text-maxcontrast);
}
</style>
