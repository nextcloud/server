<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { OCSResponse } from '@nextcloud/typings/ocs'

import axios, { isAxiosError } from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { computed, ref, shallowRef } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcProgressBar from '@nextcloud/vue/components/NcProgressBar'
import logger from '../../logger.ts'
import { SanitizeFilenameStatus } from '../../models/SanitizeFilenameStatus.ts'

type ApiStatus = { total: number, processed: number, errors?: Record<string, string[]>, status: SanitizeFilenameStatus }

const { status: initialStatus } = loadState<{ isRunningSanitization: boolean, status: ApiStatus }>('files', 'filesCompatibilitySettings')

const loading = ref(false)
const renameLimit = ref(10)
const status = ref(initialStatus.status)
const processedUsers = ref(initialStatus.processed)
const totalUsers = ref(initialStatus.total)
const errors = shallowRef<ApiStatus['errors']>(initialStatus.errors || {})

const progress = computed(() => processedUsers.value > 0 ? Math.round((processedUsers.value * 100) / totalUsers.value) : 0)
const isRunning = computed(() => status.value === SanitizeFilenameStatus.Scheduled || status.value === SanitizeFilenameStatus.Running)

/**
 * Start the sanitization process
 */
async function startSanitization() {
	if (isRunning.value) {
		return
	}

	try {
		loading.value = true
		await axios.post(generateOcsUrl('apps/files/api/v1/filenames/sanitization'), {
			limit: renameLimit.value,
		})
		status.value = SanitizeFilenameStatus.Scheduled
	} catch (error) {
		logger.error('Failed to start filename sanitization.', { error })

		if (isAxiosError(error) && error.response?.data?.ocs) {
			showError((error.response.data as OCSResponse).ocs.meta.message!)
		} else {
			showError(t('files', 'Failed to start filename sanitization.'))
		}
	} finally {
		loading.value = false
	}
}

/**
 * Refresh the filename sanitization status
 */
async function refreshStatus() {
	if (loading.value) {
		return
	}

	try {
		loading.value = true
		const { data } = await axios.get<OCSResponse<ApiStatus>>(generateOcsUrl('apps/files/api/v1/filenames/sanitization'))
		status.value = data.ocs.data.status
		totalUsers.value = data.ocs.data.total
		processedUsers.value = data.ocs.data.processed
		errors.value = data.ocs.data.errors || {}
	} catch (error) {
		logger.error('Failed to refresh filename sanitization status.', { error })
		showError(t('files', 'Failed to refresh filename sanitization status.'))
	} finally {
		loading.value = false
	}
}
</script>

<template>
	<NcNoteCard v-if="isRunning">
		<div class="sanitize-filenames__progress-container">
			<p>
				{{ t('files', 'Filename sanitization in progress.') }}
				<br>
				<template v-if="processedUsers > 0">
					{{ t('files', 'Currently {processedUsers} of {totalUsers} accounts are already processed.', { processedUsers, totalUsers }) }}
				</template>
				<template v-else>
					{{ t('files', 'Preparing …') }}
				</template>
			</p>
			<NcProgressBar :value="progress" :size="12" />
			<NcButton variant="tertiary" @click="refreshStatus">
				<template v-if="loading" #icon>
					<NcLoadingIcon />
				</template>
				{{ t('files', 'Refresh') }}
			</NcButton>
		</div>
	</NcNoteCard>

	<NcNoteCard v-else-if="status === SanitizeFilenameStatus.Done" type="success">
		{{ t('files', 'All files have been santized for Windows filename support.') }}
	</NcNoteCard>

	<form
		v-else
		class="sanitize-filenames__form"
		:disabled="loading"
		@submit.stop.prevent="startSanitization">
		<NcNoteCard v-if="status === SanitizeFilenameStatus.Error" type="error">
			{{ t('files', 'Some files could not be sanitized, please check your logs.') }}
			<ul class="sanitize-filenames__errors" :aria-label="t('files', 'Sanitization errors')">
				<li v-for="[user, failedFiles] of Object.entries(errors)" :key="user">
					<h4>{{ user }}:</h4>
					<ul :aria-label="t('files', 'Not sanitized filenames')">
						<li v-for="file of failedFiles" :key="file">
							{{ file }}
						</li>
					</ul>
				</li>
			</ul>
		</NcNoteCard>
		<NcNoteCard>
			{{ t('files', 'Windows filename support has been enabled.') }}
			<br>
			{{ t('files', 'While this blocks users from creating new files with unsupported filenames, existing files are not yet renamed and thus still may break sync on Windows.') }}
			{{ t('files', 'You can trigger a rename of files with invalid filenames, this will be done in the background and may take some time.') }}
			{{ t('files', 'Please note that this may cause high workload on the sync clients.') }}
		</NcNoteCard>

		<fieldset class="sanitize-filenames__fields">
			<NcInputField
				v-model="renameLimit"
				:label="t('files', 'Limit')"
				:helper-text="t('files', 'This allows to configure how many users should be processed in one background job run.')"
				min="1"
				type="number" />

			<NcButton type="submit" variant="error">
				<template v-if="loading" #icon>
					<NcLoadingIcon />
				</template>
				{{ t('files', 'Sanitize filenames') }}
				<span v-if="loading" class="hidden-visually">
					{{ t('files', '(starting)') }}
				</span>
			</NcButton>
		</fieldset>
	</form>
</template>

<style scoped>
.sanitize-filenames__progress-container {
	align-items: end;
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
}

.sanitize-filenames__form {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
}

.sanitize-filenames__fields {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);

	align-items: end;
	max-width: 400px;
}
</style>
