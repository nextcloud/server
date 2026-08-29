<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { ref, watchEffect } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import MarkdownPreview from '../../../appstore/src/components/MarkdownPreview.vue'
import { logger } from '../logger.ts'

type DialogButtons = typeof NcDialog['$props']['buttons']

const { appId, version = undefined } = defineProps<{
	appId: string
	version?: string
}>()

const emit = defineEmits<{
	/**
	 * Event that is called when the "Get started"-button is pressed
	 */
	close: [dismissed: boolean]
}>()

const dialogButtons: DialogButtons = [
	{
		label: t('updatenotification', 'Give feedback'),
		callback: () => {
			window.open(`https://apps.nextcloud.com/apps/${appId}#comments`, '_blank', 'noreferrer noopener')
		},
	},
	{
		label: t('updatenotification', 'Get started'),
		variant: 'primary',
		callback: () => {
			emit('close', true)
		},
	},
]

const appName = ref(appId)
const appVersion = ref(version ?? '')
const markdown = ref<string>('')
watchEffect(() => {
	const url = version
		? generateOcsUrl('/apps/updatenotification/api/v1/changelog/{app}?version={version}', { version, app: appId })
		: generateOcsUrl('/apps/updatenotification/api/v1/changelog/{app}', { version, app: appId })

	axios.get(url)
		.then(({ data }) => {
			appName.value = data.ocs.data.appName
			appVersion.value = data.ocs.data.version
			markdown.value = data.ocs.data.content
		})
		.catch((error) => {
			if (error?.response?.status === 404) {
				appName.value = appId
				markdown.value = t('updatenotification', 'No changelog available')
			} else {
				logger.error('Failed to load changelog entry', error)
				emit('close', false)
			}
		})
})
</script>

<template>
	<NcDialog
		:contentClasses="$style.appChangelogDialog"
		:buttons="dialogButtons"
		:name="t('updatenotification', 'What\'s new in {app} {version}', { app: appName, version: appVersion })"
		:open="markdown !== undefined"
		size="normal"
		@update:open="emit('close', true)">
		<MarkdownPreview :class="$style.appChangelogDialog__text" :text="markdown" :minHeadingLevel="3" />
	</NcDialog>
</template>

<style module>
.appChangelogDialog {
	min-height: 50vh !important;
}

.appChangelogDialog__text {
	padding-inline: 14px;
}
</style>
