<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiClose, mdiFolderOpenOutline, mdiFolderOutline } from '@mdi/js'
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { FilePickerClosed, getFilePickerBuilder, showError } from '@nextcloud/dialogs'
import { Permission } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import { computed, onMounted, reactive, ref } from 'vue'
import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { loadTemplateDirectory, templateDirectory as personalTemplateDirectory, setTemplateDirectory } from '../../store/templateDirectory.ts'
import { logger } from '../../utils/logger.ts'

const props = defineProps<{ organization?: boolean }>()
const templateDirectory = props.organization
	? reactive({ template_path: '', available: false, owner: '' })
	: personalTemplateDirectory
const ownFolder = computed(() => !props.organization || ('owner' in templateDirectory && templateDirectory.owner === getCurrentUser()?.uid))
const organizationUrl = generateOcsUrl('apps/files/api/v1/templates/organization')
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
		if (props.organization) {
			const { data } = await axios.get(organizationUrl)
			Object.assign(templateDirectory, data.ocs.data)
		} else {
			await loadTemplateDirectory()
		}
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
		if (props.organization) {
			const { data } = await axios.put(organizationUrl, { templatePath: path })
			Object.assign(templateDirectory, data.ocs.data)
		} else {
			await setTemplateDirectory(path)
		}
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
			.startAt(templateDirectory.available && ownFolder.value ? templateDirectory.template_path : '/')
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
	<NcAppSettingsSection :id="organization ? 'organization-templates' : 'templates'" :name="organization ? t('files', 'Organization templates') : t('files', 'Templates')">
		<p v-if="!organization">
			{{ t('files', 'Choose a folder containing your personal document templates. Changing the folder does not move or copy any files.') }}
		</p>
		<p v-if="organization">
			{{ t('files', 'All files in this folder and its subfolders become available as templates to every user. Manage the templates by adding, replacing, or removing files in this folder.') }}
		</p>
		<p v-if="organization && 'owner' in templateDirectory && templateDirectory.owner">
			{{ t('files', 'Published by {user}', { user: templateDirectory.owner }) }}
		</p>
		<NcNoteCard v-if="loadFailed" type="error">
			{{ t('files', 'Unable to load the template folder') }}
			<NcButton @click="load">
				{{ t('files', 'Retry') }}
			</NcButton>
		</NcNoteCard>
		<NcFormBox>
			<NcFormBoxButton
				:label="organization ? t('files', 'Organization template folder') : t('files', 'Personal template folder')"
				:description="loading ? t('files', 'Loading …') : templateDirectory.template_path || t('files', 'No folder selected')"
				:disabled="disabled"
				@click="chooseFolder">
				<template #icon>
					<div v-if="templateDirectory.template_path || (organization && 'owner' in templateDirectory && templateDirectory.owner)" class="template-settings__actions">
						<NcButton
							v-if="templateDirectory.available && ownFolder"
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
		<NcNoteCard v-if="!loading && !loadFailed && (templateDirectory.template_path || (organization && 'owner' in templateDirectory && templateDirectory.owner)) && !templateDirectory.available" type="warning">
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
