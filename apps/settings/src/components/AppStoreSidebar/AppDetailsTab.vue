<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppSidebarTab
		id="details"
		:name="t('settings', 'Details')"
		:order="1">
		<template #icon>
			<NcIconSvgWrapper :path="mdiTextBoxOutline" />
		</template>
		<div class="app-details">
			<div class="app-details__actions">
				<div v-if="app.active && canLimitToGroups(app)" class="app-details__actions-groups">
					<input
						:id="`groups_enable_${app.id}`"
						v-model="groupCheckedAppsData"
						type="checkbox"
						:value="app.id"
						class="groups-enable__checkbox checkbox"
						@change="setGroupLimit">
					<label :for="`groups_enable_${app.id}`">{{ t('settings', 'Limit to groups') }}</label>
					<input
						type="hidden"
						class="group_select"
						:title="t('settings', 'All')"
						value="">
					<br>
					<label for="limitToGroups">
						<span>{{ t('settings', 'Limit app usage to groups') }}</span>
					</label>
					<NcSelect
						v-if="isLimitedToGroups(app)"
						input-id="limitToGroups"
						:options="groups"
						:model-value="appGroups"
						:limit="5"
						label="name"
						:multiple="true"
						keep-open
						@option:selected="addGroupLimitation"
						@option:deselected="removeGroupLimitation"
						@search="asyncFindGroup">
						<span slot="noResult">{{ t('settings', 'No results') }}</span>
					</NcSelect>
				</div>
				<div class="app-details__actions-manage">
					<input
						v-if="app.update"
						class="update primary"
						type="button"
						:value="t('settings', 'Update to {version}', { version: app.update })"
						:disabled="installing || isLoading || isManualInstall"
						@click="update(app.id)">
					<input
						v-if="app.canUnInstall"
						class="uninstall"
						type="button"
						:value="t('settings', 'Remove')"
						:disabled="installing || isLoading"
						@click="remove(app.id, removeData)">
					<input
						v-if="app.active"
						class="enable"
						type="button"
						:value="disableButtonText"
						:disabled="installing || isLoading || isInitializing || isDeploying"
						@click="disable(app.id)">
					<input
						v-if="!app.active && (app.canInstall || app.isCompatible)"
						:title="enableButtonTooltip"
						:aria-label="enableButtonTooltip"
						class="enable primary"
						type="button"
						:value="enableButtonText"
						:disabled="!app.canInstall || installing || isLoading || !defaultDeployDaemonAccessible || isInitializing || isDeploying"
						@click="enableButtonAction">
					<input
						v-else-if="!app.active && !app.canInstall"
						:title="forceEnableButtonTooltip"
						:aria-label="forceEnableButtonTooltip"
						class="enable force"
						type="button"
						:value="forceEnableButtonText"
						:disabled="installing || isLoading"
						@click="forceEnable(app.id)">
					<NcButton
						v-if="app?.app_api && (app.canInstall || app.isCompatible)"
						:aria-label="t('settings', 'Advanced deploy options')"
						variant="secondary"
						@click="() => showDeployOptionsModal = true">
						<template #icon>
							<NcIconSvgWrapper :path="mdiToyBrickPlusOutline" />
						</template>
						{{ t('settings', 'Deploy options') }}
					</NcButton>
				</div>
				<p v-if="!defaultDeployDaemonAccessible" class="warning">
					{{ t('settings', 'Default Deploy daemon is not accessible') }}
				</p>
				<NcCheckboxRadioSwitch
					v-if="app.canUnInstall"
					:model-value="removeData"
					:disabled="installing || isLoading || !defaultDeployDaemonAccessible"
					@update:modelValue="toggleRemoveData">
					{{ t('settings', 'Delete data on remove') }}
				</NcCheckboxRadioSwitch>
			</div>

			<ul class="app-details__dependencies">
				<li v-if="app.missingMinOwnCloudVersion">
					{{ t('settings', 'This app has no minimum {productName} version assigned. This will be an error in the future.', { productName }) }}
				</li>
				<li v-if="app.missingMaxOwnCloudVersion">
					{{ t('settings', 'This app has no maximum {productName} version assigned. This will be an error in the future.', { productName }) }}
				</li>
				<li v-if="!app.canInstall">
					{{ t('settings', 'This app cannot be installed because the following dependencies are not fulfilled:') }}
					<ul class="missing-dependencies">
						<li v-for="(dep, index) in app.missingDependencies" :key="index">
							{{ dep }}
						</li>
					</ul>
				</li>
			</ul>

			<div v-if="lastModified && !app.shipped" class="app-details__section">
				<h4>
					{{ t('settings', 'Latest updated') }}
				</h4>
				<NcDateTime :timestamp="lastModified" />
			</div>

			<div class="app-details__section">
				<h4>
					{{ t('settings', 'Author') }}
				</h4>
				<p class="app-details__authors">
					{{ appAuthors }}
				</p>
			</div>

			<div class="app-details__section">
				<h4>
					{{ t('settings', 'Categories') }}
				</h4>
				<p>
					{{ appCategories }}
				</p>
			</div>

			<div v-if="externalResources.length > 0" class="app-details__section">
				<h4>{{ t('settings', 'Resources') }}</h4>
				<ul class="app-details__documentation" :aria-label="t('settings', 'Documentation')">
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
				<h4>{{ t('settings', 'Interact') }}</h4>
				<div class="app-details__interact">
					<NcButton
						:disabled="!app.bugs"
						:href="app.bugs ?? '#'"
						:aria-label="t('settings', 'Report a bug')"
						:title="t('settings', 'Report a bug')">
						<template #icon>
							<NcIconSvgWrapper :path="mdiBugOutline" />
						</template>
					</NcButton>
					<NcButton
						:disabled="!app.bugs"
						:href="app.bugs ?? '#'"
						:aria-label="t('settings', 'Request feature')"
						:title="t('settings', 'Request feature')">
						<template #icon>
							<NcIconSvgWrapper :path="mdiFeatureSearchOutline" />
						</template>
					</NcButton>
					<NcButton
						v-if="app.appstoreData?.discussion"
						:href="app.appstoreData.discussion"
						:aria-label="t('settings', 'Ask questions or discuss')"
						:title="t('settings', 'Ask questions or discuss')">
						<template #icon>
							<NcIconSvgWrapper :path="mdiTooltipQuestionOutline" />
						</template>
					</NcButton>
					<NcButton
						v-if="!app.internal"
						:href="rateAppUrl"
						:aria-label="t('settings', 'Rate the app')"
						:title="t('settings', 'Rate')">
						<template #icon>
							<NcIconSvgWrapper :path="mdiStar" />
						</template>
					</NcButton>
				</div>
			</div>

			<AppDeployOptionsModal
				v-if="app?.app_api"
				:show.sync="showDeployOptionsModal"
				:app="app" />
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
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import DaemonSelectionDialog from '../AppAPI/DaemonSelectionDialog.vue'
import AppDeployOptionsModal from './AppDeployOptionsModal.vue'
import AppManagement from '../../mixins/AppManagement.js'
import { useAppApiStore } from '../../store/app-api-store.js'
import { useAppsStore } from '../../store/apps-store.js'

export default {
	name: 'AppDetailsTab',

	components: {
		NcAppSidebarTab,
		NcButton,
		NcDateTime,
		NcIconSvgWrapper,
		NcSelect,
		NcCheckboxRadioSwitch,
		AppDeployOptionsModal,
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
		const appApiStore = useAppApiStore()

		return {
			store,
			appApiStore,

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
					label: t('settings', 'View in store'),
				})
			}
			if (this.app.website) {
				resources.push({
					id: 'website',
					href: this.app.website,
					label: t('settings', 'Visit website'),
				})
			}
			if (this.app.documentation) {
				if (this.app.documentation.user) {
					resources.push({
						id: 'doc-user',
						href: this.app.documentation.user,
						label: t('settings', 'Usage documentation'),
					})
				}
				if (this.app.documentation.admin) {
					resources.push({
						id: 'doc-admin',
						href: this.app.documentation.admin,
						label: t('settings', 'Admin documentation'),
					})
				}
				if (this.app.documentation.developer) {
					resources.push({
						id: 'doc-developer',
						href: this.app.documentation.developer,
						label: t('settings', 'Developer documentation'),
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

		groups() {
			return this.$store.getters.getGroups
				.filter((group) => group.id !== 'disabled')
				.sort((a, b) => a.name.localeCompare(b.name))
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
		toggleRemoveData() {
			this.removeData = !this.removeData
		},

		showSelectionModal(deployOptions = null) {
			this.deployOptions = deployOptions
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
.app-details {
	padding: 20px;

	&__actions {
		// app management
		&-manage {
			// if too many, shrink them and ellipsis
			display: flex;
			align-items: center;
			input {
				flex: 0 1 auto;
				min-width: 0;
				text-overflow: ellipsis;
				white-space: nowrap;
				overflow: hidden;
			}
		}
	}
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
