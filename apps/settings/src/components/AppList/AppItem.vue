<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<component
		:is="listView ? 'tr' : (inline ? 'article' : 'li')"
		class="app-item"
		:class="{
			'app-item--list-view': listView,
			'app-item--store-view': !listView,
			'app-item--selected': isSelected,
			'app-item--with-sidebar': withSidebar,
		}">
		<component
			:is="dataItemTag"
			class="app-image app-image-icon"
			:headers="getDataItemHeaders(`app-table-col-icon`)">
			<div v-if="!app?.app_api && shouldDisplayDefaultIcon" class="icon-settings-dark" />
			<NcIconSvgWrapper
				v-else-if="app.app_api && shouldDisplayDefaultIcon"
				:path="mdiCogOutline"
				:size="listView ? 24 : 48"
				style="min-width: auto; min-height: auto; height: 100%;" />

			<svg
				v-else-if="listView && app.preview && !app.app_api"
				width="32"
				height="32"
				viewBox="0 0 32 32">
				<image
					x="0"
					y="0"
					width="32"
					height="32"
					preserveAspectRatio="xMinYMin meet"
					:xlink:href="app.preview"
					class="app-icon" />
			</svg>

			<img v-if="!listView && app.screenshot && screenshotLoaded" :src="app.screenshot" alt="">
		</component>
		<component
			:is="dataItemTag"
			class="app-name"
			:headers="getDataItemHeaders(`app-table-col-name`)">
			<router-link
				class="app-name--link"
				:to="{
					name: 'apps-details',
					params: {
						category: category,
						id: app.id,
					},
				}"
				:aria-label="t('settings', 'Show details for {appName} app', { appName: app.name })">
				{{ app.name }}
			</router-link>
		</component>
		<component
			:is="dataItemTag"
			v-if="!listView"
			class="app-summary"
			:headers="getDataItemHeaders(`app-version`)">
			{{ app.summary }}
		</component>
		<component
			:is="dataItemTag"
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
		<component
			:is="dataItemTag"
			v-if="!inline"
			:headers="getDataItemHeaders(`app-table-col-actions`)"
			class="app-actions">
			<div v-if="app.error" class="warning">
				{{ app.error }}
			</div>
			<div v-if="isLoading || isInitializing" class="icon icon-loading-small" />
			<NcButton
				v-if="app.update"
				variant="primary"
				:disabled="installing || isLoading || !defaultDeployDaemonAccessible || isManualInstall"
				:title="updateButtonText"
				@click.stop="update(app.id)">
				{{ t('settings', 'Update to {update}', { update: app.update }) }}
			</NcButton>
			<NcButton
				v-if="app.canUnInstall"
				class="uninstall"
				variant="tertiary"
				:disabled="installing || isLoading"
				@click.stop="remove(app.id)">
				{{ t('settings', 'Remove') }}
			</NcButton>
			<NcButton
				v-if="app.active"
				:disabled="installing || isLoading || isInitializing || isDeploying"
				@click.stop="disable(app.id)">
				{{ disableButtonText }}
			</NcButton>
			<NcButton
				v-if="!app.active && (app.canInstall || app.isCompatible)"
				:title="enableButtonTooltip"
				:aria-label="enableButtonTooltip"
				variant="primary"
				:disabled="!app.canInstall || installing || isLoading || !defaultDeployDaemonAccessible || isInitializing || isDeploying"
				@click.stop="enableButtonAction">
				{{ enableButtonText }}
			</NcButton>
			<NcButton
				v-else-if="!app.active"
				:title="forceEnableButtonTooltip"
				:aria-label="forceEnableButtonTooltip"
				variant="secondary"
				:disabled="installing || isLoading || !defaultDeployDaemonAccessible"
				@click.stop="forceEnable(app.id)">
				{{ forceEnableButtonText }}
			</NcButton>

			<DaemonSelectionDialog
				v-if="app?.app_api && showSelectDaemonModal"
				:show.sync="showSelectDaemonModal"
				:app="app" />
		</component>
	</component>
</template>

<script>
import { mdiCogOutline } from '@mdi/js'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import DaemonSelectionDialog from '../AppAPI/DaemonSelectionDialog.vue'
import SvgFilterMixin from '../SvgFilterMixin.vue'
import AppLevelBadge from './AppLevelBadge.vue'
import AppScore from './AppScore.vue'
import AppManagement from '../../mixins/AppManagement.js'
import { useAppApiStore } from '../../store/app-api-store.ts'
import { useAppsStore } from '../../store/apps-store.js'

export default {
	name: 'AppItem',
	components: {
		AppLevelBadge,
		AppScore,
		NcButton,
		NcIconSvgWrapper,
		DaemonSelectionDialog,
	},

	mixins: [AppManagement, SvgFilterMixin],
	props: {
		app: {
			type: Object,
			required: true,
		},

		category: {
			type: String,
			required: true,
		},

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

		inline: {
			type: Boolean,
			default: false,
		},
	},

	setup() {
		const store = useAppsStore()
		const appApiStore = useAppApiStore()

		return {
			store,
			appApiStore,
			mdiCogOutline,
		}
	},

	data() {
		return {
			isSelected: false,
			scrolled: false,
			screenshotLoaded: false,
			showSelectDaemonModal: false,
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

		shouldDisplayDefaultIcon() {
			return (this.listView && !this.app.preview) || (!this.listView && !this.screenshotLoaded)
		},
	},

	watch: {
		'$route.params.id': function(id) {
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

		showSelectionModal() {
			this.showSelectDaemonModal = true
		},

		async enableButtonAction() {
			if (!this.app?.app_api) {
				this.enable(this.app.id)
				return
			}
			await this.appApiStore.fetchDockerDaemons()
			if (this.appApiStore.dockerDaemons.length === 1 && this.app.needsDownload) {
				this.enable(this.app.id, this.appApiStore.dockerDaemons[0])
			} else if (this.app.needsDownload) {
				this.showSelectionModal()
			} else {
				this.enable(this.app.id, this.app.daemon)
			}
		},
	},
}
</script>

<style scoped lang="scss">
@use '../../../../../core/css/variables.scss' as variables;
@use 'sass:math';

.app-item {
	position: relative;

	&:hover {
		background-color: var(--color-background-dark);
	}

	&--list-view {
		--app-item-padding: calc(var(--default-grid-baseline) * 2);
		--app-item-height: calc(var(--default-clickable-area) + var(--app-item-padding) * 2);

		&.app-item--selected {
			background-color: var(--color-background-dark);
		}

		> * {
			vertical-align: middle;
			border-bottom: 1px solid var(--color-border);
			padding: var(--app-item-padding);
			height: var(--app-item-height);
		}

		.app-image {
			width: var(--default-clickable-area);
			height: auto;
			text-align: end;
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

		.app-name {
			padding: 0 var(--app-item-padding);
		}

		.app-name--link {
			height: var(--app-item-height);
			display: flex;
			align-items: center;
		}

		// Note: because of Safari bug, we cannot position link overlay relative to the table row
		// So we need to manually position it relative to the table container and cell
		// See: https://bugs.webkit.org/show_bug.cgi?id=240961
		.app-name--link::after {
			content: '';
			position: absolute;
			inset-inline: 0;
			height: var(--app-item-height);
		}

		.app-actions {
			display: flex;
			gap: var(--app-item-padding);
			flex-wrap: wrap;
			justify-content: end;

			.icon-loading-small {
				display: inline-block;
				top: 4px;
				margin-inline-end: 10px;
			}
		}

		/* hide app version and level on narrower screens */
		@media only screen and (max-width: 900px) {
			.app-version,
			.app-level {
				display: none;
			}
		}

		/* Hide actions on a small screen. Click on app opens fill-screen sidebar with the buttons */
		@media only screen and (max-width: math.div(variables.$breakpoint-mobile, 2)) {
			.app-actions {
				display: none;
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

		.app-name--link::after {
			content: '';
			position: absolute;
			inset-block: 0;
			inset-inline: 0;
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

.app-version {
	color: var(--color-text-maxcontrast);
}
</style>
