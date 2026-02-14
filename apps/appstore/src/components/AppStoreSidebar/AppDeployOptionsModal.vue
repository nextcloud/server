<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog
		:open="show"
		size="normal"
		:name="t('appstore', 'Advanced deploy options')"
		@update:open="$emit('update:show', $event)">
		<div class="modal__content">
			<p class="deploy-option__hint">
				{{ configuredDeployOptions === null ? t('appstore', 'Edit ExApp deploy options before installation') : t('appstore', 'Configured ExApp deploy options. Can be set only during installation') }}.
				<a v-if="deployOptionsDocsUrl" :href="deployOptionsDocsUrl">
					{{ t('appstore', 'Learn more') }}
				</a>
			</p>
			<h3 v-if="environmentVariables.length > 0 || (configuredDeployOptions !== null && configuredDeployOptions.environment_variables.length > 0)">
				{{ t('appstore', 'Environment variables') }}
			</h3>
			<template v-if="configuredDeployOptions === null">
				<div
					v-for="envVar in environmentVariables"
					:key="envVar.envName"
					class="deploy-option">
					<NcTextField v-model="deployOptions.environment_variables[envVar.envName]" :label="envVar.displayName" />
					<p class="deploy-option__hint">
						{{ envVar.description }}
					</p>
				</div>
			</template>
			<fieldset
				v-else-if="Object.keys(configuredDeployOptions).length > 0"
				class="envs">
				<legend class="deploy-option__hint">
					{{ t('appstore', 'ExApp container environment variables') }}
				</legend>
				<NcTextField
					v-for="(value, key) in configuredDeployOptions.environment_variables"
					:key="key"
					:label="value.displayName ?? key"
					:helper-text="value.description"
					:model-value="value.value"
					readonly />
			</fieldset>
			<template v-else>
				<p class="deploy-option__hint">
					{{ t('appstore', 'No environment variables defined') }}
				</p>
			</template>

			<h3>{{ t('appstore', 'Mounts') }}</h3>
			<template v-if="configuredDeployOptions === null">
				<p class="deploy-option__hint">
					{{ t('appstore', 'Define host folder mounts to bind to the ExApp container') }}
				</p>
				<NcNoteCard type="info" :text="t('appstore', 'Must exist on the Deploy daemon host prior to installing the ExApp')" />
				<div
					v-for="mount in deployOptions.mounts"
					:key="mount.hostPath"
					class="deploy-option"
					style="display: flex; align-items: center; justify-content: space-between; flex-direction: row;">
					<NcTextField v-model="mount.hostPath" :label="t('appstore', 'Host path')" />
					<NcTextField v-model="mount.containerPath" :label="t('appstore', 'Container path')" />
					<NcCheckboxRadioSwitch v-model="mount.readonly">
						{{ t('appstore', 'Read-only') }}
					</NcCheckboxRadioSwitch>
					<NcButton
						:aria-label="t('appstore', 'Remove mount')"
						style="margin-top: 6px;"
						@click="removeMount(mount)">
						<template #icon>
							<NcIconSvgWrapper :path="mdiDeleteOutline" />
						</template>
					</NcButton>
				</div>
				<div v-if="addingMount" class="deploy-option">
					<h4>
						{{ t('appstore', 'New mount') }}
					</h4>
					<div style="display: flex; align-items: center; justify-content: space-between; flex-direction: row;">
						<NcTextField
							ref="newMountHostPath"
							v-model="newMountPoint.hostPath"
							:label="t('appstore', 'Host path')"
							:aria-label="t('appstore', 'Enter path to host folder')" />
						<NcTextField
							v-model="newMountPoint.containerPath"
							:label="t('appstore', 'Container path')"
							:aria-label="t('appstore', 'Enter path to container folder')" />
						<NcCheckboxRadioSwitch
							v-model="newMountPoint.readonly"
							:aria-label="t('appstore', 'Toggle read-only mode')">
							{{ t('appstore', 'Read-only') }}
						</NcCheckboxRadioSwitch>
					</div>
					<div style="display: flex; align-items: center; margin-top: 4px;">
						<NcButton
							:aria-label="t('appstore', 'Confirm adding new mount')"
							@click="addMountPoint">
							<template #icon>
								<NcIconSvgWrapper :path="mdiCheck" />
							</template>
							{{ t('appstore', 'Confirm') }}
						</NcButton>
						<NcButton
							:aria-label="t('appstore', 'Cancel adding mount')"
							style="margin-left: 4px;"
							@click="cancelAddMountPoint">
							<template #icon>
								<NcIconSvgWrapper :path="mdiClose" />
							</template>
							{{ t('appstore', 'Cancel') }}
						</NcButton>
					</div>
				</div>
				<NcButton
					v-if="!addingMount"
					:aria-label="t('appstore', 'Add mount')"
					style="margin-top: 5px;"
					@click="startAddingMount">
					<template #icon>
						<NcIconSvgWrapper :path="mdiPlus" />
					</template>
					{{ t('appstore', 'Add mount') }}
				</NcButton>
			</template>
			<template v-else-if="configuredDeployOptions.mounts.length > 0">
				<p class="deploy-option__hint">
					{{ t('appstore', 'ExApp container mounts') }}
				</p>
				<div
					v-for="mount in configuredDeployOptions.mounts"
					:key="mount.hostPath"
					class="deploy-option"
					style="display: flex; align-items: center; justify-content: space-between; flex-direction: row;">
					<NcTextField v-model="mount.hostPath" :label="t('appstore', 'Host path')" readonly />
					<NcTextField v-model="mount.containerPath" :label="t('appstore', 'Container path')" readonly />
					<NcCheckboxRadioSwitch v-model="mount.readonly" disabled>
						{{ t('appstore', 'Read-only') }}
					</NcCheckboxRadioSwitch>
				</div>
			</template>
			<p v-else class="deploy-option__hint">
				{{ t('appstore', 'No mounts defined') }}
			</p>
		</div>

		<template v-if="!app.active && (app.canInstall || app.isCompatible) && configuredDeployOptions === null" #actions>
			<NcButton
				:title="enableButtonTooltip"
				:aria-label="enableButtonTooltip"
				variant="primary"
				:disabled="!app.canInstall || installing || isLoading || !defaultDeployDaemonAccessible || isInitializing || isDeploying"
				@click.stop="submitDeployOptions">
				{{ enableButtonText }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { mdiCheck, mdiClose, mdiDeleteOutline, mdiPlus } from '@mdi/js'
import axios from '@nextcloud/axios'
import { emit } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import AppManagement from '../../mixins/AppManagement.js'
import { useAppApiStore } from '../../store/app-api-store.ts'
import { useAppsStore } from '../../store/apps-store.ts'

export default {
	name: 'AppDeployOptionsModal',
	components: {
		NcDialog,
		NcTextField,
		NcButton,
		NcNoteCard,
		NcCheckboxRadioSwitch,
		NcIconSvgWrapper,
	},

	mixins: [AppManagement],
	props: {
		app: {
			type: Object,
			required: true,
		},

		show: {
			type: Boolean,
			required: true,
		},
	},

	setup(props) {
		// for AppManagement mixin
		const store = useAppsStore()
		const appApiStore = useAppApiStore()

		const environmentVariables = computed(() => {
			if (props.app?.releases?.length === 1) {
				return props.app?.releases[0]?.environmentVariables || []
			}
			return []
		})

		const deployOptions = ref({
			environment_variables: environmentVariables.value.reduce((acc, envVar) => {
				acc[envVar.envName] = envVar.default || ''
				return acc
			}, {}),
			mounts: [],
		})

		return {
			t,

			environmentVariables,
			deployOptions,
			store,
			appApiStore,
			mdiPlus,
			mdiCheck,
			mdiClose,
			mdiDeleteOutline,
		}
	},

	data() {
		return {
			addingMount: false,
			newMountPoint: {
				hostPath: '',
				containerPath: '',
				readonly: false,
			},

			addingPortBinding: false,
			configuredDeployOptions: null,
			deployOptionsDocsUrl: loadState('appstore', 'deployOptionsDocsUrl', null),
		}
	},

	watch: {
		show(newShow) {
			if (newShow) {
				this.fetchExAppDeployOptions()
			} else {
				this.configuredDeployOptions = null
			}
		},
	},

	methods: {
		startAddingMount() {
			this.addingMount = true
			this.$nextTick(() => {
				this.$refs.newMountHostPath.focus()
			})
		},

		addMountPoint() {
			this.deployOptions.mounts.push(this.newMountPoint)
			this.newMountPoint = {
				hostPath: '',
				containerPath: '',
				readonly: false,
			}
			this.addingMount = false
		},

		cancelAddMountPoint() {
			this.newMountPoint = {
				hostPath: '',
				containerPath: '',
				readonly: false,
			}
			this.addingMount = false
		},

		removeMount(mountToRemove) {
			this.deployOptions.mounts = this.deployOptions.mounts.filter((mount) => mount !== mountToRemove)
		},

		async fetchExAppDeployOptions() {
			return axios.get(generateUrl(`/apps/app_api/apps/deploy-options/${this.app.id}`))
				.then((response) => {
					this.configuredDeployOptions = response.data
				})
				.catch(() => {
					this.configuredDeployOptions = null
				})
		},

		async submitDeployOptions() {
			await this.appApiStore.fetchDockerDaemons()
			if (this.appApiStore.dockerDaemons.length === 1 && this.app.needsDownload) {
				this.enable(this.app.id, this.appApiStore.dockerDaemons[0], this.deployOptions)
			} else if (this.app.needsDownload) {
				emit('showDaemonSelectionModal', this.deployOptions)
			} else {
				this.enable(this.app.id, this.app.daemon, this.deployOptions)
			}
			this.$emit('update:show', false)
		},
	},
}
</script>

<style scoped>
.deploy-option {
	margin: calc(var(--default-grid-baseline) * 4) 0;
	display: flex;
	flex-direction: column;
	align-items: flex-start;

	&__hint {
		margin-top: 4px;
		font-size: 0.8em;
		color: var(--color-text-maxcontrast);
	}
}

.envs {
	width: 100%;
	overflow: auto;
	height: 100%;
	max-height: 300px;

	li {
		margin: 10px 0;
	}
}
</style>
