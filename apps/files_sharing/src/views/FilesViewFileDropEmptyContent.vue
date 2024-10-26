<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcEmptyContent class="file-drop-empty-content"
		data-cy-files-sharing-file-drop
		:name="t('files_sharing', 'File drop')">
		<template #icon>
			<NcIconSvgWrapper :svg="svgCloudUpload" />
		</template>
		<template #description>
			{{ t('files_sharing', 'Upload files to {foldername}.', { foldername }) }}
			{{ disclaimer === '' ? '' : t('files_sharing', 'By uploading files, you agree to the terms of service.') }}
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

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { getUploader, UploadPicker } from '@nextcloud/upload'
import { ref } from 'vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import svgCloudUpload from '@mdi/svg/svg/cloud-upload.svg?raw'

defineProps<{
	foldername: string
}>()

const disclaimer = loadState<string>('files_sharing', 'disclaimer', '')
const showDialog = ref(false)
const uploadDestination = getUploader().destination
</script>

<style scoped>
:deep(.terms-of-service-dialog) {
	min-height: min(100px, 20vh);
}
/* TODO fix in library */
.file-drop-empty-content :deep(.empty-content__action) {
	display: flex;
	gap: var(--default-grid-baseline);
}
</style>
