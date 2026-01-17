<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script lang="ts">
import { loadState } from '@nextcloud/initial-state'

const { isAdmin } = loadState<{ isAdmin: boolean }>('files_external', 'settings')
const allowedBackendIds = loadState<string[]>('files_external', 'allowedBackends')
const backends = loadState<IBackend[]>('files_external', 'backends')
	.filter((b) => allowedBackendIds.includes(b.identifier))

const allAuthMechanisms = loadState<IAuthMechanism[]>('files_external', 'authMechanisms')
</script>

<script setup lang="ts">
import type { IAuthMechanism, IBackend, IStorage } from '../../types.ts'

import { t } from '@nextcloud/l10n'
import { computed, ref, toRaw, watch, watchEffect } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import ApplicableEntities from './ApplicableEntities.vue'
import AuthMechanismConfiguration from './AuthMechanismConfiguration.vue'
import BackendConfiguration from './BackendConfiguration.vue'
import MountOptions from './MountOptions.vue'

const open = defineModel<boolean>('open', { default: true })

const {
	storage = { backendOptions: {}, mountOptions: {}, type: isAdmin ? 'system' : 'personal' },
} = defineProps<{
	storage?: Partial<IStorage> & { backendOptions: IStorage['backendOptions'] }
}>()

defineEmits<{
	close: [storage?: Partial<IStorage>]
}>()

const internalStorage = ref(structuredClone(toRaw(storage)))
watchEffect(() => {
	if (open.value) {
		internalStorage.value = structuredClone(toRaw(storage))
	}
})

const backend = computed({
	get() {
		return backends.find((b) => b.identifier === internalStorage.value.backend)
	},
	set(value?: IBackend) {
		internalStorage.value.backend = value?.identifier
	},
})

const authMechanisms = computed(() => allAuthMechanisms
	.filter(({ scheme }) => backend.value?.authSchemes[scheme]))
const authMechanism = computed({
	get() {
		return authMechanisms.value.find((a) => a.identifier === internalStorage.value.authMechanism)
	},
	set(value?: IAuthMechanism) {
		internalStorage.value.authMechanism = value?.identifier
	},
})

// auto set the auth mechanism if there's only one available
watch(authMechanisms, () => {
	if (authMechanisms.value.length === 1) {
		internalStorage.value.authMechanism = authMechanisms.value[0]!.identifier
	}
})
</script>

<template>
	<NcDialog
		v-model:open="open"
		is-form
		:content-classes="$style.externalStorageDialog"
		:name="internalStorage.id ? t('files_external', 'Edit storage') : t('files_external', 'Add storage')"
		@submit="$emit('close', internalStorage)"
		@update:open="$event || $emit('close')">
		<NcTextField
			v-model="internalStorage.mountPoint"
			:label="t('files_external', 'Folder name')"
			required />

		<MountOptions v-model="internalStorage.mountOptions" />

		<ApplicableEntities
			v-if="isAdmin"
			v-model:groups="internalStorage.applicableGroups"
			v-model:users="internalStorage.applicableUsers" />

		<NcSelect
			v-model="backend"
			:options="backends"
			:disabled="!!(internalStorage.id && internalStorage.backend)"
			:input-label="t('files_external', 'External storage')"
			label="name"
			required />

		<NcSelect
			v-model="authMechanism"
			:options="authMechanisms"
			:disabled="!internalStorage.backend || authMechanisms.length <= 1 || !!(internalStorage.id && internalStorage.authMechanism)"
			:input-label="t('files_external', 'Authentication')"
			label="name"
			required />

		<BackendConfiguration
			v-if="backend"
			v-model="internalStorage.backendOptions"
			:class="$style.externalStorageDialog__configuration"
			:configuration="backend.configuration" />

		<AuthMechanismConfiguration
			v-if="authMechanism"
			v-model="internalStorage.backendOptions"
			:class="$style.externalStorageDialog__configuration"
			:auth-mechanism="authMechanism" />

		<template #actions>
			<NcButton v-if="storage.id" @click="$emit('close')">
				{{ t('files_external', 'Cancel') }}
			</NcButton>

			<NcButton variant="primary" type="submit">
				{{ storage.id ? t('files_external', 'Edit') : t('files_external', 'Create') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<style module>
.externalStorageDialog {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
	min-height: calc(14 * var(--default-clickable-area)) !important;
}

.externalStorageDialog__configuration {
	margin-block: 0.5rem;
}
</style>
