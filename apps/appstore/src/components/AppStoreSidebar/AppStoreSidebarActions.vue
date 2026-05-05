<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="app-sidebar-manage__actions">
		<div v-if="app.active && canLimitToGroups(app)" class="app-sidebar-manage__actions-groups">
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
			<br>
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
		<div class="app-sidebar-manage__actions-manage">
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

		<AppDeployOptionsModal
			v-if="app?.app_api"
			:show.sync="showDeployOptionsModal"
			:app="app" />
	</div>
</template>

<script>
import { mdiToyBrickPlusOutline } from '@mdi/js'
import { emit } from '@nextcloud/event-bus'
import { translate as t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import AppDeployOptionsModal from './AppDeployOptionsModal.vue'
import AppManagement from '../../mixins/AppManagement.js'
import { useAppApiStore } from '../../store/app-api-store.js'

export default {
	name: 'AppStoreSidebarActions',

	components: {
		NcButton,
		NcSelect,
		NcCheckboxRadioSwitch,
		NcIconSvgWrapper,
		AppDeployOptionsModal,
	},

	mixins: [AppManagement],

	props: {
		app: {
			type: Object,
			required: true,
		},
	},

	setup() {
		const appApiStore = useAppApiStore()
		return {
			appApiStore,
			t,
			mdiToyBrickPlusOutline,
		}
	},

	data() {
		return {
			removeData: false,
			showDeployOptionsModal: false,
		}
	},

	computed: {
		groups() {
			return this.$store.getters.getGroups
				.filter((group) => group.id !== 'disabled')
				.sort((a, b) => a.name.localeCompare(b.name))
		},
	},

	methods: {
		toggleRemoveData() {
			this.removeData = !this.removeData
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
				emit('showDaemonSelectionModal')
			} else {
				this.enable(this.app.id, this.app.daemon)
			}
		},
	},
}
</script>

<style scoped lang="scss">
.app-sidebar-manage {
	&__actions {
		&-manage {
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
</style>
