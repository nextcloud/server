<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcEmptyContent class="file-drop-empty-content"
		data-cy-files-sharing-file-drop
		:name="name">
		<template #icon>
			<NcIconSvgWrapper :svg="svgCloudUpload" />
		</template>
		<template #description>
			<p>
				{{ shareNote || t('files_sharing', 'Upload files to {foldername}.', { foldername }) }}
			</p>
			<p v-if="disclaimer">
				{{ t('files_sharing', 'By uploading files, you agree to the terms of service.') }}
			</p>
			<NcNoteCard v-if="getSortedUploads().length"
				class="file-drop-empty-content__note-card"
				type="success">
				<h2 id="file-drop-empty-content__heading">
					{{ t('files_sharing', 'Successfully uploaded files') }}
				</h2>
				<ul aria-labelledby="file-drop-empty-content__heading" class="file-drop-empty-content__list">
					<li v-for="file in getSortedUploads()" :key="file">
						{{ file }}
					</li>
				</ul>
			</NcNoteCard>
		</template>
		<template #action>
			<template v-if="disclaimer">
				<!-- Terms of service if enabled -->
				<NcButton type="primary" @click="showDialog = true">
					{{ t('files_sharing', 'View terms of service') }}
				</NcButton>
				<NcDialog close-on-click-outside
					content-classes="terms-of-service-dialog"
					:open.sync="showDialog"
					:name="t('files_sharing', 'Terms of service')"
					:message="disclaimer" />
			</template>
			<UploadPicker allow-folders
				:content="() => []"
				no-menu
				:destination="uploadDestination"
				multiple />
		</template>
	</NcEmptyContent>
</template>

<script lang="ts">
/* eslint-disable import/first */

// We need this on module level rather than on the instance as view will be refreshed by the files app after uploading
const uploads = new Set<string>()
</script>

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { getUploader, UploadPicker, UploadStatus } from '@nextcloud/upload'
import { ref } from 'vue'

import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import svgCloudUpload from '@mdi/svg/svg/cloud-upload.svg?raw'

defineProps<{
	foldername: string
}>()

const disclaimer = loadState<string>('files_sharing', 'disclaimer', '')
const shareLabel = loadState<string>('files_sharing', 'label', '')
const shareNote = loadState<string>('files_sharing', 'note', '')

const name = shareLabel || t('files_sharing', 'File drop')

const showDialog = ref(false)
const uploadDestination = getUploader().destination

getUploader()
	.addNotifier((upload) => {
		if (upload.status === UploadStatus.FINISHED && upload.file.name) {
			// if a upload is finished and is not a meta upload (name is set)
			// then we add the upload to the list of finished uploads to be shown to the user
			uploads.add(upload.file.name)
		}
	})

/**
 * Get the previous uploads as sorted list
 */
function getSortedUploads() {
	return [...uploads].sort((a, b) => a.localeCompare(b))
}
</script>

<style scoped lang="scss">
.file-drop-empty-content {
	margin: auto;
	max-width: max(50vw, 300px);

	.file-drop-empty-content__note-card {
		width: fit-content;
		margin-inline: auto;
	}

	#file-drop-empty-content__heading {
		margin-block: 0 10px;
		font-weight: bold;
		font-size: 20px;
	}

	.file-drop-empty-content__list {
		list-style: inside;
		max-height: min(350px, 33vh);
		overflow-y: scroll;
		padding-inline-end: calc(2 * var(--default-grid-baseline));
	}

	:deep(.terms-of-service-dialog) {
		min-height: min(100px, 20vh);
	}

	/* TODO fix in library */
	:deep(.empty-content__action) {
		display: flex;
		gap: var(--default-grid-baseline);
	}
}
</style>
