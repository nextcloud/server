<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
  -->
<script setup lang="ts">
import type { IStorage } from '../types.ts'

import { mdiPlus } from '@mdi/js'
import { loadState } from '@nextcloud/initial-state'
import { n, t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import AddExternalStorageDialog from '../components/AddExternalStorageDialog/AddExternalStorageDialog.vue'
import ExternalStorageTable from '../components/ExternalStorageTable.vue'
import UserMountSettings from '../components/UserMountSettings.vue'
import filesExternalSvg from '../../img/app-dark.svg?raw'
import { useStorages } from '../store/storages.ts'
import logger from '../utils/logger.ts'

const settings = loadState('files_external', 'settings', {
	docUrl: '',
	dependencyIssues: {
		messages: null as string[] | null,
		modules: null as Record<string, string[]> | null,
	},
	isAdmin: false,
})

const store = useStorages()

/** List of dependency issue messages */
const dependencyIssues = settings.dependencyIssues?.messages ?? []
/** Map of missing modules -> list of dependant backends */
const missingModules = settings.dependencyIssues?.modules ?? {}

const showDialog = ref(false)
const newStorage = ref<Partial<IStorage>>()

/**
 * Add a new external storage
 *
 * @param storage - The storage to add
 */
async function addStorage(storage?: Partial<IStorage>) {
	showDialog.value = false
	if (!storage) {
		return
	}

	try {
		if (settings.isAdmin) {
			await store.createGlobalStorage(storage)
		} else {
			await store.createUserStorage(storage)
		}
		newStorage.value = undefined
	} catch (error) {
		logger.error('Failed to add external storage', { error })
		showDialog.value = true
	}
}
</script>

<template>
	<NcSettingsSection
		:doc-url="settings.docUrl"
		:name="t('files_external', 'External storage')"
		:description="
			t('files_external', 'External storage enables you to mount external storage services and devices as secondary Nextcloud storage devices.')
				+ (settings.isAdmin
					? ' ' + t('files_external', 'You may also allow people to mount their own external storage services.')
					: ''
				)">
		<!-- Dependency error messages -->
		<NcNoteCard
			v-for="message, index of dependencyIssues"
			:key="index"
			type="error">
			{{ message }}
		</NcNoteCard>

		<!-- Missing modules for backends -->
		<NcNoteCard
			v-for="(dependants, module) in missingModules"
			:key="module"
			type="warning">
			<p>
				<template v-if="module === 'curl'">
					{{ t('files_external', 'The cURL support in PHP is not enabled or installed.') }}
				</template>
				<template v-else-if="module === 'ftp'">
					{{ t('files_external', 'The FTP support in PHP is not enabled or installed.') }}
				</template>
				<template v-else>
					{{ t('files_external', '{module} is not installed.', { module }) }}
				</template>
				{{ n(
					'files_external',
					'Please ask your system administrator to install it as otherwise mounting the following backend is not possible:',
					'Please ask your system administrator to install it as otherwise mounting the following backends is not possible:',
					dependants.length,
				) }}
			</p>
			<ul :class="$style.externalStoragesSection__dependantList" :aria-label="t('files_external', 'Dependant backends')">
				<li v-for="backend of dependants" :key="backend">
					{{ backend }}
				</li>
			</ul>
		</NcNoteCard>

		<!-- For user settings if the user has no permission or for user and admin settings if no storage was configured -->
		<NcEmptyContent
			v-if="false"
			:description="t('files_external', 'No external storage configured or you do not have the permission to configure them')">
			<template #icon>
				<NcIconSvgWrapper :svg="filesExternalSvg" :size="64" />
			</template>
		</NcEmptyContent>

		<ExternalStorageTable />

		<NcButton
			:class="$style.externalStoragesSection__newStorageButton"
			variant="primary"
			@click="showDialog = !showDialog">
			<template #icon>
				<NcIconSvgWrapper :path="mdiPlus" />
			</template>
			{{ t('files_external', 'Add external storage') }}
		</NcButton>

		<AddExternalStorageDialog
			v-model="newStorage"
			v-model:open="showDialog"
			@close="addStorage" />

		<UserMountSettings v-if="settings.isAdmin" />
	</NcSettingsSection>
</template>

<style module>
.externalStoragesSection__dependantList {
	list-style: disc !important;
	margin-inline-start: calc(var(--default-clickable-area) / 2);
}

.externalStoragesSection__newStorageButton {
	margin-top: var(--default-clickable-area);
}
</style>
