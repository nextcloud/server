<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import type { IBackend, IStorage } from '../types.ts'

import { mdiAccountGroupOutline, mdiInformationOutline, mdiPencilOutline, mdiTrashCanOutline } from '@mdi/js'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { NcChip, NcLoadingIcon, NcUserBubble, spawnDialog } from '@nextcloud/vue'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import AddExternalStorageDialog from './AddExternalStorageDialog/AddExternalStorageDialog.vue'
import { useUsers } from '../composables/useEntities.ts'
import { useStorages } from '../store/storages.ts'
import { StorageStatus, StorageStatusIcons, StorageStatusMessage } from '../types.ts'

const props = defineProps<{
	storage: IStorage
	isAdmin: boolean
}>()

const store = useStorages()

const backends = loadState<IBackend[]>('files_external', 'backends')
const backendName = computed(() => backends.find((b) => b.identifier === props.storage.backend)!.name)

const authMechanisms = loadState<IBackend[]>('files_external', 'authMechanisms')
const authMechanismName = computed(() => authMechanisms.find((a) => a.identifier === props.storage.authMechanism)!.name)

const checkingStatus = ref(false)
const status = computed(() => {
	if (checkingStatus.value) {
		return {
			icon: 'loading',
			label: t('files_external', 'Checking …'),
		}
	}

	const status = props.storage.status ?? StorageStatus.Indeterminate
	const label = props.storage.statusMessage || StorageStatusMessage[status]
	const icon = StorageStatusIcons[status]

	const isWarning = status === StorageStatus.NetworkError || status === StorageStatus.Timeout
	const isError = !isWarning && status !== StorageStatus.Success && status !== StorageStatus.Indeterminate

	return { icon, label, isWarning, isError }
})

const users = useUsers(() => props.storage.applicableUsers || [])

/**
 * Handle deletion of the external storage mount point
 */
async function onDelete() {
	await store.deleteStorage(props.storage)
}

/**
 * Handle editing of the external storage mount point
 */
async function onEdit() {
	const storage = await spawnDialog(AddExternalStorageDialog, {
		storage: props.storage,
	})

	if (!storage) {
		return
	}
	await store.updateStorage(storage as IStorage)
}

/**
 * Reload the status of the external storage mount point
 */
async function reloadStatus() {
	checkingStatus.value = true
	try {
		await store.reloadStorage(props.storage)
	} finally {
		checkingStatus.value = false
	}
}
</script>

<template>
	<tr :class="$style.storageTableRow">
		<td>
			<span class="hidden-visually">{{ status.label }}</span>
			<NcButton
				:aria-label="t('files_external', 'Recheck status')"
				:title="status.label"
				variant="tertiary-no-background"
				@click="reloadStatus">
				<template #icon>
					<NcLoadingIcon v-if="status.icon === 'loading'" />
					<NcIconSvgWrapper
						v-else
						:class="{
							[$style.storageTableRow__status_error]: status.isError,
							[$style.storageTableRow__status_warning]: status.isWarning,
						}"
						:path="status.icon" />
				</template>
			</NcButton>
		</td>
		<td>{{ storage.mountPoint }}</td>
		<td>{{ backendName }}</td>
		<td>{{ authMechanismName }}</td>
		<td v-if="isAdmin">
			<div :class="$style.storageTableRow__cellApplicable">
				<NcChip
					v-for="group of storage.applicableGroups"
					:key="group"
					:iconPath="mdiAccountGroupOutline"
					noClose
					:text="group" />
				<NcUserBubble
					v-for="user of users"
					:key="user.user"
					:displayName="user.displayName"
					:size="24"
					:user="user.user" />
			</div>
		</td>
		<td>
			<div v-if="isAdmin || storage.type === 'personal'" :class="$style.storageTableRow__cellActions">
				<NcButton
					:aria-label="t('files_external', 'Edit')"
					:title="t('files_external', 'Edit')"
					@click="onEdit">
					<template #icon>
						<NcIconSvgWrapper :path="mdiPencilOutline" />
					</template>
				</NcButton>
				<NcButton
					:aria-label="t('files_external', 'Delete')"
					:title="t('files_external', 'Delete')"
					variant="error"
					@click="onDelete">
					<template #icon>
						<NcIconSvgWrapper :path="mdiTrashCanOutline" />
					</template>
				</NcButton>
			</div>
			<NcIconSvgWrapper
				v-else
				inline
				:path="mdiInformationOutline"
				:name="t('files_external', 'System provided storage')"
				:title="t('files_external', 'System provided storage')" />
		</td>
	</tr>
</template>

<style module>
.storageTableRow__cellActions {
	display: flex;
	gap: var(--default-grid-baseline);
}

.storageTableRow__cellApplicable {
	display: flex;
	flex-wrap: wrap;
	gap: var(--default-grid-baseline);
	align-items: center;

	max-height: calc(48px + 2 * var(--default-grid-baseline));
	overflow: scroll;
}

.storageTableRow__status_warning {
	color: var(--color-element-warning);
}

.storageTableRow__status_error {
	color: var(--color-element-error);
}
</style>
