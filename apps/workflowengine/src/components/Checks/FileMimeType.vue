<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<SelectWithCustomValue
		:aria-label="t('workflowengine', 'File type')"
		:customLabel="t('workflowengine', 'Custom MIME type')"
		:customPlaceholder="t('workflowengine', 'e.g. httpd/unix-directory')"
		:modelValue="modelValue"
		:placeholder="t('workflowengine', 'Select a file type')"
		:predefinedValues="predefinedTypes"
		@update:modelValue="$emit('update:modelValue', $event)" />
</template>

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { imagePath } from '@nextcloud/router'
import SelectWithCustomValue from './SelectWithCustomValue.vue'

withDefaults(defineProps<{ modelValue?: string }>(), { modelValue: '' })

defineEmits<{ 'update:modelValue': [value: string] }>()

const predefinedTypes = [
	{
		iconUrl: imagePath('core', 'filetypes/audio'),
		label: t('workflowengine', 'Audio'),
		id: '/audio\\/.*/',
	},
	{
		icon: 'icon-folder',
		label: t('workflowengine', 'Folder'),
		id: 'httpd/unix-directory',
	},
	{
		icon: 'icon-picture',
		label: t('workflowengine', 'Images'),
		id: '/image\\/.*/',
	},
	{
		iconUrl: imagePath('core', 'filetypes/x-office-document'),
		label: t('workflowengine', 'Office documents'),
		id: '/(vnd\\.(ms-|openxmlformats-|oasis\\.opendocument).*)$/',
	},
	{
		iconUrl: imagePath('core', 'filetypes/application-pdf'),
		label: t('workflowengine', 'PDF documents'),
		id: 'application/pdf',
	},
	{
		iconUrl: imagePath('core', 'filetypes/video'),
		label: t('workflowengine', 'Video'),
		id: '/video\\/.*/',
	},
]
</script>
