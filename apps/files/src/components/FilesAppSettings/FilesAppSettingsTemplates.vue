<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiClose, mdiFolderOpenOutline, mdiFolderOutline } from '@mdi/js'
import { FilePickerClosed, getFilePickerBuilder, showError } from '@nextcloud/dialogs'
import { Permission } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, onMounted, ref } from 'vue'
import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { loadTemplateDirectory, setTemplateDirectory, templateDirectory } from '../../store/templateDirectory.ts'
import { logger } from '../../utils/logger.ts'

const loading = ref(true)
const loadFailed = ref(false)
const saving = ref(false)
const disabled = computed(() => loading.value || loadFailed.value || saving.value)
const folderUrl = computed(() => generateUrl('/apps/files/') + '?dir=' + encodeURIComponent(templateDirectory.template_path))

onMounted(load)

/** Refresh the selection when opening Files settings. */
async function load() {
	loading.value = true
	loadFailed.value = false
	try {
		await loadTemplateDirectory()
	} catch (error) {
		loadFailed.value = true
		logger.error('Failed to load template directory', { error })
	} finally {
		loading.value = false
	}
}

/**
 * Save the selected folder or clear the selection.
 *
 * @param path The selected folder path
 */
async function save(path: string) {
	saving.value = true
	try {
		await setTemplateDirectory(path)
	} catch (error) {
		logger.error('Failed to update template directory', { error })
		showError(t('files', 'Unable to update the template folder'))
	} finally {
		saving.value = false
	}
}

/** Choose an existing readable folder. */
async function chooseFolder() {
	try {
		const picker = getFilePickerBuilder(t('files', 'Choose a template folder'))
			.setMultiSelect(false)
			.setMimeTypeFilter(['httpd/unix-directory'])
			.allowDirectories(true)
			.setCanPick((node) => (node.permissions & Permission.READ) !== 0)
			.startAt(templateDirectory.available ? templateDirectory.template_path : '/')
			.addButton({
				label: t('files', 'Select folder'),
				variant: 'primary',
				callback: () => {},
			})
			.build()
		const [folder] = await picker.pickNodes()
		if (folder) {
			await save(folder.path)
		}
	} catch (error) {
		if (!(error instanceof FilePickerClosed)) {
			logger.error('Failed to choose template directory', { error })
			showError(t('files', 'Unable to choose a template folder'))
		}
	}
}
</script>

<template>
	<NcAppSettingsSection id="templates" :name="t('files', 'Templates')">
		<p>{{ t('files', 'Choose a folder containing your personal document templates. Changing the folder does not move or copy any files.') }}</p>
		<NcNoteCard v-if="loadFailed" type="error">
			{{ t('files', 'Unable to load the template folder') }}
			<NcButton @click="load">
				{{ t('files', 'Retry') }}
			</NcButton>
		</NcNoteCard>
		<NcFormBox>
			<NcFormBoxButton
				:label="t('files', 'Personal template folder')"
				:description="loading ? t('files', 'Loading …') : templateDirectory.template_path || t('files', 'No folder selected')"
				:disabled="disabled"
				@click="chooseFolder">
				<template #icon>
					<div v-if="templateDirectory.template_path" class="template-settings__actions">
						<NcButton
							v-if="templateDirectory.available"
							:href="folderUrl"
							:disabled="disabled"
							:aria-label="t('files', 'Open folder')"
							:title="t('files', 'Open folder')"
							variant="tertiary">
							<template #icon>
								<NcIconSvgWrapper :path="mdiFolderOpenOutline" />
							</template>
						</NcButton>
						<NcButton
							:disabled="disabled"
							:aria-label="t('files', 'Clear selection')"
							:title="t('files', 'Clear selection')"
							variant="tertiary"
							@click="save('')">
							<template #icon>
								<NcIconSvgWrapper :path="mdiClose" />
							</template>
						</NcButton>
					</div>
					<NcIconSvgWrapper v-else :path="mdiFolderOutline" />
				</template>
			</NcFormBoxButton>
		</NcFormBox>
		<NcNoteCard v-if="!loading && !loadFailed && templateDirectory.template_path && !templateDirectory.available" type="warning">
			{{ t('files', 'This folder is no longer available. Choose another folder or clear the selection.') }}
		</NcNoteCard>
	</NcAppSettingsSection>
</template>

<style scoped lang="scss">
.template-settings__actions {
	// Keep the secondary actions above the form row's clickable area.
	position: relative;
	z-index: 1;
	display: flex;
	flex-shrink: 0;
}
</style>
