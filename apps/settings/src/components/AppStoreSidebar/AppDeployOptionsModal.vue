<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog :open="show"
		size="normal"
		:name="t('settings', 'Advanced deploy options')"
		@update:open="$emit('update:show', $event)">
		<div class="modal__content">
			<p class="deploy-option__hint">
				{{ configuredDeployOptions === null ? t('settings', 'Edit ExApp deploy options before installation') : t('settings', 'Configured ExApp deploy options. Can be set only during installation') }}.
				<a v-if="deployOptionsDocsUrl" :href="deployOptionsDocsUrl">
					{{ t('settings', 'Learn more') }}
				</a>
			</p>
			<h3 v-if="environmentVariables.length > 0 || (configuredDeployOptions !== null && configuredDeployOptions.environment_variables.length > 0)">
				{{ t('settings', 'Environment variables') }}
			</h3>
			<template v-if="configuredDeployOptions === null">
				<div v-for="envVar in environmentVariables"
					:key="envVar.envName"
					class="deploy-option">
					<NcTextField :label="envVar.displayName" :value.sync="deployOptions.environment_variables[envVar.envName]" />
					<p class="deploy-option__hint">
						{{ envVar.description }}
					</p>
				</div>
			</template>
			<fieldset v-else-if="Object.keys(configuredDeployOptions).length > 0"
				class="envs">
				<legend class="deploy-option__hint">
					{{ t('settings', 'ExApp container environment variables') }}
				</legend>
				<NcTextField v-for="(value, key) in configuredDeployOptions.environment_variables"
					:key="key"
					:label="value.displayName ?? key"
					:helper-text="value.description"
					:value="value.value"
					readonly />
			</fieldset>
			<template v-else>
				<p class="deploy-option__hint">
					{{ t('settings', 'No environment variables defined') }}
				</p>
			</template>

			<h3>{{ t('settings', 'Mounts') }}</h3>
			<template v-if="configuredDeployOptions === null">
				<p class="deploy-option__hint">
					{{ t('settings', 'Define host folder mounts to bind to the ExApp container') }}
				</p>
				<NcNoteCard type="info" :text="t('settings', 'Must exist on the Deploy daemon host prior to installing the ExApp')" />
				<div v-for="mount in deployOptions.mounts"
					:key="mount.hostPath"
					class="deploy-option"
					style="display: flex; align-items: center; justify-content: space-between; flex-direction: row;">
					<NcTextField :label="t('settings', 'Host path')" :value.sync="mount.hostPath" />
					<NcTextField :label="t('settings', 'Container path')" :value.sync="mount.containerPath" />
					<NcCheckboxRadioSwitch :checked.sync="mount.readonly">
						{{ t('settings', 'Read-only') }}
					</NcCheckboxRadioSwitch>
					<NcButton :aria-label="t('settings', 'Remove mount')"
						style="margin-top: 6px;"
						@click="removeMount(mount)">
						<template #icon>
							<NcIconSvgWrapper :path="mdiDelete" />
						</template>
					</NcButton>
				</div>
				<div v-if="addingMount" class="deploy-option">
					<h4>
						{{ t('settings', 'New mount') }}
					</h4>
					<div style="display: flex; align-items: center; justify-content: space-between; flex-direction: row;">
						<NcTextField ref="newMountHostPath"
							:label="t('settings', 'Host path')"
							:aria-label="t('settings', 'Enter path to host folder')"
							:value.sync="newMountPoint.hostPath" />
						<NcTextField :label="t('settings', 'Container path')"
							:aria-label="t('settings', 'Enter path to container folder')"
							:value.sync="newMountPoint.containerPath" />
						<NcCheckboxRadioSwitch :checked.sync="newMountPoint.readonly"
							:aria-label="t('settings', 'Toggle read-only mode')">
							{{ t('settings', 'Read-only') }}
						</NcCheckboxRadioSwitch>
					</div>
					<div style="display: flex; align-items: center; margin-top: 4px;">
						<NcButton :aria-label="t('settings', 'Confirm adding new mount')"
							@click="addMountPoint">
							<template #icon>
								<NcIconSvgWrapper :path="mdiCheck" />
							</template>
							{{ t('settings', 'Confirm') }}
						</NcButton>
						<NcButton :aria-label="t('settings', 'Cancel adding mount')"
							style="margin-left: 4px;"
							@click="cancelAddMountPoint">
							<template #icon>
								<NcIconSvgWrapper :path="mdiClose" />
							</template>
							{{ t('settings', 'Cancel') }}
						</NcButton>
					</div>
				</div>
				<NcButton v-if="!addingMount"
					:aria-label="t('settings', 'Add mount')"
					style="margin-top: 5px;"
					@click="startAddingMount">
					<template #icon>
						<NcIconSvgWrapper :path="mdiPlus" />
					</template>
					{{ t('settings', 'Add mount') }}
				</NcButton>
			</template>
			<template v-else-if="configuredDeployOptions.mounts.length > 0">
				<p class="deploy-option__hint">
					{{ t('settings', 'ExApp container mounts') }}
				</p>
				<div v-for="mount in configuredDeployOptions.mounts"
					:key="mount.hostPath"
					class="deploy-option"
					style="display: flex; align-items: center; justify-content: space-between; flex-direction: row;">
					<NcTextField :label="t('settings', 'Host path')" :value.sync="mount.hostPath" readonly />
					<NcTextField :label="t('settings', 'Container path')" :value.sync="mount.containerPath" readonly />
					<NcCheckboxRadioSwitch :checked.sync="mount.readonly" disabled>
						{{ t('settings', 'Read-only') }}
					</NcCheckboxRadioSwitch>
				</div>
			</template>
			<p v-else class="deploy-option__hint">
				{{ t('settings', 'No mounts defined') }}
			</p>
		</div>

		<template v-if="!app.active && (app.canInstall || app.isCompatible) && configuredDeployOptions === null" #actions>
			<NcButton :title="enableButtonTooltip"
				:aria-label="enableButtonTooltip"
				type="primary"
				:disabled="!app.canInstall || installing || isLoading || !defaultDeployDaemonAccessible || isInitializing || isDeploying"
				@click.stop="submitDeployOptions">
				{{ enableButtonText }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { computed, ref } from 'vue'

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'

import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

import { mdiPlus, mdiCheck, mdiClose, mdiDelete } from '@mdi/js'

import { useAppApiStore } from '../../store/app-api-store.ts'
import { useAppsStore } from '../../store/apps-store.ts'

import AppManagement from '../../mixins/AppManagement.js'

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
			environmentVariables,
			deployOptions,
			store,
			appApiStore,
			mdiPlus,
			mdiCheck,
			mdiClose,
			mdiDelete,
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
			deployOptionsDocsUrl: loadState('settings', 'deployOptionsDocsUrl', null),
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
			this.deployOptions.mounts = this.deployOptions.mounts.filter(mount => mount !== mountToRemove)
		},
		async fetchExAppDeployOptions() {
			return axios.get(generateUrl(`/apps/app_api/apps/deploy-options/${this.app.id}`))
				.then(response => {
					this.configuredDeployOptions = response.data
				})
				.catch(() => {
					this.configuredDeployOptions = null
				})
		},
		submitDeployOptions() {
			this.enable(this.app.id, this.deployOptions)
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
