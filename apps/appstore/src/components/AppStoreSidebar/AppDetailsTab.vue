<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppSidebarTab
		id="details"
		:name="t('appstore', 'Details')"
		:order="1">
		<template #icon>
			<NcIconSvgWrapper :path="mdiTextBoxOutline" />
		</template>
		<div class="app-details">
			<ul class="app-details__dependencies">
				<li v-if="app.missingMinOwnCloudVersion">
					{{ t('appstore', 'This app has no minimum {productName} version assigned. This will be an error in the future.', { productName }) }}
				</li>
				<li v-if="app.missingMaxOwnCloudVersion">
					{{ t('appstore', 'This app has no maximum {productName} version assigned. This will be an error in the future.', { productName }) }}
				</li>
				<li v-if="!app.canInstall">
					{{ t('appstore', 'This app cannot be installed because the following dependencies are not fulfilled:') }}
					<ul class="missing-dependencies">
						<li v-for="(dep, index) in app.missingDependencies" :key="index">
							{{ dep }}
						</li>
					</ul>
				</li>
			</ul>

			<div v-if="lastModified && !app.shipped" class="app-details__section">
				<h4>
					{{ t('appstore', 'Latest updated') }}
				</h4>
				<NcDateTime :timestamp="lastModified" />
			</div>

			<div class="app-details__section">
				<h4>
					{{ t('appstore', 'Author') }}
				</h4>
				<p class="app-details__authors">
					{{ appAuthors }}
				</p>
			</div>

			<div class="app-details__section">
				<h4>
					{{ t('appstore', 'Categories') }}
				</h4>
				<p>
					{{ appCategories }}
				</p>
			</div>

			<div v-if="externalResources.length > 0" class="app-details__section">
				<h4>{{ t('appstore', 'Resources') }}</h4>
				<ul class="app-details__documentation" :aria-label="t('appstore', 'Documentation')">
					<li v-for="resource of externalResources" :key="resource.id">
						<a
							class="appslink"
							:href="resource.href"
							target="_blank"
							rel="noreferrer noopener">
							{{ resource.label }} ↗
						</a>
					</li>
				</ul>
			</div>

			<div class="app-details__section">
				<h4>{{ t('appstore', 'Interact') }}</h4>
				<div class="app-details__interact">
					<NcButton
						:disabled="!app.bugs"
						:href="app.bugs ?? '#'"
						:aria-label="t('appstore', 'Report a bug')"
						:title="t('appstore', 'Report a bug')">
						<template #icon>
							<NcIconSvgWrapper :path="mdiBugOutline" />
						</template>
					</NcButton>
					<NcButton
						:disabled="!app.bugs"
						:href="app.bugs ?? '#'"
						:aria-label="t('appstore', 'Request feature')"
						:title="t('appstore', 'Request feature')">
						<template #icon>
							<NcIconSvgWrapper :path="mdiFeatureSearchOutline" />
						</template>
					</NcButton>
					<NcButton
						v-if="app.appstoreData?.discussion"
						:href="app.appstoreData.discussion"
						:aria-label="t('appstore', 'Ask questions or discuss')"
						:title="t('appstore', 'Ask questions or discuss')">
						<template #icon>
							<NcIconSvgWrapper :path="mdiTooltipQuestionOutline" />
						</template>
					</NcButton>
					<NcButton
						v-if="!app.internal"
						:href="rateAppUrl"
						:aria-label="t('appstore', 'Rate the app')"
						:title="t('appstore', 'Rate')">
						<template #icon>
							<NcIconSvgWrapper :path="mdiStar" />
						</template>
					</NcButton>
				</div>
			</div>

			<DaemonSelectionDialog
				v-if="app?.app_api"
				:show.sync="showSelectDaemonModal"
				:app="app"
				:deploy-options="deployOptions" />
		</div>
	</NcAppSidebarTab>
</template>

<script>
import { mdiBugOutline, mdiFeatureSearchOutline, mdiStar, mdiTextBoxOutline, mdiTooltipQuestionOutline, mdiToyBrickPlusOutline } from '@mdi/js'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import DaemonSelectionDialog from '../AppAPI/DaemonSelectionDialog.vue'
import AppManagement from '../../mixins/AppManagement.js'
import { useAppsStore } from '../../store/apps-store.js'

export default {
	name: 'AppDetailsTab',

	components: {
		NcAppSidebarTab,
		NcButton,
		NcDateTime,
		NcIconSvgWrapper,
		DaemonSelectionDialog,
	},

	mixins: [AppManagement],

	props: {
		app: {
			type: Object,
			required: true,
		},
	},

	setup() {
		const store = useAppsStore()

		return {
			store,

			productName: window.OC.theme.productName,

			mdiBugOutline,
			mdiFeatureSearchOutline,
			mdiStar,
			mdiTextBoxOutline,
			mdiTooltipQuestionOutline,
			mdiToyBrickPlusOutline,
		}
	},

	data() {
		return {
			groupCheckedAppsData: false,
			removeData: false,
			showDeployOptionsModal: false,
			showSelectDaemonModal: false,
			deployOptions: null,
		}
	},

	computed: {
		lastModified() {
			return (this.app.appstoreData?.releases ?? [])
				.map(({ lastModified }) => Date.parse(lastModified))
				.sort()
				.at(0) ?? null
		},

		/**
		 * App authors as comma separated string
		 */
		appAuthors() {
			if (!this.app) {
				return ''
			}

			const authorName = (xmlNode) => {
				if (xmlNode['@value']) {
					// Complex node (with email or homepage attribute)
					return xmlNode['@value']
				}
				// Simple text node
				return xmlNode
			}

			const authors = Array.isArray(this.app.author)
				? this.app.author.map(authorName)
				: [authorName(this.app.author)]

			return authors
				.sort((a, b) => a.split(' ').at(-1).localeCompare(b.split(' ').at(-1)))
				.join(', ')
		},

		appstoreUrl() {
			return `https://apps.nextcloud.com/apps/${this.app.id}`
		},

		/**
		 * Further external resources (e.g. website)
		 */
		externalResources() {
			const resources = []
			if (!this.app.internal) {
				resources.push({
					id: 'appstore',
					href: this.appstoreUrl,
					label: t('appstore', 'View in store'),
				})
			}
			if (this.app.website) {
				resources.push({
					id: 'website',
					href: this.app.website,
					label: t('appstore', 'Visit website'),
				})
			}
			if (this.app.documentation) {
				if (this.app.documentation.user) {
					resources.push({
						id: 'doc-user',
						href: this.app.documentation.user,
						label: t('appstore', 'Usage documentation'),
					})
				}
				if (this.app.documentation.admin) {
					resources.push({
						id: 'doc-admin',
						href: this.app.documentation.admin,
						label: t('appstore', 'Admin documentation'),
					})
				}
				if (this.app.documentation.developer) {
					resources.push({
						id: 'doc-developer',
						href: this.app.documentation.developer,
						label: t('appstore', 'Developer documentation'),
					})
				}
			}
			return resources
		},

		appCategories() {
			return [this.app.category].flat()
				.map((id) => this.store.getCategoryById(id)?.displayName ?? id)
				.join(', ')
		},

		rateAppUrl() {
			return `${this.appstoreUrl}#comments`
		},

		appGroups() {
			return this.app.groups.map((group) => {
				return { id: group, name: group }
			})
		},
	},

	beforeUnmount() {
		this.deployOptions = null
		unsubscribe('showDaemonSelectionModal')
	},

	mounted() {
		if (this.app.groups.length > 0) {
			this.groupCheckedAppsData = true
		}
		subscribe('showDaemonSelectionModal', (deployOptions) => {
			this.showSelectionModal(deployOptions)
		})
	},

	methods: {
		showSelectionModal(deployOptions = null) {
			this.deployOptions = deployOptions
			this.showSelectDaemonModal = true
		},
	},
}
</script>

<style scoped lang="scss">
.app-details {
	padding: 20px;

	&__authors {
		color: var(--color-text-maxcontrast);
	}

	&__section {
		margin-top: 15px;

		h4 {
			font-size: 16px;
			font-weight: bold;
			margin-block-end: 5px;
		}
	}

	&__interact {
		display: flex;
		flex-direction: row;
		flex-wrap: wrap;
		gap: 12px;
	}

	&__documentation {
		a {
			text-decoration: underline;
		}
		li {
			padding-inline-start: 20px;

			&::before {
				width: 5px;
				height: 5px;
				border-radius: 100%;
				background-color: var(--color-main-text);
				content: "";
				float: inline-start;
				margin-inline-start: -13px;
				position: relative;
				top: 10px;
			}
		}
	}
}

.force {
	color: var(--color-text-error);
	border-color: var(--color-border-error);
	background: var(--color-main-background);
}

.force:hover,
.force:active {
	color: var(--color-main-background);
	border-color: var(--color-border-error) !important;
	background: var(--color-error);
}

.missing-dependencies {
	list-style: initial;
	list-style-type: initial;
	list-style-position: inside;
}
</style>
